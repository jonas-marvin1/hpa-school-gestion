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
            'student'    => $student,
            'echelle'    => EnglishLevel::echelle(),
            'historique' => $this->historiqueNiveau($student),
            'peutCorriger' => Auth::user()->hasRole('admin'),
        ]);
    }

    public function update(Request $request, User $student)
    {
        $this->garantirApprenant($student);

        $valide = $request->validate([
            'english_level_id' => 'required|exists:english_levels,id',
            'correction'       => 'sometimes|boolean',
        ]);

        $ancien = $student->englishLevel;
        $nouveau = EnglishLevel::findOrFail($valide['english_level_id']);

        if ($ancien && $ancien->id === $nouveau->id) {
            return back()->with('status', 'Ce niveau est déjà celui de l\'apprenant.');
        }

        // Seul l'admin peut marquer une correction : un coach qui forgerait
        // le champ ne doit pas pouvoir effacer une etape de l'historique.
        $correction = $request->boolean('correction') && Auth::user()->hasRole('admin');

        $student->changerNiveau($nouveau, $correction);

        $message = $correction
            ? "Niveau corrigé : {$nouveau->code}. L'ancienne valeur ne figure plus dans l'historique."
            : ($ancien
                ? "Niveau modifié : {$ancien->code} → {$nouveau->code}."
                : "Niveau initial attribué : {$nouveau->code}.");

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
     *
     * Une correction efface les etapes anterieures : le niveau errone
     * qu'elle remplace ne doit plus apparaitre comme une progression valide
     * (point 4 de la fiche). La liste etant du plus recent au plus ancien,
     * on s'arrete a la premiere correction rencontree et on ignore tout ce
     * qui la precede.
     */
    private function historiqueNiveau(User $student)
    {
        $codes = EnglishLevel::pluck('code', 'id');

        $entrees = Revision::where('revisable_type', User::class)
            ->where('revisable_id', $student->id)
            ->where('action', 'updated')
            ->with('user')
            ->latest('created_at')
            ->get()
            ->filter(fn ($r) => isset($r->changes['english_level_id']))
            ->values();

        $indexCorrection = $entrees->search(fn ($r) => $r->is_correction);

        if ($indexCorrection !== false) {
            $entrees = $entrees->slice(0, $indexCorrection + 1)->values();
        }

        return $entrees->map(function ($r) use ($codes) {
            $c = $r->changes['english_level_id'];

            return (object) [
                'date'       => $r->created_at,
                'auteur'     => $r->user->name ?? 'traitement automatique',
                // Une correction masque son propre "avant" : c'est justement
                // la valeur erronee qui ne doit plus apparaitre.
                'avant'      => $r->is_correction ? null : ($codes[$c['before']] ?? null),
                'apres'      => $codes[$c['after']] ?? null,
                'correction' => $r->is_correction,
            ];
        });
    }
}
