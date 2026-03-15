<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampusAmbassador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampusAmbassadorController extends Controller
{
    /**
     * Get the student's ambassador status
     */
    public function status(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'student') {
            return response()->json(['message' => 'Only students can be ambassadors'], 403);
        }

        $ambassador = CampusAmbassador::where('user_id', $user->id)->first();

        return response()->json(['ambassador' => $ambassador]);
    }

    /**
     * Apply to be a campus ambassador
     */
    public function apply(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'student') {
            return response()->json(['message' => 'Only students can apply'], 403);
        }

        if (CampusAmbassador::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You have already applied'], 400);
        }

        $request->validate([
            'university' => 'required|string|max:255',
        ]);

        // Generate a unique referral code
        $referral_code = strtoupper(Str::random(8));
        while (CampusAmbassador::where('referral_code', $referral_code)->exists()) {
            $referral_code = strtoupper(Str::random(8));
        }

        $ambassador = CampusAmbassador::create([
            'user_id'       => $user->id,
            'university'    => $request->university,
            'referral_code' => $referral_code,
            'status'        => 'pending',
            'points'        => 0,
        ]);

        return response()->json([
            'message'    => 'Application submitted successfully',
            'ambassador' => $ambassador
        ]);
    }

    /**
     * Admin: List all ambassador applications
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ambassadors = CampusAmbassador::with('user')->latest()->get();
        return response()->json(['ambassadors' => $ambassadors]);
    }

    /**
     * Admin: Update ambassador status (Approve/Reject)
     */
    public function updateStatus(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,active,inactive'
        ]);

        $ambassador = CampusAmbassador::findOrFail($id);
        $ambassador->update(['status' => $request->status]);

        return response()->json([
            'message'    => 'Ambassador status updated',
            'ambassador' => $ambassador
        ]);
    }

    /**
     * Public/Student: Get leaderboard
     */
    public function leaderboard()
    {
        $leaderboard = CampusAmbassador::with('user')
            ->where('status', 'active')
            ->orderBy('points', 'desc')
            ->take(10)
            ->get();

        return response()->json(['leaderboard' => $leaderboard]);
    }

    public function universityLeaderboard()
    {
        $leaderboard = CampusAmbassador::select('university', \Illuminate\Support\Facades\DB::raw('SUM(points) as total_points'))
            ->where('status', 'active')
            ->groupBy('university')
            ->orderByDesc('total_points')
            ->limit(10)
            ->get();

        return response()->json([
            'university_leaderboard' => $leaderboard
        ]);
    }
}
