<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ClassSession;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $coach = Auth::user();
        
        // Upcoming sessions for this coach
        $upcomingSessions = ClassSession::with('courseClass')
            ->where('coach_id', $coach->id)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->take(5)
            ->get();
            
        // Sessions to report (past sessions without a report)
        $pendingReports = ClassSession::with('courseClass')
            ->where('coach_id', $coach->id)
            ->where('start_time', '<', now())
            ->doesntHave('sessionReport')
            ->orderBy('start_time', 'desc')
            ->get();

        // Completed reports
        $completedReports = ClassSession::with(['courseClass', 'sessionReport'])
            ->where('coach_id', $coach->id)
            ->has('sessionReport')
            ->orderBy('start_time', 'desc')
            ->take(10) // Limit to last 10 for dashboard performance
            ->get();

        // Active classes for this coach
        // Get all unique classes where the coach has sessions
        $activeClasses = \App\Models\CourseClass::whereHas('classSessions', function ($query) use ($coach) {
            $query->where('coach_id', $coach->id);
        })->with(['level', 'level.program'])->get();

        // Rémunération : total du mois en cours (cumul de tous les paiements du mois)
        $monthlyTotal = Payment::where('coach_id', $coach->id)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->sum('total_amount');

        // Rémunération : total non encore payé, toutes périodes confondues
        $totalToPay = Payment::where('coach_id', $coach->id)
            ->where('status', 'pending')
            ->sum('total_amount');

        // Un coach intervient souvent sur plusieurs programmes. On les regroupe
        // pour n'afficher chaque programme qu'une fois, en rappelant les classes
        // concernees — sinon la meme description reviendrait a chaque classe.
        $programmes = $activeClasses
            ->filter(fn ($classe) => $classe->level?->program)
            ->groupBy(fn ($classe) => $classe->level->program->id)
            ->map(fn ($classes) => (object) [
                'programme' => $classes->first()->level->program,
                'classes'   => $classes->pluck('name')->unique()->values(),
            ])
            ->sortBy(fn ($e) => $e->programme->name)
            ->values();

        // Indicateurs du tableau de bord : le detail vit desormais dans les
        // pages dediees, cet ecran n'en donne que la mesure.
        $kpis = [
            'remuneration_mois'   => $monthlyTotal,
            'remuneration_due'    => $totalToPay,
            'rapports_en_attente' => $pendingReports->count(),
            'rapports_effectues'  => ClassSession::where('coach_id', $coach->id)->has('sessionReport')->count(),
            'prochains_cours'     => ClassSession::where('coach_id', $coach->id)
                                        ->where('start_time', '>=', now())
                                        ->where('status', '!=', 'cancelled')
                                        ->count(),
            'programmes'          => $programmes->count(),
            'classes_actives'     => $activeClasses->count(),
            'devoirs_a_corriger'  => \App\Models\Submission::whereHas('assignment', fn ($q) => $q->where('coach_id', $coach->id))
                                        ->doesntHave('grade')
                                        ->count(),
        ];

        return view('coach.dashboard', compact('kpis','upcomingSessions', 'pendingReports', 'completedReports', 'activeClasses', 'monthlyTotal', 'totalToPay', 'programmes'));
    }
}
