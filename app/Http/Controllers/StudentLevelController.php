<?php

namespace App\Http\Controllers;

use App\Models\EnglishLevel;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Positionnement et progression de niveau d'un apprenant.
 *
 * La promotion est manuelle : coach et administrateur peuvent la decider.
 * Aucun automatisme ne fait monter un apprenant de palier.
 */
class StudentLevelController extends Controller
{
    public function edit(User $student)
    {
        $this->garantirApprenant($student);

        return view('levels.edit', [
            'student'   => $student,
            'echelle'   => EnglishLevel::echelle(),
            'historique' => $this->historiqueNiveau($student),
        ]);
    }

    public function update(Request $request, User $student)
    {
        $this->garantirApprenant($student);

        $valide = $request->validate([
            'english_level_id' => 'required|exists:english_levels,id',
        ]);

        $ancien = $student->englishLevel;
        $nouveau = EnglishLevel::findOrFail($valide['english_level_id']);

        if ($ancien && $ancien->id === $nouveau->id) {
            return back()->with('status', 'Ce niveau est déjà celui de l\'apprenant.');
        }

        $student->changerNiveau($nouveau);

        $message = $ancien
            ? "Niveau modifié : {$ancien->code} → {$nouveau->code}."
            : "Niveau initial attribué : {$nouveau->code}.";

        return redirect()
            ->route('students.level.edit', $student)
            ->with('status', $message);
    }

    /** Seuls les apprenants ont un niveau d'anglais. */
    private function garantirApprenant(User $student): void
    {
        abort_unless($student->hasRole('student'), 404);
    }

    /**
     * Historique de progression, extrait du journal des modifications.
     * Seules les lignes touchant le niveau sont retenues.
     */
    private function historiqueNiveau(User $student)
    {
        $codes = EnglishLevel::pluck('code', 'id');

        return Revision::where('revisable_type', User::class)
            ->where('revisable_id', $student->id)
            ->where('action', 'updated')
            ->with('user')
            ->latest('created_at')
            ->get()
            ->filter(fn ($r) => isset($r->changes['english_level_id']))
            ->map(function ($r) use ($codes) {
                $c = $r->changes['english_level_id'];

                return (object) [
                    'date'   => $r->created_at,
                    'auteur' => $r->user->name ?? 'traitement automatique',
                    'avant'  => $codes[$c['before']] ?? null,
                    'apres'  => $codes[$c['after']] ?? null,
                ];
            })
            ->values();
    }
}
