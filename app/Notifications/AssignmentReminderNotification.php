<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AssignmentReminderNotification extends Notification
{
    use Queueable;

    public $assignmentDetails;

    /**
     * Create a new notification instance.
     */
    public function __construct($assignmentDetails)
    {
        $this->assignmentDetails = $assignmentDetails;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assignment_reminder',
            'title' => 'Rappel de devoir / rapport',
            'message' => 'N\'oubliez pas votre devoir/rapport pour la classe ' . $this->assignmentDetails['class_name'] . '.',
            'action_url' => $this->assignmentDetails['action_url'] ?? '#',
            'assignment_id' => $this->assignmentDetails['assignment_id'] ?? null,
        ];
    }
}
