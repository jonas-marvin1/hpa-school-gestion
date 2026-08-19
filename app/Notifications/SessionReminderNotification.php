<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rappel envoye peu avant le debut d'un cours.
 *
 * Canal base de donnees uniquement : la notification s'affiche dans la
 * cloche de l'application, aucun e-mail n'est envoye.
 */
class SessionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private array $details) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'session_reminder',
            'title'      => 'Votre cours commence bientôt',
            'message'    => 'Cours de ' . $this->details['class_name']
                          . ' à ' . $this->details['start_time']
                          . ' (dans ' . $this->details['minutes'] . ' minutes)'
                          . (isset($this->details['coach_name']) ? ' avec ' . $this->details['coach_name'] : '')
                          . '.',
            'session_id' => $this->details['session_id'],
            'action_url' => $this->details['action_url'] ?? null,
        ];
    }
}
