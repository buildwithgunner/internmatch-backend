<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Recruiter;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    /**
     * Search for students based on filters
     */
    public function searchStudents(Request $request)
    {
        $user = $request->user();

        // Allow Recruiters and Companies (and Admins if needed)
        if (!($user instanceof Recruiter) && !($user instanceof Company) && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::role('student')->with(['profile', 'documents']);

        // Filter by keyword (Name, Bio, Skills)
        if ($request->has('q')) {
            $q = $request->q;
            $query->where(function($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhereHas('profile', function($pq) use ($q) {
                      $pq->where('bio', 'like', "%$q%")
                         ->orWhere('skills', 'like', "%$q%");
                  });
            });
        }

        // Filter by University
        if ($request->has('university')) {
            $univ = $request->university;
            $query->whereHas('profile', function($pq) use ($univ) {
                $pq->where('university', 'like', "%$univ%");
            });
        }

        // Filter by Skills (Comma separated)
        if ($request->has('skills')) {
            $skills = explode(',', $request->skills);
            $query->whereHas('profile', function($pq) use ($skills) {
                foreach($skills as $skill) {
                    $pq->where('skills', 'like', "%" . trim($skill) . "%");
                }
            });
        }

        // Filter by Graduation Year
        if ($request->has('graduation_year')) {
            $year = $request->graduation_year;
            $query->whereHas('profile', function($pq) use ($year) {
                $pq->where('graduation_year', $year);
            });
        }

        $students = $query->latest()->paginate(20);

        // Add is_saved to each student if user is a recruiter
        if ($user instanceof Recruiter) {
            $students->getCollection()->transform(function ($student) use ($user) {
                $student->is_saved = \App\Models\SavedCandidate::where('recruiter_id', $user->id)
                    ->where('student_id', $student->id)
                    ->exists();
                return $student;
            });
        }

        return response()->json($students);
    }
}
