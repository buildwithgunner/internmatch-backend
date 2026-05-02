<?php

namespace App\Notifications;

use App\Models\Application;
use App\Traits\HandlesCalendarEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternshipAccepted extends Notification implements ShouldQueue
{
    use Queueable, HandlesCalendarEvents;

    protected $application;

    /**
     * Create a new notification instance.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $companyName = $this->application->internship->company->name ?? 'A company';
        $googleUrl   = $this->generateApplicationGoogleCalendarUrl($this->application);
        $icsContent  = $this->generateApplicationIcsContent($this->application);

        return (new MailMessage)
                    ->subject('Internship Accepted at ' . $companyName)
                    ->line('Great news! An internship placement has been officially concluded and accepted.')
                    ->line('Role: ' . $this->application->internship->title)
                    ->line('Company: ' . $companyName)
                    ->line('Start Date: ' . ($this->application->start_date ? $this->application->start_date->format('M d, Y') : 'TBD'))
                    ->line('End Date: ' . ($this->application->end_date ? $this->application->end_date->format('M d, Y') : 'TBD'))
                    ->action('View Full Details', url('/dashboard'))
                    ->line('---')
                    ->line('You can save this internship placement schedule to your Google Calendar:')
                    ->action('Add to Google Calendar', $googleUrl)
                    ->line('Alternatively, open the attached .ics file to add it to your calendar application.')
                    ->attachData($icsContent, 'internship.ics', [
                        'mime' => 'text/calendar',
                    ])
                    ->line('Welcome to the next step of your journey!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'internship_id'  => $this->application->internship_id,
            'company_name'   => $this->application->internship->company->name ?? null,
            'message'        => 'Internship placement for ' . $this->application->internship->title . ' has been concluded.',
        ];
    }
}
