<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Admin;
use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:student,company,admin',
        ]);

        $user = $this->findUserByRoleAndEmail($request->role, $request->email);

        if (!$user) {
            // Return success even if user not found (security: no user enumeration)
            return response()->json(['message' => 'If your email is registered, you will receive an OTP code.']);
        }

        $otp = rand(100000, 999999);

        $user->otp            = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        Mail::to($request->email)->send(new OtpMail(
            $otp,
            'Reset Your Password',
            'Use the following OTP code to reset your password:'
        ));

        return response()->json(['message' => 'If your email is registered, you will receive an OTP code.']);
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:student,company,admin',
            'otp'   => 'required|string',
        ]);

        $user = $this->findUserByRoleAndEmail($request->role, $request->email);

        if (!$user || $user->otp !== $request->otp || now()->greaterThan($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        return response()->json(['message' => 'OTP verified successfully.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'role'                  => 'required|in:student,company,admin',
            'otp'                   => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = $this->findUserByRoleAndEmail($request->role, $request->email);

        if (!$user || $user->otp !== $request->otp || now()->greaterThan($user->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        // Assign plain-text password — the model's 'hashed' cast handles bcrypt automatically.
        // Do NOT wrap in Hash::make() here; that would double-hash and break login.
        $user->password       = $request->password;
        $user->otp            = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function findUserByRoleAndEmail(string $role, string $email)
    {
        return match ($role) {
            'student' => User::where('email', $email)->first(),
            'company' => Company::where('email', $email)->first(),
            'admin'   => Admin::where('email', $email)->first(),
            default   => null,
        };
    }
}
