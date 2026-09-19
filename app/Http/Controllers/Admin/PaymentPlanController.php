<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Models\Program;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saisie du plan de paiement d'un apprenant : cout total, avance versee
 * et echeancier. C'est ce plan qui alimente les rappels automatiques.
 */
class PaymentPlanController extends Controller
{
    public function edit(User $student)
    {
        $this->garantirApprenant($student);

        $plan = PaymentPlan::with('echeances')
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        return view('admin.payment_plans.edit', [
            'student'   => $student,
            'plan'      => $plan,
            'programme' => $this->programmeDeLApprenant($student),
        ]);
    }

    public function store(Request $request, User $student)
    {
        $this->garantirApprenant($student);

        $valide = $request->validate([
            'total_amount'          => 'required|numeric|min:0',
            'advance_amount'        => 'required|numeric|min:0',
            'notes'                 => 'nullable|string|max:1000',
            // Un plan peut n'avoir aucune echeance a venir a ressaisir : c'est
            // justement le cas d'un dossier ramene a ce qui est deja regle
            // (point 1 du 19/09/2026, coche a zero echeance restante).
            'echeances'             => 'nullable|array',
            'echeances.*.amount'    => 'required|numeric|min:1',
            'echeances.*.due_date'  => 'required|date',
        ], [], [
            'total_amount'   => 'coût total',
            'advance_amount' => 'avance',
            'echeances'      => 'échéances',
        ]);

        $echeancesSaisies = $valide['echeances'] ?? [];

        $planExistant = PaymentPlan::where('student_id', $student->id)->latest('id')->first();

        // Les echeances deja reglees ne sont pas resaisies : elles sont
        // conservees telles quelles. Elles doivent donc etre deduites du
        // montant a repartir, sinon modifier un plan entame deviendrait
        // impossible — il faudrait ressaisir des sommes deja encaissees.
        $dejaRegle = $planExistant
            ? (float) $planExistant->echeances()->where('status', 'paid')->sum('amount')
            : 0.0;

        // Le cout total ne peut jamais descendre sous ce qui est deja
        // encaisse (avance + echeances reglees) : ce serait afficher un
        // solde negatif. Un remboursement trace releve d'un autre besoin
        // (point 1 du 19/09/2026).
        $montantDejaEncaisse = (float) $valide['advance_amount'] + $dejaRegle;

        if ((float) $valide['total_amount'] < $montantDejaEncaisse) {
            throw ValidationException::withMessages([
                'total_amount' => sprintf(
                    'Le coût total ne peut pas être inférieur au montant déjà réglé (%s).',
                    number_format($montantDejaEncaisse, 0, ',', ' ')
                ),
            ]);
        }

        $sommeEcheances = collect($echeancesSaisies)->sum(fn ($e) => (float) $e['amount']);
        $attendu = (float) $valide['total_amount'] - (float) $valide['advance_amount'] - $dejaRegle;

        // Le plan doit se boucler : sinon les rappels annonceraient un solde
        // qui ne correspondrait a rien.
        if (abs($sommeEcheances - $attendu) >= 1) {
            throw ValidationException::withMessages([
                'echeances' => sprintf(
                    'Le total des échéances à venir (%s) doit égaler le reste à répartir (%s)%s.',
                    number_format($sommeEcheances, 0, ',', ' '),
                    number_format($attendu, 0, ',', ' '),
                    $dejaRegle > 0
                        ? sprintf(', soit %s de coût total moins %s d\'avance et %s déjà réglés',
                            number_format($valide['total_amount'], 0, ',', ' '),
                            number_format($valide['advance_amount'], 0, ',', ' '),
                            number_format($dejaRegle, 0, ',', ' '))
                        : ''
                ),
            ]);
        }

        // Le programme se deduit de la classe de l'apprenant : le redemander
        // exposerait a une saisie incoherente avec son affectation reelle.
        $programmeId = $this->programmeDeLApprenant($student)?->id;

        DB::transaction(function () use ($student, $valide, $programmeId, $echeancesSaisies) {
            $plan = PaymentPlan::where('student_id', $student->id)->latest('id')->first();

            if ($plan) {
                $plan->update([
                    'program_id'     => $programmeId,
                    'total_amount'   => $valide['total_amount'],
                    'advance_amount' => $valide['advance_amount'],
                    'notes'          => $valide['notes'] ?? null,
                ]);

                // Seules les echeances en attente sont remplacees. Les
                // reglees sont conservees car deja encaissees ; les annulees
                // le sont aussi, avec leur motif, sinon l'historique qu'on
                // vient d'ajouter disparaitrait au premier reajustement du
                // calendrier. La somme qu'elles representaient reste due
                // (regle du point 1 du 14/09/2026) : c'est a l'administrateur
                // de la replanifier via une nouvelle echeance pending.
                $plan->echeances()->where('status', 'pending')->delete();
            } else {
                $plan = PaymentPlan::create([
                    'student_id'     => $student->id,
                    'program_id'     => $programmeId,
                    'total_amount'   => $valide['total_amount'],
                    'advance_amount' => $valide['advance_amount'],
                    'notes'          => $valide['notes'] ?? null,
                ]);
            }

            foreach ($echeancesSaisies as $e) {
                StudentPayment::create([
                    'student_id'      => $student->id,
                    'program_id'      => $programmeId,
                    'payment_plan_id' => $plan->id,
                    'amount'          => $e['amount'],
                    'due_date'        => $e['due_date'],
                    'status'          => 'pending',
                ]);
            }
        });

        return redirect()
            ->route('admin.students.plan.edit', $student)
            ->with('status', 'Plan de paiement enregistré. Les rappels seront envoyés automatiquement avant chaque échéance.');
    }

