<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CourseClass;
use Illuminate\Support\Facades\Auth;

/**
 * Pages de l'espace formateur qui vivaient jusqu'ici en sections du tableau
 * de bord. Les sortir en pages dediees evite que l'information se disperse
 * dans une page unique de plus en plus longue.
 */
class EspaceController extends Controller
{
    /** Programmes sur lesquels le formateur intervient. */
    public function programmes()
    {
        $programmes = $this->classesDuCoach()
            ->filter(fn ($classe) => $classe->level?->program)
            ->groupBy(fn ($classe) => $classe->level->program->id)
            ->map(fn ($classes) => (object) [
                'programme' => $classes->first()->level->program,
                'classes'   => $classes->pluck('name')->unique()->values(),
            ])
            ->sortBy(fn ($e) => $e->programme->name)
            ->values();

        return view('coach.espace.programmes', compact('programmes'));
    }

    /** Classes actives, avec le volume de seances de chacune. */
    public function classes()
    {
        $coachId = Auth::id();

        $classes = $this->classesDuCoach()->map(function ($classe) use ($coachId) {
            $seances = ClassSession::where('course_class_id', $classe->id)
                ->where('coach_id', $coachId);

            $classe->nb_seances   = (clone $seances)->count();
            $classe->nb_effectuees = (clone $seances)->whereIn('status', ['completed', 'validated'])->count();
            $classe->nb_apprenants = $classe->users->where('pivot.role', 'student')->count();

            return $classe;
        })->sortBy('name')->values();

        return view('coach.espace.classes', compact('classes'));
    }

    /** Prochaines seances, au-dela des cinq du tableau de bord. */
    public function prochainsCours()
    {
        $seances = ClassSession::with(['courseClass.level.program'])
            ->where('coach_id', Auth::id())
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->paginate(20);

        return view('coach.espace.prochains-cours', compact('seances'));
    }

    /** Classes ou le formateur a au moins une seance. */
    private function classesDuCoach()
    {
        return CourseClass::whereHas('classSessions', fn ($q) => $q->where('coach_id', Auth::id()))
            ->with(['level.program', 'users'])
            ->get();
    }
}
