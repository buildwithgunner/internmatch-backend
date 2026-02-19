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
}
