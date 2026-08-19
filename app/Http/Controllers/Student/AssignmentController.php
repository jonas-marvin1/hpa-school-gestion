<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Assignment;
use App\Models\Submission;
use App\Rules\FichierAudio;
use App\Notifications\SubmissionReceivedNotification;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function index()
    {
        $student = Auth::user();
        $classIds = $student->courseClasses->pluck('id');

        $assignments = Assignment::with(['courseClass', 'submissions' => function($q) use ($student) {
                $q->where('student_id', $student->id)->with('grade');
            }])
            ->whereIn('course_class_id', $classIds)
            ->orderBy('due_date', 'asc')
            ->get();

        return view('student.assignments.index', compact('assignments'));
    }

    public function show(Assignment $assignment)
    {
        $student = Auth::user();
        
        // Ensure student is in the class
        if (!$student->courseClasses->contains($assignment->course_class_id)) {
            abort(403, 'Vous n\'avez pas accès à ce devoir.');
        }

        $submission = Submission::with('grade')->where('assignment_id', $assignment->id)->where('student_id', $student->id)->first();

        return view('student.assignments.show', compact('assignment', 'submission'));
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $student = Auth::user();

        // Ensure student is in the class
        if (!$student->courseClasses->contains($assignment->course_class_id)) {
            abort(403, 'Vous n\'avez pas accès à ce devoir.');
        }

        // Ensure no previous submission exists
        if (Submission::where('assignment_id', $assignment->id)->where('student_id', $student->id)->exists()) {
            return back()->withErrors('Vous avez déjà soumis ce devoir.');
        }

        $rules = [];
        if ($assignment->type === 'text') {
            $rules['content'] = 'required|string';
        } elseif ($assignment->type === 'link') {
            $rules['content'] = 'required|url';
        } elseif ($assignment->type === 'audio') {
            // 25 Mo couvrent environ 25 minutes en qualite courante.
            // La regle FichierAudio remplace « mimetypes: » : les
            // enregistrements du navigateur ne declarent pas « audio/webm »
            // et etaient donc refuses a tort.
            $rules['file'] = ['required', 'file', 'max:25600', new FichierAudio()];
        } else {
            $rules['file'] = 'required|file|max:10240'; // 10MB max
        }

        $request->validate($rules);

        $submissionData = [
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'content_text' => $request->content ?? null,
            'file_path' => null, // Storage logic could go here
            'submitted_at' => now(),
        ];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('submissions', 'public');
            $submissionData['file_path'] = $path;
        }

        $submission = Submission::create($submissionData);

        $this->avertirLeFormateur($assignment, $submission, $student);

        return redirect()->route('student.assignments.show', $assignment)->with('status', 'Devoir soumis avec succès.');
    }

    /**
     * Previent le formateur du devoir qu'un rendu attend sa correction.
     *
     * Le compteur de rendus non corriges est calcule ici : le formateur voit
     * d'un coup d'oeil l'ampleur de ce qui l'attend, plutot que de recevoir
     * une alerte isolee sans contexte.
     */
    private function avertirLeFormateur(Assignment $assignment, Submission $submission, $student): void
    {
        $coach = $assignment->coach;

        if (! $coach) {
            return;
        }

        $enAttente = Submission::where('assignment_id', $assignment->id)
            ->doesntHave('grade')
            ->count();

        $coach->notify(new SubmissionReceivedNotification([
            'student_name'     => $student->name,
            'assignment_title' => $assignment->title,
            'assignment_id'    => $assignment->id,
            'submission_id'    => $submission->id,
            'class_name'       => $assignment->courseClass->name ?? 'classe inconnue',
            'pending_count'    => $enAttente,
            'action_url'       => route('coach.evaluations.index', $assignment),
        ]));
    }
}
