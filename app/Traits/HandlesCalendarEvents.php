<?php

namespace App\Traits;

use App\Models\Interview;
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
}
