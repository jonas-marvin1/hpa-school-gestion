<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassSession;
use App\Models\Submission;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    public function index(Assignment $assignment)
    {
        // Sans ce controle, un coach pouvait ouvrir par URL les rendus d'une
        // classe qui n'est pas la sienne : meme regle que Assignment::index().
        $classIds = ClassSession::where('coach_id', Auth::id())->pluck('course_class_id')->unique();
        abort_unless($classIds->contains($assignment->course_class_id), 403);

        // Get all students for this assignment's class
        $students = $assignment->courseClass->users()->role('student')->get();
        
        // Get all submissions for this assignment with grades
        $submissions = Submission::with('grade')->where('assignment_id', $assignment->id)->get()->keyBy('student_id');

        return view('coach.evaluations.index', compact('assignment', 'students', 'submissions'));
    }

    public function store(Request $request, Submission $submission)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:20',
            'feedback' => 'nullable|string',
        ]);

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
