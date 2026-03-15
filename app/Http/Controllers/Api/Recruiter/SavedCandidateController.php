<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\SavedCandidate;
use App\Models\Recruiter;
use Illuminate\Http\Request;

class SavedCandidateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $saved = SavedCandidate::where('recruiter_id', $user->id)
            ->with(['student.profile', 'student.documents'])
            ->latest()
            ->get();

        return response()->json(['saved_candidates' => $saved]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'student_id' => 'required|exists:users,id',
            'notes'      => 'nullable|string',
        ]);

        $saved = SavedCandidate::updateOrCreate(
            [
                'recruiter_id' => $user->id,
                'student_id'   => $request->student_id,
            ],
            [
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'message' => 'Candidate saved successfully',
            'saved'   => $saved
        ]);
    }

    public function destroy(Request $request, $studentId)
    {
        $user = $request->user();
        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        SavedCandidate::where('recruiter_id', $user->id)
            ->where('student_id', $studentId)
            ->delete();

        return response()->json(['message' => 'Candidate removed from saved list']);
    }
}
