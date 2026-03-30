<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Recruiter;
use App\Rules\NoEmoji;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Recruiter\StoreInternshipRequest;
use App\Http\Resources\InternshipResource;

class InternshipController extends Controller
{
    // Public: List all active internships (students browse)
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        $internships = Internship::with(['recruiter.company'])
            ->where('status', 'active')
            ->latest()
            ->get();

        $internships->each(function ($internship) use ($user) {
            $application = $user
                ? $internship->applications()
                    ->where('student_id', $user->id)
                    ->where('status', '!=', 'rejected')
                    ->first()
                : null;

            $internship->has_applied        = (bool) $application;
            $internship->application_id     = $application?->id;
            $internship->application_status = $application?->status;

            $internship->is_saved = $user 
                ? \App\Models\SavedInternship::where('user_id', $user->id)
                    ->where('internship_id', $internship->id)
                    ->exists()
                : false;
        });

        return response()->json([
            'internships' => InternshipResource::collection($internships)
        ]);
    }

    // Public: Show single internship
    public function show(Internship $internship)
    {
        $internship->load(['recruiter.company']);
        return response()->json([
            'internship' => new InternshipResource($internship)
        ]);
    }

    // Recruiter creates new internship
    public function store(StoreInternshipRequest $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Only recruiters can post internships'], 403);
        }

        if (!$user->is_verified) {
            return response()->json(['message' => 'Your account must be verified by an admin before you can post internships.'], 403);
        }

        // Validation is handled by StoreInternshipRequest

        return DB::transaction(function () use ($request, $user) {
            $internship = $user->internships()->create([
                'title'       => $request->title,
                'category'    => $request->category,
                'target_faculty' => $request->target_faculty,
                'target_department' => $request->target_department,
                'description' => strip_tags($request->description, '<p><br><ul><ol><li><strong><em><a>'),
                'location'    => $request->location,
                'type'        => $request->type,
                'duration'    => $request->duration,
                'stipend'     => $request->stipend,
                'paid'        => $request->paid,
                'deadline'    => $request->deadline,
                'status'      => 'active',
            ]);

            $internship->load('recruiter.company');

            return response()->json([
                'message'    => 'Internship posted successfully',
                'internship' => new InternshipResource($internship),
            ], 201);
        });
    }

    public function recruiterIndex(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $internships = $user->internships()
                ->with(['recruiter.company'])
                ->withCount('applications')
                ->latest()
                ->get();

            return InternshipResource::collection($internships);
        } catch (\Exception $e) {
            Log::error('Recruiter index error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to load internships'], 500);
        }
    }

    // Recruiter updates their own internship
    public function update(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter) || $user->id !== $internship->recruiter_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title'       => 'string|max:255',
            'category'    => 'nullable|string|max:255',
            'target_faculty' => 'nullable|string|max:255',
            'target_department' => 'nullable|string|max:255',
            'description' => 'string',
            'location'    => 'string',
            'type'        => 'in:Remote,Onsite,Hybrid',
            'duration'    => 'nullable|string',
            'stipend'     => 'nullable|string',
            'paid'        => 'boolean',
            'deadline'    => 'nullable|date',
            'status'      => 'in:active,paused,closed',
        ]);

        $internship->update($request->all());
        $internship->load('recruiter.company');

        return response()->json([
            'message'    => 'Internship updated',
            'internship' => new InternshipResource($internship),
        ]);
    }

    // Recruiter deletes their own internship
    public function destroy(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter) || $user->id !== $internship->recruiter_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($internship) {
            // Delete related applications first if not using cascading deletes
            $internship->applications()->each(function ($app) {
                $app->documents()->detach();
                $app->delete();
            });
            $internship->delete();
        });

        return response()->json(['message' => 'Internship deleted']);
    }
}
