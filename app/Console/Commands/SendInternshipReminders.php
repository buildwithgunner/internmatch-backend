<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Notifications\InternshipReminder;
use Carbon\Carbon;

class SendInternshipReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-internship-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch daily reminders for concluded internships starting soon.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find applications starting exactly 3 days from now
        $targetDate = Carbon::now()->addDays(3)->toDateString();

        $upcomingApplications = Application::where('status', 'accepted')
            ->whereNotNull('start_date')
            ->whereDate('start_date', $targetDate)
            ->with(['student', 'internship.recruiter'])
            ->get();

        $count = 0;

        foreach ($upcomingApplications as $application) {
            // Notify Student
            if ($application->student) {
                $application->student->notify(new InternshipReminder($application, 'student'));
            }

            // Notify Recruiter
            if ($application->internship->recruiter) {
                $application->internship->recruiter->notify(new InternshipReminder($application, 'recruiter'));
            }

            $count++;
        }

        $this->info("Successfully sent reminders for {$count} upcoming internships starting on {$targetDate}.");
    }
}
