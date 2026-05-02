<?php

namespace App\Notifications;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternshipReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $application;
    protected $recipientType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Application $application, string $recipientType)
    {
        $this->application = $application;
        $this->recipientType = $recipientType; // 'student' or 'recruiter'
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $companyName = $this->application->internship->company->name ?? 'A company';
        $roleTitle = $this->application->internship->title;
        $startDate = $this->application->start_date->format('M d, Y');

        $message = (new MailMessage)
            ->subject('Reminder: Upcoming Internship at ' . $companyName);

        if ($this->recipientType === 'student') {
            $message->line("Just a friendly reminder that your internship for **{$roleTitle}** at {$companyName} is starting soon on {$startDate}.");
            $message->line("We hope you're excited to begin!");
        } else {
            $studentName = $this->application->student->name ?? 'A student';
            $message->line("Just a friendly reminder that your upcoming intern, **{$studentName}**, is starting their {$roleTitle} internship soon on {$startDate}.");
        }

        return $message
            ->action('View Details', url('/dashboard'))
            ->line('Thank you for using InternMatch!');
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
            'message' => "Reminder: The internship role {$this->application->internship->title} starts on {$this->application->start_date->format('M d, Y')}.",
        ];
    }
}
