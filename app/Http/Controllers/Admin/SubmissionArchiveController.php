<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Program;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Archive des devoirs rendus par les apprenants.
 *
 * Consultation seule : l'administrateur consulte et filtre, il ne modifie
 * jamais un rendu ni une note. La correction reste du ressort du coach.
 */
class SubmissionArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = Submission::with([
            'student',
            'assignment.courseClass.level.program',
            'assignment.coach',
            'grade.coach',
        ])->latest('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            // Groupe indispensable : sans lui, les filtres ajoutes ensuite
            // seraient avales par le OR.
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('assignment', fn ($a) => $a->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('assignment', fn ($a) => $a->where('course_class_id', $request->class_id));
        }

        if ($request->filled('coach_id')) {
            $query->whereHas('assignment', fn ($a) => $a->where('coach_id', $request->coach_id));
        }

        if ($request->filled('program_id')) {
            $query->whereHas(
                'assignment.courseClass.level',
                fn ($l) => $l->where('program_id', $request->program_id)
            );
        }

        if ($request->filled('graded')) {
            $request->graded === 'yes'
                ? $query->has('grade')
                : $query->doesntHave('grade');
        }

        $submissions = $query->paginate(20)->appends($request->all());

        return view('admin.submissions.index', [
            'submissions' => $submissions,
            'classes'     => CourseClass::orderBy('name')->get(),
            'coaches'     => User::role('coach')->orderBy('name')->get(),
            'students'    => User::role('student')->orderBy('name')->get(),
            'programs'    => Program::orderBy('name')->get(),
        ]);
    }

    public function show(Submission $submission)
    {
        $submission->load([
            'student',
            'assignment.courseClass.level.program',
            'assignment.coach',
            'grade.coach',
            'revisions.user',
            'grade.revisions.user',
        ]);

        // Historique unifie : le rendu et sa note se lisent sur une seule
        // frise chronologique, l'administrateur n'a pas a recoller deux listes.
        $historique = $submission->revisions
            ->map(fn ($r) => ['objet' => 'Rendu', 'revision' => $r])
            ->concat(
                ($submission->grade?->revisions ?? collect())
                    ->map(fn ($r) => ['objet' => 'Note', 'revision' => $r])
            )
            ->sortByDesc(fn ($e) => $e['revision']->created_at)
            ->values();

        return view('admin.submissions.show', compact('submission', 'historique'));
    }
}
