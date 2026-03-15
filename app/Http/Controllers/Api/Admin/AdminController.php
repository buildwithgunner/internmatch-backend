<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Admin;
use App\Models\Internship;
use App\Models\Application;
use App\Models\Recruiter;
use App\Models\Report;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get all pending reports for moderation.
     */
    public function moderationReports()
    {
        $reports = Report::with(['student:id,name', 'internship:id,title,recruiter_id', 'internship.recruiter:id,name,is_verified,trust_score,is_banned'])
            ->where('is_resolved', false)
            ->latest()
            ->get();

        return response()->json(['reports' => $reports]);
    }

    /**
     * Verify a recruiter and update their trust score.
     */
    public function verifyRecruiter(Request $request, $id)
    {
        $recruiter = Recruiter::findOrFail($id);
        $recruiter->is_verified = true;
        $recruiter->updateTrustScore();

        return response()->json([
            'message' => 'Recruiter verified successfully',
            'recruiter' => $recruiter
        ]);
    }

    /**
     * Ban a recruiter and deactivate their internships.
     */
    public function banRecruiter(Request $request, $id)
    {
        $recruiter = Recruiter::findOrFail($id);
        $recruiter->is_banned = true;
        $recruiter->save();

        // Deactivate all their internships
        Internship::where('recruiter_id', $id)->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'Recruiter banned and internships deactivated',
            'recruiter' => $recruiter
        ]);
    }

    /**
     * Mark a report as resolved.
     */
    public function resolveReport(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        $report->is_resolved = true;
        $report->save();

        return response()->json(['message' => 'Report resolved successfully']);
    }

    /**
     * Verify a company.
     */
    public function verifyCompany(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->is_verified = true;
        $company->save();

        return response()->json([
            'message' => 'Company verified successfully',
            'company' => $company
        ]);
    }

    /**
     * Toggle verification status for any user type that supports it.
     */
    public function toggleVerification($type, $id)
    {
        $model = match ($type) {
            'recruiter' => Recruiter::findOrFail($id),
            'company'   => Company::findOrFail($id),
            default     => null
        };

        if (!$model) return response()->json(['message' => 'Invalid user type'], 400);

        $model->is_verified = !$model->is_verified;
        
        if ($type === 'recruiter' && $model->is_verified && method_exists($model, 'updateTrustScore')) {
            $model->updateTrustScore();
        }
        
        $model->save();

        $status = $model->is_verified ? 'verified' : 'unverified';
        return response()->json([
            'message' => "User {$status} successfully",
            'is_verified' => $model->is_verified
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Admin)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_students'       => User::count(),
            'total_recruiters'     => Recruiter::count(),
            'total_companies'      => Company::count(),
            'total_internships'    => Internship::count(),
            'total_applications'   => Application::count(),
            'active_internships'   => Internship::where('status', 'active')->count(),
            'pending_verifications' => Company::where('is_verified', false)->count(),
        ];

        return response()->json([
            'message' => 'Welcome to Admin Dashboard',
            'stats'   => $stats,
            'admin'   => $user,
        ]);
    }

    public function users()
    {
        $students   = User::select('id', 'name', 'email', 'is_banned', 'created_at')->get();
        $recruiters = Recruiter::select('id', 'name', 'email', 'company_name', 'is_verified', 'is_banned', 'created_at')->get();
        $companies  = Company::select('id', 'company_name as name', 'email', 'is_verified', 'is_banned', 'created_at')->get();

        return response()->json([
            'students'   => $students,
            'recruiters' => $recruiters,
            'companies'  => $companies,
        ]);
    }

    /**
     * View detailed user info
     */
    public function showUser($type, $id)
    {
        $model = match ($type) {
            'student'   => User::with(['profile', 'documents', 'applications.internship']),
            'recruiter' => Recruiter::with(['internships']),
            'company'   => Company::with(['internships', 'recruiters']),
            default     => null
        };

        if (!$model) return response()->json(['message' => 'Invalid user type'], 400);

        $user = $model->findOrFail($id);
        return response()->json(['user' => $user, 'type' => $type]);
    }

    /**
     * Suspend or Resume a user account
     */
    public function toggleBan($type, $id)
    {
        $model = match ($type) {
            'student'   => User::findOrFail($id),
            'recruiter' => Recruiter::findOrFail($id),
            'company'   => Company::findOrFail($id),
            default     => null
        };

        if (!$model) return response()->json(['message' => 'Invalid user type'], 400);

        $model->is_banned = !$model->is_banned;
        $model->save();

        $action = $model->is_banned ? 'suspended' : 'activated';

        return response()->json([
            'message' => "User {$action} successfully",
            'is_banned' => $model->is_banned
        ]);
    }

    /**
     * Delete a user account
     */
    public function deleteUser($type, $id)
    {
        $model = match ($type) {
            'student'   => User::findOrFail($id),
            'recruiter' => Recruiter::findOrFail($id),
            'company'   => Company::findOrFail($id),
            default     => null
        };

        if (!$model) return response()->json(['message' => 'Invalid user type'], 400);

        $model->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function internships()
    {
        $internships = Internship::with('recruiter.company')
            ->select('id', 'title', 'recruiter_id', 'status', 'created_at')
            ->latest()
            ->get();

        return response()->json(['internships' => $internships]);
    }

    public function reports()
    {
        $totalStudents = User::count();
        $totalCompanies = Company::count();
        $totalInternships = Internship::count();
        $totalApplications = Application::count();

        // Get monthly trends for the last 6 months
        $userGrowth = [];
        $applicationTrends = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M');

            $studentsCount = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $companiesCount = Company::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $appsCount = Application::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $userGrowth[] = [
                'month' => $monthName,
                'count' => $studentsCount + $companiesCount,
            ];

            $applicationTrends[] = [
                'month' => $monthName,
                'count' => $appsCount,
            ];
        }

        return response()->json([
            'stats' => [
                'total_users'        => $totalStudents + $totalCompanies,
                'total_internships'  => $totalInternships,
                'total_applications' => $totalApplications,
            ],
            'user_growth'        => $userGrowth,
            'application_trends' => $applicationTrends,
        ]);
    }
}
