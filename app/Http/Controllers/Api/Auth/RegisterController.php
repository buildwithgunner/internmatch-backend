<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\OtpMail;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:student,company,admin',
        ]);

        $role = $request->role;

        // Check email uniqueness across all tables
        $emailTaken = User::where('email', $request->email)->exists() ||
                      Company::where('email', $request->email)->exists() ||
                      Admin::where('email', $request->email)->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        // Validate secret key for admins
        if ($role === 'admin') {
            $secretKey = config('app.admin_registration_key', 'buildwithme');
            if ($request->admin_key !== $secretKey) {
                throw ValidationException::withMessages([
                    'admin_key' => ['Invalid admin registration key.'],
                ]);
            }
        }

        $otp = rand(100000, 999999);

        // Store in Cache for 10 minutes instead of DB
        $cacheKey = 'pending_reg_' . $request->email;
        Cache::put($cacheKey, [
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $role,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(10),
        ], now()->addMinutes(10));

        Mail::to($request->email)->send(new OtpMail(
            $otp,
            'Verify Your Account',
            'Welcome! Please use the following OTP code to verify your account:'
        ));

        return response()->json([
            'message'            => 'Registration successful! Please check your email for OTP verification code.',
            'needs_verification' => true,
            'email'              => $request->email,
            'role'               => $role,
        ], 201);
    }

    public function verifyAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:student,company,admin',
            'otp'   => 'required|string',
        ]);

        $role  = $request->role;
        $email = $request->email;
        $otp   = $request->otp;

        $cacheKey   = 'pending_reg_' . $email;
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            throw ValidationException::withMessages([
                'otp' => ['Verification data not found or expired. Please register again.'],
            ]);
        }

        if ((string) $cachedData['otp'] !== (string) $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        // Create the actual account in DB
        if ($role === 'student') {
            $user = User::create([
                'name'              => $cachedData['name'],
                'email'             => $cachedData['email'],
                'password'          => $cachedData['password'],
                'email_verified_at' => now(),
            ]);
        } elseif ($role === 'company') {
            $user = Company::create([
                'company_name'      => $cachedData['name'],
                'email'             => $cachedData['email'],
                'password'          => $cachedData['password'],
                'email_verified_at' => now(),
            ]);
        } elseif ($role === 'admin') {
            $user = Admin::create([
                'name'              => $cachedData['name'],
                'email'             => $cachedData['email'],
                'password'          => $cachedData['password'],
                'email_verified_at' => now(),
            ]);
        }

        Cache::forget($cacheKey);

        $token = $user->createToken('internmatch-token')->plainTextToken;

        if ($user instanceof User && $role === 'student') {
            $user->is_profile_complete = $user->isProfileComplete();
        }

        return response()->json([
            'message' => 'Account verified successfully',
            'user'    => $user,
            'role'    => $role,
            'token'   => $token,
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:student,company,admin',
        ]);

        $email      = $request->email;
        $cacheKey   = 'pending_reg_' . $email;
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            return response()->json(['message' => 'Registration session expired. Please register again.'], 404);
        }

        $otp                      = rand(100000, 999999);
        $cachedData['otp']        = $otp;
        $cachedData['expires_at'] = now()->addMinutes(10);

        Cache::put($cacheKey, $cachedData, now()->addMinutes(10));

        Mail::to($email)->send(new OtpMail(
            $otp,
            'Your New OTP Code',
            'We received a request for a new OTP code. Use the following code to verify your account:'
        ));

        return response()->json(['message' => 'OTP resent successfully.']);
    }
}
