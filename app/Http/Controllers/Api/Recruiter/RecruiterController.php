<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Recruiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RecruiterController extends Controller
{
    /**
     * Get recruiter profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->load('company');
        $user->trust_level = $user->trust_level; // Trigger accessor

        return response()->json(['recruiter' => $user]);
    }

    /**
     * Update recruiter profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'bio'      => 'nullable|string',
            'linkedin' => 'nullable|url',
            'website'  => 'nullable|url',
        ]);

        $user->update($validated);
        $user->updateTrustScore();

        return response()->json([
            'message'   => 'Profile updated successfully',
            'recruiter' => $user->load('company')
        ]);
    }

    /**
     * Update account settings (password)
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
