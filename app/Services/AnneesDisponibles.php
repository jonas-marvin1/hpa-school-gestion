<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Construit la liste des annees proposees dans les filtres de l'application.
 *
 * Trois exigences se combinent ici :
 *
 * 1. Aucune annee contenant des donnees ne doit manquer, meme ancienne.
 * 2. L'annee en cours doit toujours etre proposee, meme sans donnee.
 * 3. Les annees a venir doivent l'etre aussi : en decembre, on planifie deja
 *    l'annee suivante, et un filtre qui s'arrete a l'annee en cours empeche
 *    de consulter ce qui vient d'etre saisi.
 *
 * Rien n'est ecrit en dur : la liste est recalculee a chaque affichage a
 * partir de la date du jour. Le 1er janvier, la nouvelle annee apparait
 * d'elle-meme dans tous les filtres, sans intervention ni tache planifiee.
 */
class AnneesDisponibles
{
    /**
     * Nombre d'annees proposees au-dela de l'annee en cours.
     *
     * Une seule suffit au rythme d'une ecole : on prepare la rentree suivante,
     * rarement au-dela. Augmenter cette valeur elargit tous les filtres de
     * l'application d'un coup.
     */
    public const EN_AVANCE = 1;

    /**
     * Annees deduites d'une colonne entiere, par exemple « payments.year ».
     */
    public static function depuisColonneAnnee(Builder $requete, string $colonne = 'year'): Collection
    {
        $annees = $requete->clone()
            ->reorder()
            ->select($colonne)
            ->distinct()
            ->whereNotNull($colonne)
            ->pluck($colonne)
            ->map(fn ($annee) => (int) $annee);

        return self::completer($annees);
    }

    /**
     * Annees deduites d'une colonne date, par exemple « class_sessions.start_time ».
     *
     * On lit la date la plus ancienne et la plus recente, puis on deroule
     * l'intervalle en PHP. Extraire l'annee en SQL demanderait une requete par
     * moteur (YEAR() sur MySQL, strftime() sur SQLite) pour un resultat
     * identique : deux agregats couvrent toute la periode.
     */
    public static function depuisColonneDate(Builder $requete, string $colonne): Collection
    {
        $base = $requete->clone()->reorder();

        $premiere = $base->clone()->min($colonne);
        $derniere = $base->clone()->max($colonne);

        $annees = collect();

        if ($premiere && $derniere) {
            $annees = collect(range(
                Carbon::parse($premiere)->year,
                Carbon::parse($derniere)->year,
            ));
        }

        return self::completer($annees);
    }

    /**
     * Ajoute l'annee en cours et les annees a venir, puis ordonne la liste de
     * la plus recente a la plus ancienne.
     */
    private static function completer(Collection $annees): Collection
    {
        $courante = now()->year;

        return $annees
            ->merge(range($courante, $courante + self::EN_AVANCE))
            ->unique()
            ->sortDesc()
            ->values();
    }
}
