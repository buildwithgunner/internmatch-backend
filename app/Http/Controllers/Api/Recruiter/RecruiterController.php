<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Recruiter;
use App\Rules\NoEmoji;
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
            'name'     => ['sometimes', 'string', 'max:255', new NoEmoji],
            'phone'    => 'nullable|string|max:20',
            'sector'   => ['nullable', 'string', 'max:255', new NoEmoji],
            'position' => ['nullable', 'string', 'max:255', new NoEmoji],
            'bio'      => ['nullable', 'string', new NoEmoji],
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
     * Upload tangible document for verification
     */
    public function uploadDocument(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file     = $request->file('document');
        $filename = \Illuminate\Support\Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('documents/recruiters', $filename, 'public');

        if ($user->tangible_document) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->tangible_document);
        }

        $user->update(['tangible_document' => $path]);
        $user->updateTrustScore();

        return response()->json([
            'message'   => 'Document uploaded successfully',
            'recruiter' => $user->fresh()->load('company'),
            'url'       => asset('storage/' . $path),
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

    /**
     * Delete self account (Soft Delete)
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Revoke current token
        $user->currentAccessToken()->delete();

        $user->delete();

        return response()->json([
            'message' => 'Your recruiter account has been deactivated successfully.'
        ]);
    }
}
