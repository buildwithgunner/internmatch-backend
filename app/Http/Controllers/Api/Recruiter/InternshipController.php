<?php

namespace App\Http\Controllers\Api\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Recruiter;
use App\Rules\NoEmoji;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternshipController extends Controller
{
    // Public: List all active internships (students browse)
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        $internships = Internship::with(['recruiter.company'])
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(function ($internship) use ($user) {
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

                // Append company info directly onto internship if it exists to keep frontend backwards compatible
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

        return response()->json(['internships' => $internships]);
    }

    // Public: Show single internship
    public function show(Internship $internship)
    {
        $internship->load(['recruiter.company']);

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

        return response()->json(['internship' => $internship]);
    }

    // Recruiter creates new internship
    public function store(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Only recruiters can post internships'], 403);
        }

        if (!$user->is_verified) {
            return response()->json(['message' => 'Your account must be verified by an admin before you can post internships.'], 403);
        }

        $request->validate([
            'title'       => ['required', 'string', 'max:255', new NoEmoji],
            'category'    => 'nullable|string|max:255',
            'target_faculty' => 'nullable|string|max:255',
            'target_department' => 'nullable|string|max:255',
            'description' => 'required|string',
            'location'    => ['required', 'string', new NoEmoji],
            'type'        => 'required|in:Remote,Onsite,Hybrid',
            'duration'    => 'nullable|string',
            'stipend'     => 'nullable|string',
            'paid'        => 'boolean',
            'deadline'    => 'nullable|date',
        ]);

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
            'internship' => $internship,
        ], 201);
    }

    public function recruiterIndex(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $internships = $user->internships()
                ->withCount('applications')
                ->latest()
                ->get();

            return response()->json(['postings' => $internships]);
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
            'internship' => $internship,
        ]);
    }

    // Recruiter deletes their own internship
    public function destroy(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Recruiter) || $user->id !== $internship->recruiter_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $internship->delete();

        return response()->json(['message' => 'Internship deleted']);
    }
}
