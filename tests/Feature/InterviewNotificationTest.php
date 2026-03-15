<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Application;
use App\Models\Interview;
use App\Notifications\InterviewScheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InterviewNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_sent_when_interview_is_scheduled()
    {
        Notification::fake();

        // Create a company and a student
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::factory()->create(['user_id' => $companyUser->id]);
        
        $student = User::factory()->create(['role' => 'student']);
        
        // Create an internship and application
        $internship = Internship::factory()->create(['company_id' => $company->id]);
        $application = Application::factory()->create([
            'internship_id' => $internship->id,
            'student_id' => $student->id
        ]);

        // Act: Schedule an interview
        $response = $this->actingAs($companyUser)
            ->postJson('/api/v1/company/interviews', [
                'student_id'     => $student->id,
                'application_id' => $application->id,
                'scheduled_at'   => now()->addDays(2)->toDateTimeString(),
                'type'           => 'Technical',
                'notes'          => 'Prepare for coding test',
            ]);

        $response->assertStatus(201);

        // Assert: Notification was sent to the student
        Notification::assertSentTo(
            $student,
            InterviewScheduled::class,
            function ($notification, $channels) use ($student) {
                return in_array('database', $channels) && in_array('mail', $channels);
            }
        );
    }

    public function test_student_can_list_notifications()
    {
        $student = User::factory()->create(['role' => 'student']);
        
        // Mock a notification in the database
        // Note: This requires the notifications table to exist
        $interview = Interview::factory()->create(['student_id' => $student->id]);
        $student->notify(new InterviewScheduled($interview));

        $response = $this->actingAs($student)
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications' => [
                    '*' => ['id', 'type', 'data', 'read_at']
                ]
            ]);
    }
}
