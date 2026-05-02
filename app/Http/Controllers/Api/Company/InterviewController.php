<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\Company;
use App\Models\Application;
use App\Notifications\InterviewScheduled;
use App\Traits\ChecksOwnership;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    use ChecksOwnership;
    // ... (index method)

    // Company: schedule a new interview
    public function store(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Company)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'student_id'     => 'required|exists:users,id',
            'application_id' => 'nullable|exists:applications,id',
            'scheduled_at'   => 'required|date',
            'type'           => 'nullable|string',
            'notes'          => 'nullable|string',
            'meeting_link'   => 'nullable|string',
        ]);

        $interview = $user->interviews()->create([
            'student_id'     => $validated['student_id'],
            'application_id' => $validated['application_id'] ?? null,
            'scheduled_at'   => $validated['scheduled_at'],
            'type'           => $validated['type'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'meeting_link'   => $validated['meeting_link'] ?? null,
            'status'         => 'scheduled',
        ]);

        // Trigger Notification to Student
        if ($interview->student) {
            $interview->student->notify(new InterviewScheduled($interview));
        }

        return response()->json([
            'message'   => 'Interview scheduled',
            'interview' => $interview,
        ], 201);
    }

    // Company: update an interview
    public function update(Request $request, Interview $interview)
    {
        $user = $request->user();

        if ($guard = $this->guardIs($user, Company::class)) return $guard;
        if ($check = $this->assertOwnership($interview, fn($i) => $i->company_id === $user->id)) return $check;

        $validated = $request->validate([
            'scheduled_at'        => 'sometimes|date',
            'status'              => 'sometimes|string',
            'type'                => 'nullable|string',
            'notes'               => 'nullable|string',
            'meeting_link'        => 'nullable|string',
            'cancellation_reason' => 'nullable|string',
        ]);

        $interview->update($validated);

        return response()->json([
            'message'   => 'Interview updated',
            'interview' => $interview,
        ]);
    }

    // Company: cancel an interview
    public function cancel(Request $request, Interview $interview)
    {
        $user = $request->user();

        if ($guard = $this->guardIs($user, Company::class)) return $guard;
        if ($check = $this->assertOwnership($interview, fn($i) => $i->company_id === $user->id)) return $check;

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $interview->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $validated['reason'],
        ]);

        return response()->json([
            'message'   => 'Interview cancelled',
            'interview' => $interview,
        ]);
    }

    // Company: delete an interview
    public function destroy(Request $request, Interview $interview)
    {
        $user = $request->user();

        if ($guard = $this->guardIs($user, Company::class)) return $guard;
        if ($check = $this->assertOwnership($interview, fn($i) => $i->company_id === $user->id)) return $check;

        $interview->delete();

        return response()->json(['message' => 'Interview deleted']);
    }
}
