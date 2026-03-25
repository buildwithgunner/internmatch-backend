<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NoEmoji;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Http\Resources\UserResource;

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

        return response()->json([
            'student' => new UserResource($user)
        ]);
    }

    public function updateProfile(UpdateStudentProfileRequest $request)
    {
        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
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
                'student' => new UserResource($user),
            ]);
        });
    }

    // Company views a specific student profile (for applicant review)
    public function show(Request $request, $id)
    {
        $student = User::with(['profile', 'documents'])->findOrFail($id);

        if ($student->role !== 'student') {
            return response()->json(['message' => 'Not a student'], 404);
        }

        return response()->json([
            'student' => new UserResource($student)
        ]);
    }
}
