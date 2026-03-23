<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Get recommended internships for the student based on their profile.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $profile = $user->profile;

        if (!$profile) {
            // Fallback to latest active internships if profile is missing
            return response()->json([
                'internships' => Internship::with(['recruiter.company'])
                    ->where('status', 'active')
                    ->latest()
                    ->take(6)
                    ->get()
            ]);
        }

        // 1. Build Search Criteria from Profile
        $interests = array_filter([
            $profile->department,
            $profile->faculty,
            $profile->preferred_role,
            $profile->interests
        ]);

        if ($profile->interests) {
            $interests = array_merge($interests, explode(',', $profile->interests));
        }
        $interests = array_unique(array_map('trim', $interests));

        $skills = $profile->skills ? explode(',', $profile->skills) : [];
        $skills = array_map('trim', $skills);

        // 2. Fetch Relevant Internships using an OR approach for maximum discovery
        $recommendations = Internship::with(['recruiter.company'])
            ->where('status', 'active')
            ->where(function ($query) use ($interests, $skills, $profile) {
                // Match by Category (Priority) and its Umbrella Group
                if (!empty($interests)) {
                    $allInterests = [];
                    foreach ($interests as $interest) {
                        $allInterests = array_merge($allInterests, \App\Models\Internship::getRelatedFields($interest));
                    }
                    $allInterests = array_unique($allInterests);
                    
                    $query->whereIn('category', $allInterests);

                    // Cross-match with Umbrellas in other fields
                    foreach ($interests as $interest) {
                        $umbrella = \App\Models\Internship::getUmbrellaFor($interest);
                        if ($umbrella) {
                            $query->orWhere('category', 'LIKE', "%{$umbrella}%")
                                  ->orWhere('title', 'LIKE', "%{$umbrella}%")
                                  ->orWhere('target_faculty', 'LIKE', "%{$umbrella}%");
                        }
                    }
                }

                // Match by Keywords in Title/Description/Category
                foreach ($interests as $interest) {
                    $query->orWhere('title', 'LIKE', "%{$interest}%")
                          ->orWhere('description', 'LIKE', "%{$interest}%")
                          ->orWhere('category', 'LIKE', "%{$interest}%");
                }

                foreach ($skills as $skill) {
                    $query->orWhere('title', 'LIKE', "%{$skill}%")
                          ->orWhere('description', 'LIKE', "%{$skill}%")
                          ->orWhere('category', 'LIKE', "%{$skill}%");
                }

                // Match by targeted Faculty/Department
                if ($profile->faculty) {
                    $query->orWhere('target_faculty', 'LIKE', "%{$profile->faculty}%");
                    
                    // Add umbrella match for faculty
                    $umbrella = \App\Models\Internship::getUmbrellaFor($profile->faculty);
                    if ($umbrella) {
                         $query->orWhere('category', 'LIKE', "%{$umbrella}%")
                               ->orWhere('target_faculty', 'LIKE', "%{$umbrella}%");
                    }
                }
                if ($profile->department) {
                    $query->orWhere('target_department', 'LIKE', "%{$profile->department}%");
                }
            })
            ->latest()
            ->take(15)
            ->get();

        // 3. Fallback/Augment if few strict recommendations found
        if ($recommendations->count() < 6) {
            $extra = Internship::with(['recruiter.company'])
                ->where('status', 'active')
                ->whereNotIn('id', $recommendations->pluck('id'))
                ->latest()
                ->take(6 - $recommendations->count())
                ->get();
            
            $recommendations = $recommendations->concat($extra);
        }

        // 4. Formatting output with application/save status (consistent with Internship management)
        $formatted = $recommendations->unique('id')->map(function ($internship) use ($user) {
            $application = $internship->applications()
                ->where('student_id', $user->id)
                ->where('status', '!=', 'rejected')
                ->first();

            $internship->has_applied        = (bool) $application;
            $internship->application_id     = $application?->id;
            $internship->application_status = $application?->status;

            $internship->is_saved = \App\Models\SavedInternship::where('user_id', $user->id)
                ->where('internship_id', $internship->id)
                ->exists();

            // Inject Company details for frontend compatibility
            if ($internship->recruiter && $internship->recruiter->company) {
                $internship->company = $internship->recruiter->company;
                if ($internship->company->logo_path) {
                     $internship->company->logo_url = asset('storage/' . $internship->company->logo_path);
                }
            } else if ($internship->recruiter && $internship->recruiter->company_name) {
                $internship->company = [
                    'company_name' => $internship->recruiter->company_name
                ];
            }

            return $internship;
        });

        return response()->json(['internships' => $formatted]);
    }
}
