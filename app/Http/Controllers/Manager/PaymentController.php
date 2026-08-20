<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
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
        
        $payments = $query->paginate(15)->appends($request->all());

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

        // Annees proposees par le filtre. Elles sont deduites des fiches
        // existantes plutot que d'une fenetre glissante autour de l'annee
        // courante : aucune annee ayant des donnees ne peut ainsi manquer,
        // et il n'y a plus rien a modifier dans le code chaque annee.
        $years = Payment::query()
            ->select('year')
            ->distinct()
            ->whereNotNull('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->push(now()->year)   // l'annee en cours reste proposee meme sans fiche
            ->unique()
            ->sortDesc()
            ->values();

        return view('manager.payments.index', compact('payments', 'totalToPay', 'coaches', 'classes', 'years'));
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
}