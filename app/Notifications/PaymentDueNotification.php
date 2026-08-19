<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentDueNotification extends Notification
{
    use Queueable;

    public $paymentDetails;

    /**
     * Create a new notification instance.
     */
    public function __construct($paymentDetails)
    {
        $this->paymentDetails = $paymentDetails;
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
        // Le solde restant apres reglement est affiche lorsqu'un plan de
        // paiement existe : l'apprenant voit ainsi ou il en est de sa formation,
        // et pas seulement la somme du mois.
        $solde = isset($this->paymentDetails['remaining_balance'])
            ? ' Il resterait ensuite ' . $this->paymentDetails['remaining_balance'] . ' à régler.'
            : '';

        $retard = ($this->paymentDetails['overdue'] ?? false)
            ? 'En retard : votre paiement de ' . $this->paymentDetails['amount']
              . ' était attendu le ' . $this->paymentDetails['due_date'] . '.'
            : 'Votre paiement de ' . $this->paymentDetails['amount']
              . ' est attendu pour le ' . $this->paymentDetails['due_date'] . '.';

        return [
            'type' => 'payment_due',
            'title' => ($this->paymentDetails['overdue'] ?? false) ? 'Paiement en retard' : 'Échéance de paiement',
            'message' => $retard . $solde,
            'action_url' => route('student.dashboard'),
            'payment_id' => $this->paymentDetails['payment_id'] ?? null,
        ];
    }
}
