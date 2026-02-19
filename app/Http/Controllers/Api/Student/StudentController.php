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

        $user->is_profile_complete = $user->isProfileComplete();
        $user->load('documents');

        return response()->json(['student' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:20',
            'bio'     => 'nullable|string',
            'skills'  => 'nullable|string',
            'linkedin' => 'nullable|url',
        ]);

        $user->update($validated);

        $user->is_profile_complete = $user->isProfileComplete();
        $user->load('documents');

        return response()->json([
            'message' => 'Profile updated successfully',
            'student' => $user,
        ]);
    }

    // Company views a specific student profile (for applicant review)
    public function show(Request $request, $id)
    {
        $student = User::with('documents')->findOrFail($id);

        if ($student->role !== 'student') {
            return response()->json(['message' => 'Not a student'], 404);
        }

        return response()->json(['student' => $student]);
    }
}
