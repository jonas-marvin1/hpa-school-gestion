<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Assignment;
use App\Models\User;
use App\Services\ProlongationDelaiEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Action « Prolonger le delai », partagee entre Coach\AssignmentController
 * et Manager\AssignmentController (fiche du 14/09/2026, point 3), sur le
 * meme principe que GereAttributionApprenant.
 *
 * Un seul point d'entree pour la prolongation collective (sans student_id
 * dans la requete) et individuelle (avec) : c'est la meme fenetre cote
 * interface, qui ne differe que par ce champ.
 */
trait ProlongeDelaiEvaluation
{
    public function prolonger(Request $request, Assignment $assignment)
    {
        Gate::authorize('prolongerDelai', $assignment);

        $validated = $request->validate([
            'student_id' => 'nullable|exists:users,id',
            'new_due_date' => 'required|date',
            'motif' => 'required|string|max:1000',
        ], [], [
            'new_due_date' => 'nouvelle date limite',
            'motif' => 'motif',
        ]);

        $student = $validated['student_id'] ?? null
            ? User::findOrFail($validated['student_id'])
            : null;

        $resultat = app(ProlongationDelaiEvaluation::class)->accorder(
            $assignment,
            $student,
            Carbon::parse($validated['new_due_date']),
            $validated['motif'],
            Auth::user(),
        );

        $message = $student
            ? "Délai prolongé pour {$student->name} jusqu'au {$resultat['date']->format('d/m/Y à H:i')} — apprenant notifié."
            : "Délai prolongé jusqu'au {$resultat['date']->format('d/m/Y à H:i')} — {$resultat['notifies']} apprenant(s) notifié(s).";

        return back()->with('status', $message);
    }
}
