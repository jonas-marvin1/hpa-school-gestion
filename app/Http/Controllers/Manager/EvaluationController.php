<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    public function index(Assignment $assignment)
    {
        // La gestionnaire a le controle complet, sur toutes les classes :
        // aucune restriction d'appartenance a verifier ici, contrairement au
        // coach (Coach\EvaluationController).
        // Devoir nominatif : seul l'apprenant vise apparait.
        $students = $assignment->student_id
            ? collect([$assignment->student])->filter()
            : $assignment->courseClass->users()->role('student')->get();

        $submissions = Submission::with('grade')->where('assignment_id', $assignment->id)->get()->keyBy('student_id');

        return view('manager.evaluations.index', compact('assignment', 'students', 'submissions'));
    }

    public function store(Request $request, Submission $submission)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:20',
            'feedback' => 'nullable|string',
        ]);

        // Correcteur : voir dette technique CLAUDE.md sur le nommage de
        // cette colonne, partagee avec le coach.
        Grade::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'coach_id' => Auth::id(),
            ],
            [
                'score' => $validated['score'],
                'feedback' => $validated['feedback'],
            ]
        );

        return back()->with('status', 'Note enregistrée avec succès.');
    }
}
