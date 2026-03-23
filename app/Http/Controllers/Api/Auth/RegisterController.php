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
use App\Helpers\CaptchaHelper;
use App\Rules\NoEmoji;

class RegisterController extends Controller
{
    use CaptchaHelper;

    /**
     * Get a new math captcha.
     */
    public function getCaptcha()
    {
        $captcha = self::generateCaptcha();
        $key = 'captcha_' . str()->random(32);
        
        Cache::put($key, $captcha['answer'], now()->addMinutes(10));

        return response()->json([
            'question' => $captcha['question'],
            'captcha_key' => $key
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255', new NoEmoji],
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:student,company,admin,recruiter',
            'phone'    => 'nullable|string|max:20',
            // Recruiter specific fields (passed only if role === recruiter)
            'recruiter_type' => 'nullable|in:independent,company',
            'company_name'   => ['nullable', 'string', 'max:255', new NoEmoji],
            'sector'         => ['nullable', 'string', 'max:255', new NoEmoji],
            'position'       => ['nullable', 'string', 'max:255', new NoEmoji],
            'website'        => 'nullable|string|max:255',
            'referral_code'  => 'nullable|string|max:10',
            'captcha_answer' => 'required|string',
            'captcha_key'    => 'required|string',
        ]);

        // Verify Captcha
        $expected = Cache::get($request->captcha_key);
        if (!$expected || !self::verifyCaptcha($request->captcha_answer, $expected)) {
            Cache::forget($request->captcha_key); // Invalidate on failure
            throw ValidationException::withMessages([
                'captcha_answer' => ['Invalid or expired captcha answer.'],
            ]);
        }
        Cache::forget($request->captcha_key); // Use once

        $role = $request->role;

        // Check email uniqueness across all tables
        $emailTaken = User::where('email', $request->email)->exists() ||
                      Company::where('email', $request->email)->exists() ||
                      Admin::where('email', $request->email)->exists() ||
                      \App\Models\Recruiter::where('email', $request->email)->exists();

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
        // For recruiters matching an existing company, we do a loose check just logic-wise, 
        // but finding the actual ID happens in verifyAccount. We just cache these fields.
        $cacheKey = 'pending_reg_' . $request->email;
        $cachedData = [
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => $role,
            'phone'          => $request->phone,
            'otp'            => $otp,
            'expires_at'     => now()->addMinutes(10),
            'recruiter_type' => $request->recruiter_type ?? null,
            'company_name'   => $request->company_name ?? null,
            'sector'         => $request->sector ?? null,
            'position'       => $request->position ?? null,
            'website'        => $request->website ?? null,
            'referral_code'  => $request->referral_code ?? null,
        ];

        Cache::put($cacheKey, $cachedData, now()->addMinutes(10));

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
            'role'  => 'required|in:student,company,admin,recruiter',
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
            // Handle referral logic
            $ambassadorId = null;
            if (!empty($cachedData['referral_code'])) {
                $ambassador = \App\Models\CampusAmbassador::where('referral_code', $cachedData['referral_code'])
                    ->where('status', 'active')
                    ->first();
                if ($ambassador) {
                    $ambassadorId = $ambassador->id;
                }
            }

            $user = User::create([
                'name'                      => $cachedData['name'],
                'email'                     => $cachedData['email'],
                'password'                  => $cachedData['password'],
                'phone'                     => $cachedData['phone'] ?? null,
                'email_verified_at'         => now(),
                'referred_by_ambassador_id' => $ambassadorId,
                'country'                   => $cachedData['country'] ?? null,
            ]);
            // Create profile
            $user->profile()->create([
                'country' => $cachedData['country'] ?? null,
            ]);
        } elseif ($role === 'recruiter') {
            // Check if it's a company recruiter and try to link the company automatically by name (case-insensitive)
            $companyId = null;
            if (isset($cachedData['recruiter_type']) && $cachedData['recruiter_type'] === 'company' && !empty($cachedData['company_name'])) {
                $company = Company::whereRaw('LOWER(company_name) = ?', [strtolower($cachedData['company_name'])])->first();
                if ($company) {
                    $companyId = $company->id;
                }
            }

            $user = \App\Models\Recruiter::create([
                'name'              => $cachedData['name'],
                'email'             => $cachedData['email'],
                'password'          => $cachedData['password'],
                'phone'             => $cachedData['phone'] ?? null,
                'company_id'        => $companyId,
                'company_name'      => $cachedData['company_name'] ?? null,
                'sector'            => $cachedData['sector'] ?? null,
                'position'          => $cachedData['position'] ?? null,
                'website'           => $cachedData['website'] ?? null,
                'country'           => $cachedData['country'] ?? null,
                // Recruiter needs admin approval to post.
                'is_verified'       => false, 
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
            'message' => 'Welcome to InternMatch!',
            'user'    => $user,
            'role'    => $role,
            'token'   => $token,
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:student,company,admin,recruiter',
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
