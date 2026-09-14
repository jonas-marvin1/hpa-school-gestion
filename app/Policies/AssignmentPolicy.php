<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

/**
 * Habilitations propres aux devoirs (Assignment), regroupees ici plutot que
 * dispersees dans les controleurs (fiche du 14/09/2026, point 3, §4.6).
 */
class AssignmentPolicy
{
    /**
     * Prolonger le delai d'une evaluation : l'administration et la
     * gestionnaire peuvent le faire sur n'importe quel devoir, y compris
     * ceux crees par un coach (meme regle que la modification et la
     * suppression, deja ouvertes a la gestionnaire sur tout le module).
     * Le coach ne peut prolonger que les devoirs dont il est l'auteur.
     */
    public function prolongerDelai(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('manager') || $user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('coach') && $assignment->coach_id === $user->id;
    }
}
