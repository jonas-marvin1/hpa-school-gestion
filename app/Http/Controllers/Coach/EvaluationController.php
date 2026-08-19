<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    public function index(Assignment $assignment)
    {
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
