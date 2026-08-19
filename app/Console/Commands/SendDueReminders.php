<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\ClassSession;
use App\Models\StudentPayment;
use App\Models\User;
use App\Notifications\AssignmentReminderNotification;
use App\Notifications\PaymentDueNotification;
use App\Notifications\SessionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDueReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = "Envoie les rappels d'échéance de paiement et de devoirs aux apprenants concernés";

    /** Jours avant l'échéance auxquels un rappel est envoyé (0 = jour J). */
    private const REMINDER_OFFSETS_DAYS = [3, 1, 0];

    /** Minutes avant le debut d'un cours auxquelles le rappel part. */
    private const SESSION_REMINDER_MINUTES = 20;

    /**
     * Marge de rattrapage sur le rappel de cours.
     *
     * Sans cron, la commande n'est declenchee que lorsqu'un utilisateur
     * visite l'application : le moment exact « T-20 minutes » peut donc etre
     * manque. On accepte une fenetre pour envoyer le rappel meme en retard,
     * plutot que de ne rien envoyer du tout.
     */
    private const SESSION_REMINDER_TOLERANCE_MINUTES = 25;

    public function handle(): int
    {
        $sentPayments = $this->sendPaymentReminders();
        $sentAssignments = $this->sendAssignmentReminders();
        $sentSessions = $this->sendSessionReminders();

        $this->info("Rappels envoyés : {$sentPayments} paiement(s), {$sentAssignments} devoir(s), {$sentSessions} cours.");

        return self::SUCCESS;
    }

    /**
     * Previent les apprenants dont un cours demarre dans les prochaines minutes.
     */
    private function sendSessionReminders(): int
    {
        $count = 0;

        $debut = now();
        $fin   = now()->addMinutes(self::SESSION_REMINDER_MINUTES);

        $sessions = ClassSession::query()
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_time', [
                $debut->copy()->subMinutes(self::SESSION_REMINDER_TOLERANCE_MINUTES),
                $fin,
            ])
            ->with(['courseClass.users', 'coach'])
            ->get();

        foreach ($sessions as $session) {
            if (! $session->courseClass) {
                continue;
            }

            $minutes = max(0, (int) round(now()->diffInMinutes($session->start_time, false)));

            foreach ($session->courseClass->users->filter(fn (User $u) => $u->hasRole('student')) as $student) {
                // Une seule alerte par apprenant et par seance, quel que soit
                // le nombre de fois ou la commande est declenchee.
                if ($this->alreadyNotified($student, SessionReminderNotification::class, 'session_id', $session->id)) {
                    continue;
                }

                $student->notify(new SessionReminderNotification([
                    'class_name' => $session->courseClass->name ?? 'votre classe',
                    'start_time' => $session->start_time->format('H:i'),
                    'minutes'    => $minutes,
                    'coach_name' => $session->coach->name ?? null,
                    'session_id' => $session->id,
                    'action_url' => route('student.dashboard'),
                ]));

                $count++;
            }
        }

        return $count;
    }

    private function sendPaymentReminders(): int
    {
        $count = 0;

        // Echeances a venir : rappel aux jalons definis ci-dessus.
        // Echeances depassees : relance quotidienne tant qu'elles ne sont pas
        // reglees. Sans cela, un impaye cesserait d'etre signale le lendemain
        // de sa date, ce qui est exactement le moment ou il faut relancer.
        $aVenir = collect(self::REMINDER_OFFSETS_DAYS)
            ->map(fn ($offset) => now()->addDays($offset)->toDateString());

        $echeances = StudentPayment::where('status', 'pending')
            ->where(function ($q) use ($aVenir) {
                $q->whereIn(DB::raw('DATE(due_date)'), $aVenir->all())
                  ->orWhereDate('due_date', '<', now()->toDateString());
            })
            ->with(['student', 'paymentPlan'])
            ->get();

        {
            $payments = $echeances;

            foreach ($payments as $payment) {
                if (!$payment->student) {
                    continue;
                }

                if ($this->alreadyNotifiedToday($payment->student, PaymentDueNotification::class, 'payment_id', $payment->id)) {
                    continue;
                }

                // Le solde restant n'est connu que si l'echeance appartient a
                // un plan de paiement ; les echeances isolees restent gerees.
                $plan = $payment->paymentPlan;
                $solde = $plan
                    ? number_format(max(0, $plan->soldeRestant() - (float) $payment->amount), 0, ',', ' ') . ' FCFA'
                    : null;

                $payment->student->notify(new PaymentDueNotification([
                    'amount' => number_format($payment->amount, 0, ',', ' ') . ' FCFA',
                    'due_date' => $payment->due_date->format('d/m/Y'),
                    'payment_id' => $payment->id,
                    'overdue' => $payment->due_date->lt(now()->startOfDay()),
                    'remaining_balance' => $solde,
                ]));

                $count++;
            }
        }

        return $count;
    }

    private function sendAssignmentReminders(): int
    {
        $count = 0;

        foreach (self::REMINDER_OFFSETS_DAYS as $offset) {
            $targetDate = now()->addDays($offset)->toDateString();

            $assignments = Assignment::whereDate('due_date', $targetDate)
                ->with(['courseClass.users', 'submissions'])
                ->get();

            foreach ($assignments as $assignment) {
                if (!$assignment->courseClass) {
                    continue;
                }

                $submittedStudentIds = $assignment->submissions->pluck('student_id')->all();

                $students = $assignment->courseClass->users->filter(fn (User $u) => $u->hasRole('student'));

                foreach ($students as $student) {
                    if (in_array($student->id, $submittedStudentIds, true)) {
                        continue;
                    }

                    if ($this->alreadyNotifiedToday($student, AssignmentReminderNotification::class, 'assignment_id', $assignment->id)) {
                        continue;
                    }

                    $student->notify(new AssignmentReminderNotification([
                        'class_name' => $assignment->courseClass->name ?? 'N/A',
                        'assignment_id' => $assignment->id,
                        'action_url' => route('student.assignments.show', $assignment),
                    ]));

                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Variante sans borne de date : une seance n'a lieu qu'une fois, son
     * rappel ne doit donc jamais repartir, meme un autre jour.
     */
    private function alreadyNotified(User $notifiable, string $notificationClass, string $dataKey, int $id): bool
    {
        return $notifiable->notifications()
            ->where('type', $notificationClass)
            ->get()
            ->contains(fn ($n) => ($n->data[$dataKey] ?? null) === $id);
    }

    private function alreadyNotifiedToday(User $notifiable, string $notificationClass, string $dataKey, int $id): bool
    {
        return $notifiable->notifications()
            ->where('type', $notificationClass)
            ->whereDate('created_at', now()->toDateString())
            ->get()
            ->contains(fn ($n) => ($n->data[$dataKey] ?? null) === $id);
    }
}
