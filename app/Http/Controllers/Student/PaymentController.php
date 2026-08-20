<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentPayment;
use App\Services\AnneesDisponibles;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $studentId = Auth::id();
        
        $query = StudentPayment::with('program')
            ->where('student_id', $studentId)
            ->orderBy('due_date', 'asc');

        // Filters
        if ($request->filled('month')) {
            $query->whereMonth('due_date', $request->month);
        }
        // Sans ce filtre, choisir un mois remontait ce mois de toutes les
        // annees confondues : les echeances d'aout 2026 et d'aout 2027
        // figuraient dans la meme liste.
        if ($request->filled('year')) {
            $query->whereYear('due_date', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(10)->appends($request->all());

        // Annees proposees par le filtre : celles ou l'apprenant a des
        // echeances, l'annee en cours et les annees a venir. Un echeancier
        // s'etale souvent sur l'annee suivante : sans les annees a venir, ces
        // echeances seraient invisibles au filtre.
        $years = AnneesDisponibles::depuisColonneDate(
            StudentPayment::where('student_id', $studentId),
            'due_date'
        );

        // Prochain paiement
        $nextPayment = StudentPayment::where('student_id', $studentId)
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->first();

        // Progression visuelle
        $totalPayments = StudentPayment::where('student_id', $studentId)->count();
        $paidPayments = StudentPayment::where('student_id', $studentId)->where('status', 'paid')->count();
        
        $progressPercentage = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;

        return view('student.payments.index', compact('payments', 'nextPayment', 'progressPercentage', 'totalPayments', 'paidPayments', 'years'));
    }
}
