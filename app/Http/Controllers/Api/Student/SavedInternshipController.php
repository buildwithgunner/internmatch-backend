<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\SavedInternship;
use App\Models\Internship;
use Illuminate\Http\Request;

class SavedInternshipController extends Controller
{
    public function index(Request $request)
    {
        $saved = SavedInternship::where('user_id', $request->user()->id)
            ->with(['internship.company', 'internship.recruiter'])
            ->latest()
            ->get()
            ->pluck('internship');

        return response()->json([
            'saved_internships' => $saved
        ]);
    }

    public function toggle(Request $request, $id)
    {
        $user = $request->user();
        $internship = Internship::findOrFail($id);

        $existing = SavedInternship::where('user_id', $user->id)
            ->where('internship_id', $internship->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'message' => 'Internship removed from saved list',
                'is_saved' => false
            ]);
        }

        SavedInternship::create([
            'user_id' => $user->id,
            'internship_id' => $internship->id
        ]);

        return response()->json([
            'message' => 'Internship saved successfully',
            'is_saved' => true
        ], 201);
    }
}
