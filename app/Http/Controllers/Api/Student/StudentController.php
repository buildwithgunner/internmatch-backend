<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->load(['profile', 'documents']);
        $user->is_profile_complete = $user->isProfileComplete();
        $user->profile_strength = $user->calculateProfileStrength();

        return response()->json(['student' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            // Basic User Info
            'name'            => 'sometimes|string|max:255',
            'phone'           => 'nullable|string|max:20',
            
            // Academic Info
            'university'      => 'nullable|string|max:255',
            'faculty'         => 'nullable|string|max:255',
            'department'      => 'nullable|string|max:255',
            'level'           => 'nullable|string|max:50',
            'graduation_year' => 'nullable|integer',
            
            // Profile Details
            'bio'             => 'nullable|string',
            'skills'          => 'nullable|string',
            
            // Location
            'country'         => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            
            // Links
            'portfolio_url'   => 'nullable|url',
            'github_url'      => 'nullable|url',
            'linkedin_url'    => 'nullable|url',
            'website_url'     => 'nullable|url',

            // Internship Preferences
            'preferred_role'  => 'nullable|string|max:255',
            'internship_type' => 'nullable|in:Remote,Onsite,Hybrid',
            'availability'    => 'nullable|in:Full-time,Part-time',
        ]);

        // Update User info
        $user->update($request->only(['name', 'phone']));

        // Update or Create Profile
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->except(['name', 'phone'])
        );

        $user->load(['profile', 'documents']);
        $user->is_profile_complete = $user->isProfileComplete();
        $profileStrength = $user->calculateProfileStrength();
        $user->profile_strength = $profileStrength;

        // Referral Reward Logic
        if ($profileStrength['percentage'] === 100 && $user->referred_by_ambassador_id && !$user->referral_rewarded) {
            $ambassador = \App\Models\CampusAmbassador::find($user->referred_by_ambassador_id);
            if ($ambassador) {
                $ambassador->increment('points', 50);
                $user->update(['referral_rewarded' => true]);
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'student' => $user,
        ]);
    }

    // Company views a specific student profile (for applicant review)
    public function show(Request $request, $id)
    {
        $student = User::with(['profile', 'documents'])->findOrFail($id);

        if ($student->role !== 'student') {
            return response()->json(['message' => 'Not a student'], 404);
        }

        return response()->json(['student' => $student]);
    }
}
