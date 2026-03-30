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

        $query = User::where('role', 'student')->with(['profile', 'documents']);

        // --- Prioritisation Logic Based on Recruiter/Company Sector ---
        $relatedFields = [];
        if ($user instanceof Recruiter && !empty($user->sector)) {
            $relatedFields = \App\Models\Internship::getRelatedFields($user->sector);
        } elseif ($user instanceof Company) {
            // For Company, try to get the most common category from their internships
            $latestInternship = \App\Models\Internship::whereHas('recruiter', function($rq) use ($user) {
                $rq->where('company_id', $user->id);
            })->latest()->first();
            
            if ($latestInternship && !empty($latestInternship->category)) {
                $relatedFields = \App\Models\Internship::getRelatedFields($latestInternship->category);
            }
        }

        if (!empty($relatedFields)) {
            // Normalize fields for SQL (Escape and wrap in quotes)
            $escapedFields = array_map(function($f) {
                return str_replace("'", "''", $f);
            }, $relatedFields);
            
            // Build the ORDER BY CASE logic to prioritize matching students
            // We check faculty, department, preferred_role and skills for matching substrings
            $caseConditions = [];
            foreach ($escapedFields as $field) {
                $caseConditions[] = "student_profiles.faculty LIKE '%{$field}%'";
                $caseConditions[] = "student_profiles.department LIKE '%{$field}%'";
                $caseConditions[] = "student_profiles.preferred_role LIKE '%{$field}%'";
                $caseConditions[] = "student_profiles.skills LIKE '%{$field}%'";
            }

            if (!empty($caseConditions)) {
                $query->leftJoin('student_profiles', 'users.id', '=', 'student_profiles.user_id')
                      ->select('users.*'); // Avoid column collision after join

                $sqlCase = implode(' OR ', $caseConditions);
                $query->orderByRaw("CASE WHEN ({$sqlCase}) THEN 0 ELSE 1 END");
            }
        }
        // ----------------------------------------------------------------

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
        if ($user instanceof Recruiter || $user instanceof Company) {
            $students->getCollection()->transform(function ($student) use ($user) {
                $student->is_saved = \App\Models\SavedCandidate::where(
                    $user instanceof Recruiter ? 'recruiter_id' : 'company_id', 
                    $user->id
                )
                    ->where('student_id', $student->id)
                    ->exists();
                return $student;
            });
        }

        return \App\Http\Resources\UserResource::collection($students);
    }

    /**
     * Get recommended students for a specific internship
     */
    public function recommendedStudents(Request $request, \App\Models\Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter) && !($user instanceof Company) && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = User::where('role', 'student')->with(['profile', 'documents']);

        $query->whereHas('profile', function($pq) use ($internship) {
            $pq->where(function($q) use ($internship, $pq) {
                // Match by Faculty
                if ($internship->target_faculty) {
                    $q->where('faculty', 'like', "%{$internship->target_faculty}%");
                }
                
                // Match by Department
                if ($internship->target_department) {
                    $q->orWhere('department', 'like', "%{$internship->target_department}%");
                }

                // Match by Category in Interests or Skills (and its Umbrella Group)
                if ($internship->category) {
                    $cat = $internship->category;
                    $relatedFields = \App\Models\Internship::getRelatedFields($cat);
                    
                    $q->orWhere(function($sub) use ($relatedFields) {
                        foreach ($relatedFields as $field) {
                            $sub->orWhere('interests', 'like', "%{$field}%")
                                ->orWhere('skills', 'like', "%{$field}%")
                                ->orWhere('preferred_role', 'like', "%{$field}%");
                        }
                    });

                    // Add faculty match if it matches the umbrella group
                    $umbrella = \App\Models\Internship::getUmbrellaFor($cat);
                    if ($umbrella) {
                        $q->orWhere('faculty', 'like', "%{$umbrella}%");
                    }
                }
            });
        });

        $students = $query->latest()->take(15)->get();

        // Add is_saved status
        $students->transform(function ($student) use ($user) {
            $student->is_saved = \App\Models\SavedCandidate::where(
                $user instanceof Recruiter ? 'recruiter_id' : 'company_id', 
                $user->id
            )
            ->where('student_id', $student->id)
            ->exists();
            return $student;
        });

        return \App\Http\Resources\UserResource::collection($students);
    }
}
