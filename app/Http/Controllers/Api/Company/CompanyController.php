<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['company' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'website'      => 'nullable|url',
            'description'  => 'nullable|string',
            'industry'     => 'nullable|string',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'company' => $user,
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
            Storage::disk('public')->delete($user->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');

        $user->logo_path = $path;
        $user->save();

        return response()->json([
            'message'   => 'Logo uploaded successfully',
            'logo_path' => asset('storage/' . $path),
        ]);
    }

    public function deleteLogo(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->logo_path && Storage::disk('public')->exists($user->logo_path)) {
            Storage::disk('public')->delete($user->logo_path);
        }

        $user->logo_path = null;
        $user->save();

        return response()->json(['message' => 'Logo removed successfully']);
    }

    public function dashboardStats(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activePostings = $user->internships()->where('status', 'active')->count();

        $totalApplicants = Application::whereHas('internship', function ($q) use ($user) {
            $q->where('company_id', $user->id);
        })->count();

        $upcomingInterviews = $user->interviews()
            ->where('scheduled_at', '>=', now())
            ->where('status', 'scheduled')
            ->count();

        return response()->json([
            'stats' => [
                'activePostings'  => $activePostings,
                'totalApplicants' => $totalApplicants,
                'interviews'      => $upcomingInterviews,
            ],
        ]);
    }
}
