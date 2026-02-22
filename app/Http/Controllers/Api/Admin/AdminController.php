<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Admin;
use App\Models\Internship;
use App\Models\Application;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Admin)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_students'       => User::count(),
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
        $students  = User::select('id', 'name', 'email', 'created_at')->get();
        $companies = Company::select('id', 'company_name as name', 'email', 'is_verified', 'created_at')->get();

        return response()->json([
            'students'  => $students,
            'companies' => $companies,
        ]);
    }

    public function internships()
    {
        $internships = Internship::with('company:id,company_name')
            ->select('id', 'title', 'company_id', 'status', 'created_at')
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
