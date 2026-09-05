<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsCsv;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\User;
use App\Services\AnneesDisponibles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ExportsCsv;

    /** Libelles des statuts de fiche, partages entre l'ecran et l'export CSV. */
    private const STATUTS = [
        'paid'      => 'Payé',
        'validated' => 'Payé',
        'cancelled' => 'Annulé',
        'pending'   => 'En attente',
    ];

    /**
     * Construit la requete des fiches de paie filtree par les criteres de
     * l'URL, sans pagination : utilisee telle quelle par l'ecran et par
     * l'export CSV.
     */
    private function filtrerPaiements(Request $request): Builder
    {
        $query = Payment::with(['coach', 'validator', 'sessions.courseClass'])->orderBy('created_at', 'desc');
        if ($request->has('search_coach') && $request->search_coach != '') {
            $search = $request->search_coach;
            $query->whereHas('coach', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('coach_id') && $request->coach_id != '') {
            $query->where('coach_id', $request->coach_id);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('month') && $request->month != '') {
            $query->where('month', $request->month);
        }

        if ($request->has('year') && $request->year != '') {
            $query->where('year', $request->year);
        }

        if ($request->has('class_id') && $request->class_id != '') {
            $classId = $request->class_id;
            $query->whereHas('sessions', function($q) use ($classId) {
                $q->where('course_class_id', $classId);
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $payments = $this->filtrerPaiements($request)->paginate(15)->appends($request->all());

        // Calculate Total à payer (only 'pending' payments)
        $totalToPayQuery = Payment::where('status', 'pending');
        
        if ($request->has('search_coach') && $request->search_coach != '') {
            $search = $request->search_coach;
            $totalToPayQuery->whereHas('coach', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('coach_id') && $request->coach_id != '') {
            $totalToPayQuery->where('coach_id', $request->coach_id);
        }
        if ($request->has('month') && $request->month != '') {
            $totalToPayQuery->where('month', $request->month);
        }
        if ($request->has('year') && $request->year != '') {
            $totalToPayQuery->where('year', $request->year);
        }
        if ($request->has('class_id') && $request->class_id != '') {
            $classId = $request->class_id;
            $totalToPayQuery->whereHas('sessions', function($q) use ($classId) {
                $q->where('course_class_id', $classId);
            });
        }
        
        $totalToPay = $totalToPayQuery->sum('total_amount');
        
        $coaches = User::role('coach')->get();
        $classes = CourseClass::orderBy('name')->get();

        // Annees proposees par le filtre : celles des fiches existantes,
        // l'annee en cours et les annees a venir. La construction est
        // centralisee dans AnneesDisponibles, qui alimente de la meme facon
        // tous les filtres par annee de l'application.
        $years = AnneesDisponibles::depuisColonneAnnee(Payment::query());

        return view('manager.payments.index', compact('payments', 'totalToPay', 'coaches', 'classes', 'years'));
    }

    /**
     * Export CSV des fiches de paie correspondant aux filtres actifs.
     * Meme requete que l'ecran, sans pagination.
     */
    public function export(Request $request)
    {
        $payments = $this->filtrerPaiements($request)->get();

        $lignes = $payments->map(function (Payment $payment) {
            $session = $payment->sessions->first();

            return [
                $session ? $session->start_time->format('d/m/Y') : str_pad((string) $payment->month, 2, '0', STR_PAD_LEFT).'/'.$payment->year,
                $session ? $session->start_time->format('H:i').' - '.$session->end_time->format('H:i') : '-',
                $session && $session->courseClass ? $session->courseClass->name : 'N/A',
                $payment->coach->name ?? 'Inconnu',
                self::STATUTS[$payment->status] ?? $payment->status,
                number_format((float) $payment->total_amount, 2, ',', ''),
            ];
        });

        return $this->streamCsv('fiches_de_paie.csv', ['Date', 'Horaire', 'Classe', 'Formateur', 'Statut', 'Montant'], $lignes);
    }

    public function create()
    {
        // Simple form to choose month and year to generate
        return view('manager.payments.create');
    }

    public function store(Request $request, \App\Services\PaymentService $paymentService)
    {
        $result = $paymentService->generatePendingPayments();
        $generatedCount = $result['count'];

        return redirect()->route('manager.payments.index')
            ->with('status', "$generatedCount fiche(s) de paie générée(s).");
    }

    public function update(Request $request, Payment $payment)
    {
        $payment->update([
            'status' => 'paid',
            'validated_by' => Auth::id(),
        ]);

        return redirect()->back()->with('status', 'Fiche de paie payée.');
    }

    public function markAsPaid(Request $request, Payment $payment)
    {
        $payment->update([
            'status' => 'paid',
        ]);

        return redirect()->back()->with('status', 'Paiement réel enregistré.');
    }

    /**
     * Passe plusieurs fiches a « payé » en une seule operation.
     *
     * L'administrateur regle souvent plusieurs formateurs le meme jour :
     * repeter le clic ligne par ligne est une perte de temps et une source
     * d'oubli.
     */
    public function markManyAsPaid(Request $request)
    {
        $valide = $request->validate([
            'payment_ids'   => 'required|array|min:1',
            'payment_ids.*' => 'integer|exists:payments,id',
        ], [
            'payment_ids.required' => 'Sélectionnez au moins une fiche à régler.',
        ]);

        // On ne touche qu'aux fiches encore en attente : si l'une d'elles a ete
        // reglee entre l'affichage et la validation, elle est simplement ignoree
        // plutot que reecrite.
        $misesAJour = Payment::whereIn('id', $valide['payment_ids'])
            ->where('status', 'pending')
            ->update(['status' => 'paid']);

        $ignorees = count($valide['payment_ids']) - $misesAJour;

        $message = $misesAJour === 0
            ? "Aucune fiche à régler : la sélection était déjà payée."
            : sprintf(
                '%d fiche%s réglée%s.%s',
                $misesAJour,
                $misesAJour > 1 ? 's' : '',
                $misesAJour > 1 ? 's' : '',
                $ignorees > 0 ? " {$ignorees} déjà réglée" . ($ignorees > 1 ? 's' : '') . ' ignorée' . ($ignorees > 1 ? 's' : '') . '.' : ''
            );

        return redirect()->back()->with('status', $message);
    }

    public function show(Payment $payment)
    {
        $sessions = $payment->sessions()->with(['courseClass', 'coach'])->get();

        return view('manager.payments.show', compact('payment', 'sessions'));
    }

    public function destroy(Payment $payment)
    {
        // Unlink sessions so they can be regenerated if needed
        $payment->sessions()->update(['payment_id' => null]);
        $payment->delete();

        return redirect()->route('manager.payments.index')->with('status', 'Fiche de paie supprimée avec succès. La session est à nouveau disponible pour facturation.');
    }

    /**
     * Supprime plusieurs fiches en une seule operation.
     *
     * Seules les fiches en attente ou annulees sont supprimees : supprimer
     * une fiche delie ses sessions (elles redeviennent facturables), et le
     * faire en masse sur des fiches deja payees ouvrirait la porte a un
     * double paiement. La suppression d'une fiche payee reste possible a
     * l'unite via destroy(), un geste volontaire et isole.
     */
    public function destroyMany(Request $request)
    {
        $valide = $request->validate([
            'payment_ids'   => 'required|array|min:1',
            'payment_ids.*' => 'integer|exists:payments,id',
        ], [
            'payment_ids.required' => 'Sélectionnez au moins une fiche à supprimer.',
        ]);

        $aSupprimer = Payment::whereIn('id', $valide['payment_ids'])
            ->whereIn('status', ['pending', 'cancelled'])
            ->get();

        $supprimees = $aSupprimer->count();
        $ignorees = count($valide['payment_ids']) - $supprimees;

        // Deliaison des sessions et suppression dans la meme transaction :
        // un echec en cours de route ne doit pas laisser des sessions
        // deliees sans que la fiche correspondante ait disparu.
        DB::transaction(function () use ($aSupprimer) {
            foreach ($aSupprimer as $payment) {
                $payment->sessions()->update(['payment_id' => null]);
                $payment->delete();
            }
        });

        if ($supprimees === 0) {
            $message = 'Aucune fiche supprimée : la sélection ne contenait que des fiches payées.';
        } else {
            $message = sprintf('%d fiche%s supprimée%s.', $supprimees, $supprimees > 1 ? 's' : '', $supprimees > 1 ? 's' : '');
            if ($ignorees > 0) {
                $message .= sprintf(
                    ' %d fiche%s payée%s ignorée%s : supprimez-les une par une si nécessaire.',
                    $ignorees, $ignorees > 1 ? 's' : '', $ignorees > 1 ? 's' : '', $ignorees > 1 ? 's' : ''
                );
            }
        }

        return redirect()->route('manager.payments.index')->with('status', $message);
    }
}