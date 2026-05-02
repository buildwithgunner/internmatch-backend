<?php

namespace App\Traits;

use App\Models\Interview;
use App\Models\Application;
use Carbon\Carbon;

trait HandlesCalendarEvents
{
    /**
     * Generate a Google Calendar link for the interview.
     */
    public function generateGoogleCalendarUrl(Interview $interview): string
    {
        $title = urlencode("Interview with " . ($interview->company->name ?? 'InternMatch Partner'));
        $details = urlencode("Meeting Link: " . ($interview->meeting_link ?? 'Not provided') . "\n\nNotes: " . ($interview->notes ?? 'N/A'));
        
        $start = $interview->scheduled_at->format('Ymd\THis\Z');
        $end = $interview->scheduled_at->addHour()->format('Ymd\THis\Z');
        
        return "https://www.google.com/calendar/render?action=TEMPLATE&text={$title}&details={$details}&dates={$start}/{$end}";
    }

    /**
     * Generate iCalendar (.ics) content for the interview.
     */
    public function generateIcsContent(Interview $interview): string
    {
        $companyName = $interview->company->name ?? 'InternMatch Partner';
        $summary = "Interview with {$companyName}";
        $description = "Meeting Link: " . ($interview->meeting_link ?? 'Not provided') . "\\n\\nNotes: " . ($interview->notes ?? 'N/A');
        
        $start = $interview->scheduled_at->format('Ymd\THis\Z');
        $end = $interview->scheduled_at->copy()->addHour()->format('Ymd\THis\Z');
        $now = now()->format('Ymd\THis\Z');
        $uid = "interview-" . $interview->id . "@internmatch.com";

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//InternMatch//Interview Scheduler//EN',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            "LOCATION:" . ($interview->meeting_link ?? 'Online'),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }

    /**
     * Generate a Google Calendar link for a concluded internship (application).
     */
    public function generateApplicationGoogleCalendarUrl(Application $application): string
    {
        $companyName = $application->internship->company->name ?? 'InternMatch Partner';
        $title = urlencode("Internship at {$companyName} - " . $application->internship->title);
        $details = urlencode("Internship role: " . $application->internship->title . "\nType: " . ($application->internship->type ?? 'Not specified'));
        
        // Default to today if dates are somehow missing
        $start = ($application->start_date ?? now())->format('Ymd\THis\Z');
        $end = ($application->end_date ?? now()->addMonth())->format('Ymd\THis\Z');
        
        return "https://www.google.com/calendar/render?action=TEMPLATE&text={$title}&details={$details}&dates={$start}/{$end}";
    }

    /**
     * Generate iCalendar (.ics) content for a concluded internship.
     */
    public function generateApplicationIcsContent(Application $application): string
    {
        $companyName = $application->internship->company->name ?? 'InternMatch Partner';
        $summary = "Internship at {$companyName}";
        $description = "Internship role: " . $application->internship->title . "\\nType: " . ($application->internship->type ?? 'Not specified');
        
        $start = ($application->start_date ?? now())->format('Ymd\THis\Z');
        $end = ($application->end_date ?? now()->addMonth())->format('Ymd\THis\Z');
        $now = now()->format('Ymd\THis\Z');
        $uid = "internship-" . $application->id . "@internmatch.com";

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//InternMatch//Internship Placement//EN',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            "LOCATION:" . ($application->internship->location ?? 'Online'),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }
}
