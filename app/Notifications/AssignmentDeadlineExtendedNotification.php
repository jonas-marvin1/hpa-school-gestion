<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignmentDeadlineExtendedNotification extends Notification
{
    use Queueable;

    public $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'assignment_deadline_extended',
            'title' => 'Délai de devoir prolongé',
            'message' => 'La date limite du devoir « ' . $this->details['assignment_title'] . ' » a été repoussée au ' . $this->details['new_due_date'] . '.',
            'action_url' => $this->details['action_url'] ?? '#',
            'assignment_id' => $this->details['assignment_id'] ?? null,
        ];
    }
}
