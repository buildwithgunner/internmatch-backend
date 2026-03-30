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
            'role'     => 'required|in:student,company,admin,recruiter',
        ]);

        $role = $request->role;

        $account = match ($role) {
            'student'   => \App\Models\User::withTrashed()->where('email', $request->email)->first(),
            'company'   => \App\Models\Company::withTrashed()->where('email', $request->email)->first(),
            'admin'     => \App\Models\Admin::where('email', $request->email)->first(), // Admin doesn't have SoftDeletes yet, and shouldn't usually.
            'recruiter' => \App\Models\Recruiter::withTrashed()->where('email', $request->email)->first(),
            default     => null,
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

        // Check if account is soft-deleted (deactivated)
        if (method_exists($account, 'trashed') && $account->trashed()) {
            return response()->json([
                'message'        => 'This account has been deactivated. Please contact support/admin to restore it.',
                'is_deactivated' => true
            ], 403);
        }

        // Check if account is banned
        if (isset($account->is_banned) && $account->is_banned) {
            return response()->json([
                'message' => 'Your account has been suspended. Please contact support.',
                'is_banned' => true
            ], 403);
        }

        $token = $account->createToken('internmatch-token')->plainTextToken;

        $resource = match ($role) {
            'student'   => new \App\Http\Resources\UserResource($account),
            'company'   => new \App\Http\Resources\CompanyResource($account),
            'recruiter' => new \App\Http\Resources\RecruiterResource($account),
            default     => $account,
        };

        return response()->json([
            'message' => 'Logged in successfully to InternMatch!',
            'user'    => $resource,
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

        $role = $user instanceof \App\Models\User    ? 'student' :
               ($user instanceof \App\Models\Company ? 'company' :
               ($user instanceof \App\Models\Recruiter ? 'recruiter' : 'admin'));

        $resource = match ($role) {
            'student'   => new \App\Http\Resources\UserResource($user),
            'company'   => new \App\Http\Resources\CompanyResource($user),
            'recruiter' => new \App\Http\Resources\RecruiterResource($user),
            default     => $user,
        };

        return response()->json([
            'user' => $resource,
            'role' => $role,
        ]);
    }

    /**
     * Delete self account (Soft Delete)
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        
        // Revoke current token before deleting
        $user->currentAccessToken()->delete();
        
        $user->delete();

        return response()->json([
            'message' => 'Your account has been deactivated successfully. You have been logged out.'
        ]);
    }
}
