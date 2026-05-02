<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use App\Models\Company;
use App\Models\Recruiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Student\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Notifications\InternshipAccepted;

class ApplicationController extends Controller
{
    // Student submits application
    public function store(StoreApplicationRequest $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Only students can apply'], 403);
        }

        if (!$user->isProfileComplete()) {
            return response()->json([
                'message'    => 'Please complete your profile first (Phone, Bio, Skills, and required documents: Resume, University Certificate, Passport Photo) before applying.',
                'error_code' => 'INCOMPLETE_PROFILE',
            ], 403);
        }

        // Validation is handled by StoreApplicationRequest

        return DB::transaction(function () use ($request, $user, $internship) {
            // Prevent duplicate (unless rejected)
            $existing = Application::where('student_id', $user->id)
                ->where('internship_id', $internship->id)
                ->first();

            if ($existing) {
                if ($existing->status !== 'rejected') {
                    return response()->json(['message' => 'You have already applied to this internship'], 422);
                }
                // If rejected, delete old one to allow a fresh application
                $existing->documents()->detach();
                $existing->delete();
            }

            $application = Application::create([
                'student_id'        => $user->id,
                'internship_id'     => $internship->id,
                'cover_letter_text' => $request->cover_letter_text,
                'portfolio_url'     => $request->portfolio_url,
                'status'            => 'pending',
            ]);

            if ($request->has('document_types')) {
                $types = $request->document_types;
                if (!in_array('resume', $types)) {
                    $types[] = 'resume';
                }
                $documents = $user->documents()->whereIn('type', $types)->get();
                $application->documents()->attach($documents->pluck('id'));
            } else {
                $resume = $user->documents()->where('type', 'resume')->first();
                if ($resume) {
                    $application->documents()->attach($resume->id);
                }
            }

            return response()->json([
                'message'     => 'Application submitted successfully',
                'application' => new ApplicationResource($application->load('documents')),
            ], 201);
        });
    }

    // Student views their own applications
    public function studentIndex(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $applications = $user->applications()
            ->with(['internship.recruiter.company', 'documents'])
            ->latest()
            ->get();

        return response()->json([
            'applications' => ApplicationResource::collection($applications)
        ]);
    }

    // Company views applications for their internship
    public function index(Request $request, Internship $internship)
    {
        $user = $request->user();

        if ($user instanceof Company) {
            // Check if internship belongs to any recruiter associated with this company
            $recruiterIds = $user->recruiters()->pluck('id')->toArray();
            if (!in_array($internship->recruiter_id, $recruiterIds)) return response()->json(['message' => 'Unauthorized'], 403);
        } else if ($user instanceof Recruiter) {
            if ($user->id !== $internship->recruiter_id) return response()->json(['message' => 'Unauthorized'], 403);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $applications = $internship->applications()
            ->with(['student', 'documents'])
            ->latest()
            ->get();

        return response()->json([
            'applications' => ApplicationResource::collection($applications)
        ]);
    }

    // Company updates application status
    public function updateStatus(Request $request, Application $application)
    {
        $user = $request->user();

        $application->loadMissing('internship');

        if ($user instanceof Company) {
            $recruiterIds = $user->recruiters()->pluck('id')->toArray();
            if (!in_array($application->internship->recruiter_id, $recruiterIds)) return response()->json(['message' => 'Unauthorized'], 403);
        } else if ($user instanceof Recruiter) {
            if ($user->id !== $application->internship->recruiter_id) return response()->json(['message' => 'Unauthorized'], 403);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status'     => 'required|in:pending,reviewed,interview,rejected,accepted,offered',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $application->update([
            'status'     => $request->status,
            'start_date' => $request->start_date ?? $application->start_date,
            'end_date'   => $request->end_date ?? $application->end_date,
        ]);

        if ($request->status === 'accepted') {
            // Notify Student
            if ($application->student) {
                $application->student->notify(new InternshipAccepted($application));
            }
            
            // Notify Recruiter
            if ($application->internship->recruiter) {
                $application->internship->recruiter->notify(new InternshipAccepted($application));
            }
        }

        return response()->json([
            'message'     => 'Status updated successfully',
            'application' => new ApplicationResource($application->fresh(['student', 'documents'])),
        ]);
    }

    // Student cancels their application
    public function destroy(Request $request, Application $application)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->id !== $application->student_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel an application that is no longer pending'], 400);
        }

        $application->documents()->detach();
        $application->delete();

        return response()->json(['message' => 'Application cancelled successfully']);
    }
}
