<?php

namespace App\View\Components;

use App\Models\Assignment;
use App\Models\StudentPayment;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Bandeau d'alertes de l'apprenant : echeance de paiement proche ou depassee,
 * et devoirs a rendre dont la date approche.
 *
 * Les alertes sont recalculees a chaque affichage plutot que stockees en base :
 * elles restent donc exactes sans dependre d'une tache planifiee, ce que
 * l'hebergement mutualise ne garantit pas.
 */
class AlertesEcheances extends Component
{
    /** Nombre de jours avant l'echeance a partir duquel on previent. */
    public const PREAVIS_PAIEMENT_JOURS = 7;

    /** Idem pour les devoirs. */
    public const PREAVIS_DEVOIR_JOURS = 3;

    public $alertes = [];

    public function __construct()
    {
        $student = Auth::user();

        if (! $student) {
            return;
        }

        $this->alertes = array_merge(
            $this->alertesPaiement($student),
            $this->alertesDevoirs($student)
        );
    }

    /** Paiements en retard, ou dont l'echeance tombe dans les prochains jours. */
    private function alertesPaiement($student)
    {
        $alertes = [];

        $paiements = StudentPayment::where('student_id', $student->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', now()->addDays(self::PREAVIS_PAIEMENT_JOURS))
            ->orderBy('due_date')
            ->get();

        foreach ($paiements as $paiement) {
            // due_date est caste en "date", donc fixe a minuit : isPast() rendrait
            // "en retard" des le matin du jour de l'echeance. On compare au jour pres.
            $enRetard = $paiement->due_date->lt(now()->startOfDay());
            $jours = (int) now()->startOfDay()->diffInDays($paiement->due_date, false);

            $alertes[] = [
                'niveau'  => $enRetard ? 'danger' : 'avertissement',
                'titre'   => $enRetard ? 'Paiement en retard' : 'Échéance de paiement proche',
                'message' => $enRetard
                    ? 'Un versement de ' . number_format($paiement->amount, 0, ',', ' ') . ' FCFA était attendu le ' . $paiement->due_date->format('d/m/Y') . '.'
                    : 'Un versement de ' . number_format($paiement->amount, 0, ',', ' ') . ' FCFA est attendu le ' . $paiement->due_date->format('d/m/Y')
                        . ($jours === 0 ? " (aujourd'hui)." : ' (dans ' . $jours . ' jour' . ($jours > 1 ? 's' : '') . ').'),
                'lien'      => route('student.payments.index'),
                'lienLabel' => 'Voir mes paiements',
            ];
        }

        return $alertes;
    }

    /** Devoirs non rendus dont la date de remise approche ou est depassee. */
    private function alertesDevoirs($student)
    {
        $classIds = $student->courseClasses->pluck('id');

        if ($classIds->isEmpty()) {
            return [];
        }

        $rendus = Submission::where('student_id', $student->id)->pluck('assignment_id');

        // La date limite effective depend d'une eventuelle prolongation
        // individuelle (fiche du 14/09/2026, point 3) : le filtre sur
        // due_date ne peut donc plus se faire en SQL, il faut charger les
        // devoirs candidats puis calculer/filtrer en PHP via dateLimitePour().
        //
        // Le filtre sur student_id manquait ici avant ce point : un devoir
        // nominatif attribue a un autre apprenant de la meme classe
        // declenchait quand meme une alerte, alors qu'il n'est meme pas
        // visible dans sa liste de devoirs.
        $devoirs = Assignment::whereIn('course_class_id', $classIds)
            ->whereNotNull('due_date')
            ->whereNotIn('id', $rendus)
            ->where(function ($q) use ($student) {
                $q->whereNull('student_id')->orWhere('student_id', $student->id);
            })
            ->get()
            ->map(fn ($devoir) => [$devoir, $devoir->dateLimitePour($student)])
            ->filter(fn ($paire) => $paire[1]->lte(now()->addDays(self::PREAVIS_DEVOIR_JOURS)))
            ->sortBy(fn ($paire) => $paire[1]);

        $alertes = [];

        foreach ($devoirs as [$devoir, $echeance]) {
            $echu  = $echeance->isPast();
            $jours = (int) now()->startOfDay()->diffInDays($echeance, false);

            $alertes[] = [
                'niveau'  => $echu ? 'danger' : 'avertissement',
                'titre'   => $echu ? 'Devoir en retard' : 'Devoir à rendre bientôt',
                'message' => '« ' . $devoir->title . ' » '
                    . ($echu
                        ? 'devait être rendu le ' . $echeance->format('d/m/Y') . '.'
                        : 'est à rendre le ' . $echeance->format('d/m/Y')
                            . ($jours === 0 ? " (aujourd'hui)." : ' (dans ' . $jours . ' jour' . ($jours > 1 ? 's' : '') . ').')),
                'lien'      => route('student.assignments.index'),
                'lienLabel' => 'Voir mes devoirs',
            ];
        }

        return $alertes;
    }

    public function render()
    {
        return view('components.alertes-echeances');
    }
}
