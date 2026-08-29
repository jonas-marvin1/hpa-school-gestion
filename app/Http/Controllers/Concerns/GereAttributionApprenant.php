<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CourseClass;
use Illuminate\Http\Request;

/**
 * Attribution individuelle d'une evaluation a un apprenant precis.
 *
 * Partage entre Coach\AssignmentController et Manager\AssignmentController,
 * qui offrent tous deux la meme capacite d'attribution (point 1, fiche du
 * 27/08/2026).
 */
trait GereAttributionApprenant
{
    /**
     * Classes avec leurs apprenants preattaches, pour que la vue puisse
     * filtrer en Alpine.js la liste proposee selon la classe choisie, sans
     * requete supplementaire au changement de selection.
     */
    private function classesAvecApprenants(?iterable $classIds = null)
    {
        $query = CourseClass::query();

        if ($classIds !== null) {
            $query->whereIn('id', $classIds);
        }

        return $query->with(['users' => fn ($q) => $q->role('student')])->get();
    }

    /**
     * Un apprenant attribue nominativement doit appartenir a la classe
     * choisie : sans ce controle, une requete forgee attribuerait un devoir
     * a un apprenant d'une autre classe.
     */
    private function regleApprenantDeLaClasse(Request $request)
    {
        return function (string $attribute, $value, \Closure $fail) use ($request) {
            if (blank($value)) {
                return;
            }

            $classe = CourseClass::find($request->course_class_id);
            $appartient = $classe && $classe->users()->role('student')->where('users.id', $value)->exists();

            if (! $appartient) {
                $fail("L'apprenant sélectionné n'appartient pas à la classe choisie.");
            }
        };
    }
}
