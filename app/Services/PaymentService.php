<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ClassSession;

class PaymentService
{
    /**
     * Generate one payment (fiche de paie) per validated session that isn't invoiced yet.
     * Returns an array with the count of generated payments.
     */
    public function generatePendingPayments(): array
    {
        $sessions = ClassSession::where('status', 'validated')
            ->whereNull('payment_id')
            ->get();

        foreach ($sessions as $session) {
            $payment = Payment::create([
                'coach_id' => $session->coach_id,
                'month' => (int) $session->start_time->format('n'),
                'year' => (int) $session->start_time->format('Y'),
                'total_sessions' => 1,
                'total_amount' => $session->amount,
                'status' => 'pending',
            ]);

            $session->update(['payment_id' => $payment->id]);
        }

        return [
            'count' => $sessions->count(),
        ];
    }
}
