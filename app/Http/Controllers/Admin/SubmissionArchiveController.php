<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Models\CourseClass;
use App\Models\Program;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Archive des devoirs rendus par les apprenants.
 *
 * Consultation seule : l'administrateur consulte et filtre, il ne modifie
 * jamais un rendu ni une note. La correction reste du ressort du coach.
 */
class SubmissionArchiveController extends Controller
{
    use ExportsCsv;

    /**
     * Construit la requete des rendus filtree par les criteres de l'URL,
     * sans pagination : utilisee telle quelle par l'ecran et par l'export CSV.
     */
    private function filtrerRendus(Request $request): Builder
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

        return $query;
    }

    public function index(Request $request)
    {
        $submissions = $this->filtrerRendus($request)->paginate(20)->appends($request->all());

        return view('admin.submissions.index', [
            'submissions' => $submissions,
            'classes'     => CourseClass::orderBy('name')->get(),
            'coaches'     => User::role('coach')->orderBy('name')->get(),
            'students'    => User::role('student')->orderBy('name')->get(),
            'programs'    => Program::orderBy('name')->get(),
        ]);
    }

    /**
     * Export CSV des rendus correspondant aux filtres actifs.
     * Meme requete que l'ecran, sans pagination.
     */
    public function export(Request $request)
    {
        $submissions = $this->filtrerRendus($request)->get();

        $lignes = $submissions->map(fn (Submission $s) => [
            $s->created_at->format('d/m/Y H:i'),
            $s->student->name ?? '—',
            $s->assignment->title ?? '—',
            $s->assignment->courseClass->name ?? '—',
            $s->assignment->coach->name ?? '—',
            $s->grade ? rtrim(rtrim(number_format($s->grade->score, 1, ',', ''), '0'), ',').'/20' : 'En attente',
        ]);

        return $this->streamCsv('devoirs_archives.csv', ['Rendu le', 'Apprenant', 'Devoir', 'Classe', 'Formateur', 'Note'], $lignes);
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
