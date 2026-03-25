<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Application;
use App\Rules\NoEmoji;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Company\UpdateCompanyProfileRequest;
use App\Http\Resources\CompanyResource;

class CompanyController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'company' => new CompanyResource($user)
        ]);
    }

    public function updateProfile(UpdateCompanyProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully',
            'company' => new CompanyResource($user),
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

    /**
     * Delete self account (Soft Delete)
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Revoke current token
        $user->currentAccessToken()->delete();

        $user->delete();

        return response()->json([
            'message' => 'Your company account has been deactivated successfully.'
        ]);
    }
}
