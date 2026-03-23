<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Get statistics and recommendations for the Recruiter Dashboard.
     */
    public function index(Request $request)
    {
        $recruiterId = $request->user()->id;

        try {
            // Total unique students who've applied to this recruiter's internships
            $totalTalent = Application::whereHas('internship', function ($q) use ($recruiterId) {
                $q->where('recruiter_id', $recruiterId);
            })->distinct('student_id')->count('student_id');

            // Active internship postings
            $activeSearches = Internship::where('recruiter_id', $recruiterId)
                ->where('status', 'active')
                ->count();

            // Interviews scheduled for this recruiter's internships
            $interviews = Interview::whereHas('application.internship', function ($q) use ($recruiterId) {
                $q->where('recruiter_id', $recruiterId);
            })->where('status', 'scheduled')->count();

            // Active internship categories for this recruiter
            $postedCategories = Internship::where('recruiter_id', $recruiterId)
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->toArray();

            // Top Recommendations
            $recommendedStudents = User::where('role', 'student')
                ->whereHas('profile')
                ->whereHas('profile', function($q) use ($postedCategories) {
                    if (!empty($postedCategories)) {
                        $q->whereIn('department', $postedCategories)
                          ->orWhereIn('preferred_role', $postedCategories);
                        
                        foreach ($postedCategories as $cat) {
                            $q->orWhere('skills', 'LIKE', "%{$cat}%");
                        }
                    }
                })
                ->with('profile')
                ->latest()
                ->take(5)
                ->get();

            // Fallback: recent applicants
            if ($recommendedStudents->count() < 3) {
                $recentApplicants = User::whereHas('applications', function($q) use ($recruiterId) {
                    $q->whereHas('internship', function ($q2) use ($recruiterId) {
                        $q2->where('recruiter_id', $recruiterId);
                    });
                })
                ->where('role', 'student')
                ->whereNotIn('id', $recommendedStudents->pluck('id'))
                ->with('profile')
                ->latest()
                ->take(3)
                ->get();
                
                $recommendedStudents = $recommendedStudents->concat($recentApplicants);
            }

            if ($recommendedStudents->count() < 3) {
                $randomVetted = User::where('role', 'student')
                    ->whereHas('profile')
                    ->whereNotIn('id', $recommendedStudents->pluck('id'))
                    ->with('profile')
                    ->inRandomOrder()
                    ->take(3)
                    ->get();
                
                $recommendedStudents = $recommendedStudents->concat($randomVetted);
            }

            $recommendations = $recommendedStudents->unique('id')->take(5)->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'role_title' => $student->profile->preferred_role ?? $student->profile->department ?? 'Student Talent',
                    'location' => $student->profile ? ($student->profile->city . ', ' . $student->profile->state) : 'Remote',
                    'avatar' => $student->profile->avatar ?? null,
                ];
            });

            return response()->json([
                'stats' => [
                    'total_talent' => $totalTalent,
                    'active_searches' => $activeSearches,
                    'interviews' => $interviews,
                ],
                'recommendations' => $recommendations
            ]);
        } catch (\Exception $e) {
            Log::error('Recruiter dashboard error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load dashboard data'], 500);
        }
    }

}
