<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Company;
use Illuminate\Http\Request;

class InternshipController extends Controller
{
    // Public: List all active internships (students browse)
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        $internships = Internship::with('company')
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

                if ($internship->company && $internship->company->logo_path) {
                    $internship->company->logo_url = asset('storage/' . $internship->company->logo_path);
                }

                return $internship;
            });

        return response()->json(['internships' => $internships]);
    }

    // Public: Show single internship
    public function show(Internship $internship)
    {
        $internship->load('company');

        if ($internship->company && $internship->company->logo_path) {
            $internship->company->logo_url = asset('storage/' . $internship->company->logo_path);
        }

        return response()->json(['internship' => $internship]);
    }

    // Company creates new internship
    public function store(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Only companies can post internships'], 403);
        }

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string',
            'type'        => 'required|in:Remote,Onsite,Hybrid',
            'duration'    => 'nullable|string',
            'stipend'     => 'nullable|string',
            'paid'        => 'boolean',
            'deadline'    => 'nullable|date',
        ]);

        $internship = $user->internships()->create([
            'title'       => $request->title,
            'description' => $request->description,
            'location'    => $request->location,
            'type'        => $request->type,
            'duration'    => $request->duration,
            'stipend'     => $request->stipend,
            'paid'        => $request->paid,
            'deadline'    => $request->deadline,
            'status'      => 'active',
        ]);

        $internship->load('company');

        if ($internship->company && $internship->company->logo_path) {
            $internship->company->logo_url = asset('storage/' . $internship->company->logo_path);
        }

        return response()->json([
            'message'    => 'Internship posted successfully',
            'internship' => $internship,
        ], 201);
    }

    // Company lists their own internships
    public function companyIndex(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $internships = $user->internships()
            ->withCount('applications')
            ->latest()
            ->get()
            ->map(function ($internship) {
                if ($internship->company && $internship->company->logo_path) {
                    $internship->company->logo_url = asset('storage/' . $internship->company->logo_path);
                }
                return $internship;
            });

        return response()->json(['postings' => $internships]);
    }

    // Company updates their own internship
    public function update(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Company) || $user->id !== $internship->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title'       => 'string|max:255',
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
        $internship->load('company');

        if ($internship->company && $internship->company->logo_path) {
            $internship->company->logo_url = asset('storage/' . $internship->company->logo_path);
        }

        return response()->json([
            'message'    => 'Internship updated',
            'internship' => $internship,
        ]);
    }

    // Company deletes their own internship
    public function destroy(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Company) || $user->id !== $internship->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $internship->delete();

        return response()->json(['message' => 'Internship deleted']);
    }
}
