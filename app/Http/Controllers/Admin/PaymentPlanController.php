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
            'echeances'             => 'required|array|min:1',
            'echeances.*.amount'    => 'required|numeric|min:1',
            'echeances.*.due_date'  => 'required|date',
        ], [], [
            'total_amount'   => 'coût total',
            'advance_amount' => 'avance',
            'echeances'      => 'échéances',
        ]);

        if ((float) $valide['advance_amount'] > (float) $valide['total_amount']) {
            throw ValidationException::withMessages([
                'advance_amount' => "L'avance ne peut pas dépasser le coût total.",
            ]);
        }

        $planExistant = PaymentPlan::where('student_id', $student->id)->latest('id')->first();

        // Les echeances deja reglees ne sont pas resaisies : elles sont
        // conservees telles quelles. Elles doivent donc etre deduites du
        // montant a repartir, sinon modifier un plan entame deviendrait
        // impossible — il faudrait ressaisir des sommes deja encaissees.
        $dejaRegle = $planExistant
            ? (float) $planExistant->echeances()->where('status', 'paid')->sum('amount')
            : 0.0;

        $sommeEcheances = collect($valide['echeances'])->sum(fn ($e) => (float) $e['amount']);
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

        DB::transaction(function () use ($student, $valide, $programmeId) {
            $plan = PaymentPlan::where('student_id', $student->id)->latest('id')->first();

            if ($plan) {
                $plan->update([
                    'program_id'     => $programmeId,
                    'total_amount'   => $valide['total_amount'],
                    'advance_amount' => $valide['advance_amount'],
                    'notes'          => $valide['notes'] ?? null,
                ]);

                // Les echeances deja reglees sont conservees telles quelles :
                // seules celles restant a payer sont remplacees.
                $plan->echeances()->where('status', '!=', 'paid')->delete();
            } else {
                $plan = PaymentPlan::create([
                    'student_id'     => $student->id,
                    'program_id'     => $programmeId,
                    'total_amount'   => $valide['total_amount'],
                    'advance_amount' => $valide['advance_amount'],
                    'notes'          => $valide['notes'] ?? null,
                ]);
            }

            foreach ($valide['echeances'] as $e) {
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
