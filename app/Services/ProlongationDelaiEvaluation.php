<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentDeadlineExtension;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\AssignmentDeadlineExtendedNotification;
use App\Notifications\AssignmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Octroi d'une prolongation de delai, individuelle ou collective, sur une
 * evaluation (fiche du 14/09/2026, point 3).
 *
 * Regroupe les regles de validation metier communes aux deux formes de
 * prolongation, controleur par controleur (Manager\AssignmentController et
 * Coach\AssignmentController partagent cette classe) plutot que dupliquees.
 * L'habilitation (qui a le droit) reste hors de cette classe : elle est
 * verifiee en amont par AssignmentPolicy, avant l'appel.
 */
class ProlongationDelaiEvaluation
{
    /**
     * @return array{date: Carbon, notifies: int} nombre d'apprenants dont le delai a change.
     */
    public function accorder(Assignment $assignment, ?User $student, Carbon $nouvelleDate, string $motif, User $accordePar): array
    {
        if (! $nouvelleDate->isFuture()) {
            throw ValidationException::withMessages([
                'new_due_date' => 'La nouvelle date limite doit être dans le futur.',
            ]);
        }

        return $student
            ? $this->accorderIndividuelle($assignment, $student, $nouvelleDate, $motif, $accordePar)
            : $this->accorderCollective($assignment, $nouvelleDate, $motif, $accordePar);
    }

    private function accorderIndividuelle(Assignment $assignment, User $student, Carbon $nouvelleDate, string $motif, User $accordePar): array
    {
        $this->assurerAppartenance($assignment, $student);

        if (Submission::where('assignment_id', $assignment->id)->where('student_id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'student_id' => 'Cet apprenant a déjà déposé un rendu : le délai ne peut plus être prolongé pour lui.',
            ]);
        }

        $dateActuelle = $assignment->dateLimitePour($student);

        if (! $nouvelleDate->gt($dateActuelle)) {
            throw ValidationException::withMessages([
                'new_due_date' => 'La nouvelle date limite doit être postérieure à la date limite actuelle de cet apprenant (' . $dateActuelle->format('d/m/Y à H:i') . ').',
            ]);
        }

        AssignmentDeadlineExtension::create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'previous_due_date' => $dateActuelle,
            'new_due_date' => $nouvelleDate,
            'motif' => $motif,
            'extended_by' => $accordePar->id,
        ]);

        $this->avertir($assignment, collect([$student]), $nouvelleDate);

        return ['date' => $nouvelleDate, 'notifies' => 1, 'concernes' => collect([$student])];
    }

    private function accorderCollective(Assignment $assignment, Carbon $nouvelleDate, string $motif, User $accordePar): array
    {
        $dateActuelle = Carbon::parse($assignment->due_date);

        if (! $nouvelleDate->gt($dateActuelle)) {
            throw ValidationException::withMessages([
                'new_due_date' => 'La nouvelle date limite doit être postérieure à la date limite actuelle du devoir (' . $dateActuelle->format('d/m/Y à H:i') . ').',
            ]);
        }

        $submittedIds = Submission::where('assignment_id', $assignment->id)->pluck('student_id')->all();

        // Ne concerne, de fait, que les apprenants sans rendu : voir §2 de
        // la fiche. Un devoir nominatif n'a qu'un seul destinataire possible.
        $etudiants = $assignment->student_id
            ? collect([$assignment->student])->filter()
            : $assignment->courseClass->users()->role('student')->get();

        $concernes = $etudiants->reject(fn (User $s) => in_array($s->id, $submittedIds, true));

        AssignmentDeadlineExtension::create([
            'assignment_id' => $assignment->id,
            'student_id' => null,
            'previous_due_date' => $dateActuelle,
            'new_due_date' => $nouvelleDate,
            'motif' => $motif,
            'extended_by' => $accordePar->id,
        ]);

        // due_date fait foi pour tout apprenant sans prolongation
        // individuelle qui lui est propre : voir Assignment::dateLimitePour().
        $assignment->update(['due_date' => $nouvelleDate]);

        $this->avertir($assignment, $concernes, $nouvelleDate);

        return ['date' => $nouvelleDate, 'notifies' => $concernes->count(), 'concernes' => $concernes];
    }

    /**
     * Notifie chaque apprenant concerne et reinitialise le marqueur de
     * rappel de devoir : sans ce nettoyage, un rappel deja envoye aujourd'hui
     * pour l'ancienne date bloquerait tout nouveau rappel pour la nouvelle
     * (SendDueReminders::alreadyNotifiedToday() ne distingue que par
     * assignment_id, pas par date), et la prolongation resterait a moitie
     * muette (fiche du 14/09/2026, §6).
     */
    private function avertir(Assignment $assignment, iterable $students, Carbon $nouvelleDate): void
    {
        foreach ($students as $student) {
            $student->notifications()
                ->where('type', AssignmentReminderNotification::class)
                ->get()
                ->each(function ($notification) use ($assignment) {
                    if (($notification->data['assignment_id'] ?? null) === $assignment->id) {
                        $notification->delete();
                    }
                });

            $student->notify(new AssignmentDeadlineExtendedNotification([
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'new_due_date' => $nouvelleDate->format('d/m/Y à H:i'),
                'action_url' => route('student.assignments.show', $assignment),
            ]));
        }
    }

    /**
     * Un apprenant vise individuellement doit appartenir a la classe cible
     * du devoir ; si le devoir est deja nominatif, seule une prolongation
     * pour cet apprenant-la a un sens (§4 de la fiche).
     */
    private function assurerAppartenance(Assignment $assignment, User $student): void
    {
        if ($assignment->student_id) {
            if ($assignment->student_id !== $student->id) {
                throw ValidationException::withMessages([
                    'student_id' => 'Cette évaluation est attribuée à un autre apprenant.',
                ]);
            }

            return;
        }

        $appartient = $assignment->courseClass->users()->role('student')->where('users.id', $student->id)->exists();

        if (! $appartient) {
            throw ValidationException::withMessages([
                'student_id' => "L'apprenant sélectionné n'appartient pas à la classe de ce devoir.",
            ]);
        }
    }
}
