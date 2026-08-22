<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ClassSession;
use App\Models\SessionQuota;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait GuardsSessionQuota
{
    /**
     * Bloque la creation ou le deplacement d'une session vers un mois dont le
     * quota de la classe est atteint. Sans quota saisi pour ce mois, aucun
     * blocage : c'est le comportement par defaut tant que l'admin n'a rien
     * renseigne.
     *
     * $sessionExclue retire la session en cours d'edition du comptage : sans
     * cela, deplacer une session existante (meme horaire, meme mois) la
     * bloquerait elle-meme.
     */
    protected function verifierQuotaSession(int $courseClassId, string $startTime, ?int $sessionExclue = null): void
    {
        $debut = Carbon::parse($startTime)->startOfMonth();
        $fin = $debut->copy()->endOfMonth();

        $quota = SessionQuota::where('course_class_id', $courseClassId)
            ->where('year', $debut->year)
            ->where('month', $debut->month)
            ->value('quota');

        if ($quota === null) {
            return;
        }

        $genere = ClassSession::where('course_class_id', $courseClassId)
            ->whereBetween('start_time', [$debut, $fin])
            ->where('status', '!=', 'cancelled')
            ->when($sessionExclue, fn ($q) => $q->where('id', '!=', $sessionExclue))
            ->count();

        if ($genere >= $quota) {
            throw ValidationException::withMessages([
                'course_class_id' => sprintf(
                    'Quota de sessions atteint pour cette classe en %s (%d/%d programmées) : impossible d\'ajouter ou de déplacer une session sur ce mois.',
                    $debut->translatedFormat('F Y'),
                    $genere,
                    $quota
                ),
            ]);
        }
    }
}
