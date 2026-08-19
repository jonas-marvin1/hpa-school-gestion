<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\CourseClass;
use App\Models\ClassSession;

class DashboardController extends Controller
{
    public function index()
    {
        $totalClasses = CourseClass::count();
        $upcomingSessions = ClassSession::where('start_time', '>=', now())
                                        ->orderBy('start_time')
                                        ->take(5)
                                        ->get();
                                        
        $kpis = [
            'total_sessions' => ClassSession::count(),
            'completed_sessions' => ClassSession::where('status', 'completed')->count(),
            'pending_payments' => \App\Models\Payment::where('status', 'pending')->sum('total_amount'),
            'paid_payments' => \App\Models\Payment::where('status', 'paid')->sum('total_amount'),
        ];

        return view('manager.dashboard', compact('totalClasses', 'upcomingSessions', 'kpis'));
    }
}
