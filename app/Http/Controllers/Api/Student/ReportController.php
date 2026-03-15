<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Internship $internship)
    {
        $user = $request->user();

        // Optional: Ensure only students can report
        if ($user->role !== 'student') {
            return response()->json(['message' => 'Only students can report internships'], 403);
        }

        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        // Check if student has already reported this internship
        $existingReport = Report::where('internship_id', $internship->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existingReport) {
            return response()->json(['message' => 'You have already reported this internship.'], 400);
        }

        $report = Report::create([
            'internship_id' => $internship->id,
            'student_id'    => $user->id,
            'reason'        => $request->reason,
        ]);

        // Get the associated recruiter and update their score
        $recruiter = $internship->recruiter;
        if ($recruiter) {
            $recruiter->increment('reports_count');
            $recruiter->updateTrustScore();
        }

        return response()->json([
            'message' => 'Report submitted successfully. Thank you for keeping our community safe.',
            'report' => $report,
        ], 201);
    }
}
