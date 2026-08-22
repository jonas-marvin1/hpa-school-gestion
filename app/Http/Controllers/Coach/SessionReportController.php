<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ClassSession;
use App\Models\SessionReport;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class SessionReportController extends Controller
{
    public function index(Request $request)
    {
        $coachId = Auth::id();

        // La liste ne montrait que les rapports deja rediges. Le coach doit aussi
        // voir ceux qui lui restent a faire, c'est-a-dire les seances passees
        // encore sans rapport (meme critere que le tableau de bord).
        $query = ClassSession::with(['courseClass', 'sessionReport', 'attendances'])
            ->where('coach_id', $coachId)
            ->where(function ($q) {
                $q->has('sessionReport')
                  ->orWhere(function ($p) {
                      $p->doesntHave('sessionReport')
                        ->where('start_time', '<', now());
                  });
            })
            ->orderBy('start_time', 'desc');

        if ($request->filled('month')) {
            $query->whereMonth('start_time', $request->month);
        }
        // Sans ce filtre, choisir un mois remontait ce mois de toutes les
        // annees confondues : aout 2026 et aout 2027 dans la meme liste.
        if ($request->filled('year')) {
            $query->whereYear('start_time', $request->year);
        }
        if ($request->filled('class_id')) {
            $query->where('course_class_id', $request->class_id);
        }
        if ($request->status === 'done') {
            $query->has('sessionReport');
        } elseif ($request->status === 'todo') {
            $query->doesntHave('sessionReport');
        }

        $reports = $query->paginate(15)->appends($request->all());

        $classes = \App\Models\CourseClass::whereHas('classSessions', function ($q) use ($coachId) {
            $q->where('coach_id', $coachId);
        })->get();

        // Annees proposees par le filtre : celles ou le coach a des seances,
        // l'annee en cours et les annees a venir.
        $years = \App\Services\AnneesDisponibles::depuisColonneDate(
            ClassSession::where('coach_id', $coachId),
            'start_time'
        );

        return view('coach.reports.index', compact('reports', 'classes', 'years'));
    }

    public function create(ClassSession $session)
    {
        // Ensure the coach owns this session
        if ($session->coach_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Marge de tolerance definie sur le modele (ClassSession::MARGE_RAPPORT_MINUTES),
        // pour que les deux points d'entree du rapport partagent la meme regle.
        if (! $session->fenetreRapportOuverte()) {
            return redirect()->route('coach.dashboard')->with('error', 'Vous ne pouvez remplir le rapport de présence qu\'entre ' . $session->debutFenetreRapport()->format('H:i') . ' et ' . $session->finFenetreRapport()->format('H:i') . ' (' . ClassSession::MARGE_RAPPORT_MINUTES . ' min avant/après le cours).');
        }

        // Get the students assigned to the class
        $students = $session->courseClass->users()->role('student')->get();

        return view('coach.sessions.report', compact('session', 'students'));
    }

    public function store(Request $request, ClassSession $session)
    {
        // Ensure the coach owns this session
        if ($session->coach_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Marge de tolerance definie sur le modele (ClassSession::MARGE_RAPPORT_MINUTES),
        // pour que les deux points d'entree du rapport partagent la meme regle.
        if (! $session->fenetreRapportOuverte()) {
            return redirect()->route('coach.dashboard')->with('error', 'Vous ne pouvez remplir le rapport de présence qu\'entre ' . $session->debutFenetreRapport()->format('H:i') . ' et ' . $session->finFenetreRapport()->format('H:i') . ' (' . ClassSession::MARGE_RAPPORT_MINUTES . ' min avant/après le cours).');
        }

        $validated = $request->validate([
            'progress_summary' => 'required|string',
            'observations' => 'nullable|string',
            'attendances' => 'array',
            'attendances.*' => 'boolean'
        ]);

        // Create Report
        SessionReport::create([
            'class_session_id' => $session->id,
            'progress' => $validated['progress_summary'],
            'observations' => $validated['observations'] ?? null,
        ]);

        // Mark session as completed
        $session->update(['status' => 'completed']);
        // Process Attendances
        $students = $session->courseClass->users()->role('student')->get();
        foreach ($students as $student) {
            $isPresent = isset($validated['attendances'][$student->id]) && $validated['attendances'][$student->id];
            Attendance::create([
                'class_session_id' => $session->id,
                'student_id' => $student->id,
                'is_present' => $isPresent,
            ]);
        }

        return redirect()->route('coach.dashboard')->with('status', 'Le rapport de session et les présences ont été enregistrés.');
    }
}