    /** Marque une echeance comme reglee. */
    public function marquerPayee(StudentPayment $echeance)
    {
        $echeance->update([
            'status'    => 'paid',
            'paid_date' => now(),
        ]);

        return back()->with('status', 'Échéance marquée comme réglée.');
    }

    /**
     * Annule une echeance en attente. Le motif est facultatif : ce qui
     * protege le geste, c'est la fenetre de confirmation cote vue, pas une
     * saisie obligatoire (correctif du 14/09/2026, la premiere version
     * exigeait un motif que la vue ne demandait jamais).
     *
     * Une echeance deja reglee est refusee : l'annuler ne serait pas une
     * annulation mais un remboursement, une operation comptable differente
     * qu'on traitera separement si le besoin s'en presente un jour.
     */
    public function annuler(Request $request, StudentPayment $echeance)
    {
        if ($echeance->status !== 'pending') {
            throw ValidationException::withMessages([
                'echeance' => 'Seule une échéance en attente peut être annulée.',
            ]);
        }

        $valide = $request->validate([
            'motif' => 'nullable|string|max:255',
        ], [], ['motif' => 'motif']);

        // Le motif s'ajoute aux notes existantes plutot que de les remplacer,
        // prefixe par la date : c'est la seule trace de ce qui s'est passe
        // pour qui reprendra le dossier dans deux ans. Sans motif, la date
        // et l'auteur (trace par ailleurs) suffisent a retracer l'operation.
        $entree = filled($valide['motif'] ?? null)
            ? sprintf('[%s] Annulée : %s', now()->format('d/m/Y'), $valide['motif'])
            : sprintf('[%s] Annulée', now()->format('d/m/Y'));

        $echeance->update([
            'status' => 'cancelled',
            'notes'  => trim(($echeance->notes ? $echeance->notes."\n" : '').$entree),
        ]);

        return back()->with('status', 'Échéance annulée.');
    }

    /** Remet une echeance annulee en attente. */
    public function reactiver(StudentPayment $echeance)
    {
        if ($echeance->status !== 'cancelled') {
            throw ValidationException::withMessages([
                'echeance' => 'Seule une échéance annulée peut être réactivée.',
            ]);
        }

        $echeance->update(['status' => 'pending']);

        return back()->with('status', 'Échéance réactivée.');
    }

    /**
     * Programme suivi par l'apprenant, deduit de sa classe.
     *
     * Un apprenant est affecte a une classe, elle-meme rattachee a un niveau
     * qui appartient a un programme. L'information existe donc deja : la
     * redemander a la saisie n'apporterait qu'un risque d'incoherence.
     */
    private function programmeDeLApprenant(User $student): ?Program
    {
        return $student->courseClasses()
            ->with('level.program')
            ->get()
            ->map(fn ($classe) => $classe->level?->program)
            ->filter()
            ->first();
    }

    private function garantirApprenant(User $student): void
    {
        abort_unless($student->hasRole('student'), 404);
    }
}
