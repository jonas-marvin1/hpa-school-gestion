<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ClassSession;
use App\Models\CourseClass;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $coachId = Auth::id();
        
        $query = ClassSession::with(['courseClass', 'payment', 'coach'])
            ->where('coach_id', $coachId)
            ->whereIn('status', ['completed', 'validated'])
            ->orderBy('start_time', 'desc');

        // Filters
        if ($request->filled('month')) {
            $query->whereMonth('start_time', $request->month);
        }
        if ($request->filled('class_id')) {
            $query->where('course_class_id', $request->class_id);
        }
        if ($request->filled('status')) {
            if ($request->status === 'paid') {
                $query->whereHas('payment', function($q) {
                    $q->where('status', 'paid');
                });
            } elseif ($request->status === 'generated') {
                $query->whereHas('payment', function($q) {
                    $q->where('status', '!=', 'paid');
                });
            } elseif ($request->status === 'pending') {
                $query->whereNull('payment_id');
            }
        }

        $sessions = $query->paginate(15)->appends($request->all());

        $sessionDetails = [];
        foreach ($sessions as $session) {
            $sessionDetails[$session->id] = $session->amount;
        }

        // Calculate total for current month (or filtered month)
        $monthToCalculate = $request->filled('month') ? $request->month : now()->month;
        $yearToCalculate = $request->filled('year') ? $request->year : now()->year;
        
        $monthlyTotal = Payment::where('coach_id', $coachId)
            ->where('month', $monthToCalculate)
            ->where('year', $yearToCalculate)
            ->sum('total_amount');

        $classes = CourseClass::whereHas('classSessions', function($q) use ($coachId) {
            $q->where('coach_id', $coachId);
        })->get();

        // Calculate Total à payer à ce jour (all pending fiches de paie, toutes périodes confondues)
        $totalToPay = Payment::where('coach_id', $coachId)
            ->where('status', 'pending')
            ->sum('total_amount');

        return view('coach.payments.index', compact('sessions', 'sessionDetails', 'monthlyTotal', 'monthToCalculate', 'yearToCalculate', 'classes', 'totalToPay'));
    }
}
