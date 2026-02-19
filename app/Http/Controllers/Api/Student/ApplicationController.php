<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Internship;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    // Student submits application
    public function store(Request $request, Internship $internship)
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

        $request->validate([
            'cover_letter_text' => 'nullable|string',
            'portfolio_url'     => 'nullable|url',
            'document_types'    => 'sometimes|array',
            'document_types.*'  => 'in:resume,cover_letter,student_id,transcript,primary_certificate,secondary_certificate,university_certificate,certificate,recommendation_letter,passport_photo',
        ]);

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
            'application' => $application->load('documents'),
        ], 201);
    }

    // Student views their own applications
    public function studentIndex(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof User) || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $applications = $user->applications()
            ->with(['internship.company'])
            ->latest()
            ->get();

        return response()->json(['applications' => $applications]);
    }

    // Company views applications for their internship
    public function index(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (!($user instanceof Company) || $user->id !== $internship->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $applications = $internship->applications()
            ->with(['student', 'documents'])
            ->latest()
            ->get();

        return response()->json(['applications' => $applications]);
    }

    // Company updates application status
    public function updateStatus(Request $request, Application $application)
    {
        $user = $request->user();

        $application->loadMissing('internship');

        if (!($user instanceof Company) || $user->id !== $application->internship->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,reviewed,interview,rejected,accepted,offered',
        ]);

        $application->update(['status' => $request->status]);

        return response()->json([
            'message'     => 'Status updated successfully',
            'application' => $application->fresh(['student', 'documents']),
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
