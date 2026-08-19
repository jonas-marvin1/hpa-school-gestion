<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\CourseClass;
use App\Models\ClassSession;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $kpis = [
            'total_students' => User::role('student')->count(),
            'total_coaches' => User::role('coach')->count(),
            'total_classes' => CourseClass::count(),
            'total_sessions' => ClassSession::count(),
            'completed_sessions' => ClassSession::where('status', 'completed')->count(),
            'pending_payments' => Payment::where('status', 'pending')->sum('total_amount'),
            'paid_payments' => Payment::where('status', 'paid')->sum('total_amount'),
        ];

        // Echeances des apprenants a relancer : celles qui tombent aujourd'hui
        // et celles deja depassees. C'est ce que l'administrateur doit voir en
        // arrivant, pour savoir qui rappeler dans la journee.
        $echeancesDuJour = \App\Models\StudentPayment::with(['student', 'paymentPlan'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', today())
            ->orderBy('due_date')
            ->get();

        $echeancesEnRetard = \App\Models\StudentPayment::with(['student', 'paymentPlan'])
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')   // la plus ancienne d'abord : la plus urgente
            ->get();

        $totalDuJour    = $echeancesDuJour->sum('amount');
        $totalEnRetard  = $echeancesEnRetard->sum('amount');

        // Example Alerts
        $alerts = [];
        
        $classesWithoutStudents = CourseClass::whereDoesntHave('users', function($query) {
            $query->whereHas('roles', function($q) {
                $q->where('name', 'student');
            });
        })->get();

        if ($classesWithoutStudents->count() > 0) {
            $classNames = $classesWithoutStudents->pluck('name')->implode(', ');
            $alerts[] = [
                'type' => 'warning',
                'message' => $classesWithoutStudents->count() . ' classe(s) n\'ont aucun apprenant affecté : ' . $classNames
            ];
        }

        return view('admin.dashboard', compact(
            'kpis', 'alerts',
            'echeancesDuJour', 'echeancesEnRetard', 'totalDuJour', 'totalEnRetard'
        ));
    }
}
