<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\Company;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    // Student: view their own interviews
    public function studentIndex(Request $request)
    {
        $user = $request->user();

        if ($user instanceof Company) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $interviews = Interview::where('student_id', $user->id)
            ->with(['company', 'application.internship'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json(['interviews' => $interviews]);
    }
}
