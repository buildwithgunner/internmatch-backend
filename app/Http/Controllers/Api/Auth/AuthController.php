<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required|in:student,company,admin',
        ]);

        $role = $request->role;

        $account = match ($role) {
            'student' => \App\Models\User::where('email', $request->email)->first(),
            'company' => \App\Models\Company::where('email', $request->email)->first(),
            'admin'   => \App\Models\Admin::where('email', $request->email)->first(),
            default   => null,
        };

        if (!$account || !Hash::check($request->password, $account->password)) {
            // Check if account is pending verification in Cache
            if (Cache::has('pending_reg_' . $request->email)) {
                return response()->json([
                    'message'            => 'Your account is pending verification. Please verify your email first.',
                    'needs_verification' => true,
                    'email'              => $request->email,
                    'role'               => $role,
                ], 403);
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $account->createToken('internmatch-token')->plainTextToken;

        if ($account instanceof User && $role === 'student') {
            $account->is_profile_complete = $account->isProfileComplete();
        }

        return response()->json([
            'message' => 'Login successful',
            'user'    => $account,
            'role'    => $role,
            'token'   => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $role = $user instanceof User    ? 'student' :
               ($user instanceof Company ? 'company' : 'admin');

        if ($user instanceof User) {
            $user->is_profile_complete = $user->isProfileComplete();
        }

        return response()->json([
            'user' => $user,
            'role' => $role,
        ]);
    }
}
