<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentPayment;
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(10)->appends($request->all());

        // Prochain paiement
        $nextPayment = StudentPayment::where('student_id', $studentId)
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->first();

        // Progression visuelle
        $totalPayments = StudentPayment::where('student_id', $studentId)->count();
        $paidPayments = StudentPayment::where('student_id', $studentId)->where('status', 'paid')->count();
        
        $progressPercentage = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;

        return view('student.payments.index', compact('payments', 'nextPayment', 'progressPercentage', 'totalPayments', 'paidPayments'));
    }
}
