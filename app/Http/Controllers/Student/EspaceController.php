<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\EnglishLevel;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;

/**
 * Pages de l'espace apprenant, extraites du tableau de bord pour que chaque
 * sujet ait sa page plutot qu'un empilement de blocs.
 */
class EspaceController extends Controller
{
    /** Programme suivi et son contenu detaille. */
    public function programme()
    {
        $programmes = Auth::user()->courseClasses()
            ->with('level.program')
            ->get()
            ->filter(fn ($classe) => $classe->level?->program)
            ->groupBy(fn ($classe) => $classe->level->program->id)
            ->map(fn ($grp) => (object) [
                'programme' => $grp->first()->level->program,
                'classes'   => $grp->pluck('name')->unique()->values(),
                'niveau'    => $grp->first()->level->name ?? null,
            ])
            ->values();

        return view('student.espace.programme', compact('programmes'));
    }

    /** Emploi du temps : seances a venir et seances passees. */
    public function emploiDuTemps()
    {
        $classIds = Auth::user()->courseClasses->pluck('id');

        $aVenir = ClassSession::with(['courseClass', 'coach'])
            ->whereIn('course_class_id', $classIds)
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();

        // Les seances passees sont paginees : leur nombre croit sans limite,
        // contrairement a celles a venir.
        $passees = ClassSession::with(['courseClass', 'coach'])
            ->whereIn('course_class_id', $classIds)
            ->where('start_time', '<', now())
            ->orderByDesc('start_time')
            ->paginate(15);

        return view('student.espace.emploi-du-temps', compact('aVenir', 'passees'));
    }

    /** Progression : niveau d'anglais, cursus, devoirs, assiduite. */
    public function progression()
    {
        $etudiant = Auth::user();
        $classIds = $etudiant->courseClasses->pluck('id');

        $sessionsTotal = ClassSession::whereIn('course_class_id', $classIds)->where('status', '!=', 'cancelled')->count();
        $sessionsDone  = ClassSession::whereIn('course_class_id', $classIds)->whereIn('status', ['completed', 'validated'])->count();

        $assignmentIds    = Assignment::whereIn('course_class_id', $classIds)->pluck('id');
        $assignmentsTotal = $assignmentIds->count();
        $assignmentsDone  = Submission::where('student_id', $etudiant->id)->whereIn('assignment_id', $assignmentIds)->count();

        $totalSessions   = Attendance::where('student_id', $etudiant->id)->count();
        $presentSessions = Attendance::where('student_id', $etudiant->id)->where('is_present', true)->count();

        return view('student.espace.progression', [
            'echelleNiveaux'     => EnglishLevel::echelle(),
            'niveauActuel'       => $etudiant->englishLevel,
            'courseProgress'     => $sessionsTotal > 0 ? (int) round($sessionsDone / $sessionsTotal * 100) : 0,
            'sessionsDone'       => $sessionsDone,
            'sessionsTotal'      => $sessionsTotal,
            'assignmentProgress' => $assignmentsTotal > 0 ? (int) round($assignmentsDone / $assignmentsTotal * 100) : 0,
            'assignmentsDone'    => $assignmentsDone,
            'assignmentsTotal'   => $assignmentsTotal,
            'attendanceRate'     => $totalSessions > 0 ? (int) round($presentSessions / $totalSessions * 100) : 100,
            'totalSessions'      => $totalSessions,
            'averageGrade'       => Grade::whereHas('submission', fn ($q) => $q->where('student_id', $etudiant->id))->avg('score'),
            'notes'              => Submission::with(['assignment', 'grade'])
                                        ->where('student_id', $etudiant->id)
                                        ->whereHas('grade')
                                        ->latest('created_at')
                                        ->get(),
        ]);
    }
}
