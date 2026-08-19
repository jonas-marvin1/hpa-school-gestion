<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Previent le formateur qu'un devoir vient d'etre rendu et attend correction.
 *
 * Le pendant existait deja cote apprenant (rappel de devoir a rendre) ; il
 * manquait le retour dans l'autre sens, sans lequel le formateur devait
 * verifier manuellement si de nouveaux rendus etaient arrives.
 */
class SubmissionReceivedNotification extends Notification
{
    public function __construct(private array $details) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'submission_received',
            'title'         => 'Devoir rendu à corriger',
            'message'       => $this->details['student_name']
                             . ' a rendu « ' . $this->details['assignment_title'] . ' »'
                             . ' (' . $this->details['class_name'] . ').'
                             . ' ' . $this->details['pending_count'] . ' rendu'
                             . ($this->details['pending_count'] > 1 ? 's' : '')
                             . ' en attente de correction sur ce devoir.',
            'assignment_id' => $this->details['assignment_id'],
            'submission_id' => $this->details['submission_id'],
            'action_url'    => $this->details['action_url'] ?? null,
        ];
    }
}
