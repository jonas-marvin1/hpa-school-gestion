<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\ClassSession;
use App\Models\Attendance;
use App\Models\PaymentPlan;
use App\Models\Submission;

class DashboardController extends Controller
{
    public function index()
    {
        $student = Auth::user();
        
        // Find classes the student is enrolled in
        $classes = $student->courseClasses;
        $classIds = $classes->pluck('id');
        
        // Upcoming sessions
        $upcomingSessions = ClassSession::with(['courseClass', 'coach'])
            ->whereIn('course_class_id', $classIds)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->take(5)
            ->get();
            
        // Recent grades
        $recentGrades = Submission::with(['assignment', 'grade'])
            ->where('student_id', $student->id)
            ->whereHas('grade')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        // Attendance stats
        $totalSessions = Attendance::where('student_id', $student->id)->count();
        $presentSessions = Attendance::where('student_id', $student->id)->where('is_present', true)->count();
        $attendanceRate = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100) : 100;

        // Sessions pending feedback
        $pendingFeedbacks = ClassSession::with('courseClass')
            ->whereHas('attendances', function($q) use ($student) {
                $q->where('student_id', $student->id)
                  ->where('is_present', true)
                  ->whereNull('feedback');
            })
            ->where('status', '!=', 'completed')
            ->orderBy('start_time', 'desc')
            ->get();

        // Prochain paiement
        $nextPayment = \App\Models\StudentPayment::where('student_id', $student->id)
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->first();

        // Progression paiement
        $totalPayments = \App\Models\StudentPayment::where('student_id', $student->id)->count();
        $paidPayments = \App\Models\StudentPayment::where('student_id', $student->id)->where('status', 'paid')->count();
        $paymentProgress = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;

        // --- Progression pedagogique (affichee sous forme de jauges) ---

        // Avancement du cursus : seances deja tenues sur l'ensemble des seances
        // programmees pour les classes de l'apprenant.
        $sessionsTotal = ClassSession::whereIn('course_class_id', $classIds)
            ->where('status', '!=', 'cancelled')
            ->count();
        $sessionsDone = ClassSession::whereIn('course_class_id', $classIds)
            ->whereIn('status', ['completed', 'validated'])
            ->count();
        $courseProgress = $sessionsTotal > 0 ? round(($sessionsDone / $sessionsTotal) * 100) : 0;

        // Devoirs rendus sur devoirs demandes.
        $assignmentIds = \App\Models\Assignment::whereIn('course_class_id', $classIds)->pluck('id');
        $assignmentsTotal = $assignmentIds->count();
        $assignmentsDone = Submission::where('student_id', $student->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->count();
        $assignmentProgress = $assignmentsTotal > 0 ? round(($assignmentsDone / $assignmentsTotal) * 100) : 0;

        // Moyenne generale, sur 20.
        $averageGrade = \App\Models\Grade::whereHas('submission', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->avg('score');

        // Programmes suivis, deduits des classes de l'apprenant. Regroupes au
        // cas ou il serait inscrit a plusieurs classes d'un meme programme.
        $programmes = $classes->load('level.program')
            ->filter(fn ($classe) => $classe->level?->program)
            ->groupBy(fn ($classe) => $classe->level->program->id)
            ->map(fn ($grp) => (object) [
                'programme' => $grp->first()->level->program,
                'classes'   => $grp->pluck('name')->unique()->values(),
            ])
            ->sortBy(fn ($e) => $e->programme->name)
            ->values();

        // Niveau d'anglais et echelle complete, pour la frise de progression.
        $echelleNiveaux = \App\Models\EnglishLevel::echelle();
        $niveauActuel   = $student->englishLevel;

        // Solde du : regle metier, annuler une echeance ne change pas la dette
        // (voir docs/fiches/2026-09-14.md, point 1). Sommer les echeances
        // "pending" la ferait baisser a chaque annulation ; sommer aussi les
        // "cancelled" la compterait deux fois une fois la somme replanifiee
        // dans une nouvelle echeance. Le solde doit donc venir du contrat.
        $plan = PaymentPlan::where('student_id', $student->id)->latest('id')->first();

        if ($plan) {
            // Cas courant : le solde vient du plan (total - avance - regle),
            // exactement le calcul deja affiche a l'administrateur sur la
            // page du plan. Les deux ecrans disent ainsi la meme chose par
            // construction, pas par coincidence.
            $soldeDu = $plan->soldeRestant();
        } else {
            // Echeances isolees, non rattachees a un plan de paiement
            // (payment_plan_id nullable) : il n'y a pas de contrat global a
            // partir duquel calculer un solde, on retombe sur la somme des
            // echeances encore en attente.
            $soldeDu = (float) \App\Models\StudentPayment::where('student_id', $student->id)
                ->where('status', 'pending')
                ->sum('amount');
        }

        // Indicateurs du tableau de bord apprenant : meme logique que cote
        // formateur, le detail vit dans les pages de la rubrique « Ma formation ».
        $kpis = [
            'niveau'              => $niveauActuel?->code,
            'progression_cursus'  => $courseProgress,
            'devoirs_a_rendre'    => max(0, $assignmentsTotal - $assignmentsDone),
            'devoirs_rendus'      => $assignmentsDone,
            'moyenne'             => $averageGrade !== null ? round($averageGrade, 1) : null,
            'assiduite'           => $attendanceRate,
            'prochains_cours'     => ClassSession::whereIn('course_class_id', $classIds)
                                        ->where('start_time', '>=', now())
                                        ->where('status', '!=', 'cancelled')
                                        ->count(),
            'programmes'          => $programmes->count(),
            'solde_du'            => $soldeDu,
        ];

        return view('student.dashboard', compact('kpis',
            'echelleNiveaux', 'niveauActuel', 'programmes',
            'upcomingSessions', 'recentGrades', 'attendanceRate', 'totalSessions',
            'pendingFeedbacks', 'nextPayment', 'paymentProgress',
            'courseProgress', 'sessionsDone', 'sessionsTotal',
            'assignmentProgress', 'assignmentsDone', 'assignmentsTotal',
            'averageGrade'
        ));
    }
}
