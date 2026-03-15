<?php

namespace App\Notifications;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $interview;

    /**
     * Create a new notification instance.
     */
    public function __construct(Interview $interview)
    {
        $this->interview = $interview;
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
        $companyName = $this->interview->company->name ?? 'A company';
        $scheduledAt = $this->interview->scheduled_at->format('M d, Y H:i');

        return (new MailMessage)
                    ->subject('Interview Scheduled with ' . $companyName)
                    ->line('An interview has been scheduled for your internship application.')
                    ->line('Company: ' . $companyName)
                    ->line('Time: ' . $scheduledAt)
                    ->line('Type: ' . ($this->interview->type ?? 'Not specified'))
                    ->action('View Interview Details', url('/interviews'))
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
            'interview_id'   => $this->interview->id,
            'company_name'   => $this->interview->company->name ?? null,
            'scheduled_at'   => $this->interview->scheduled_at,
            'type'           => $this->interview->type,
            'meeting_link'   => $this->interview->meeting_link,
            'message'        => 'A new interview has been scheduled with ' . ($this->interview->company->name ?? 'a company'),
        ];
    }
}
