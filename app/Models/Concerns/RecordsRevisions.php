<?php

namespace App\Models\Concerns;

use App\Models\Revision;
use Illuminate\Support\Facades\Auth;

/**
 * Journalise automatiquement les changements d'un modele.
 *
 * Le suivi passe par les evenements Eloquent plutot que par les controleurs :
 * quel que soit le chemin emprunte pour modifier l'objet (ecran, commande,
 * tinker), la modification est consignee.
 *
 * Le modele peut declarer les champs a ignorer :
 *     protected array $revisionExcept = ['updated_at'];
 */
trait RecordsRevisions
{
    /**
     * Marque la prochaine sauvegarde comme correction d'une erreur de saisie,
     * plutot que comme une evolution legitime. L'appelant positionne ce
     * drapeau juste avant de modifier et sauvegarder le modele ; il est
     * remis a plat une fois la revision enregistree.
     *
     * Propriete declaree ici plutot qu'un attribut de modele : elle ne doit
     * jamais transiter par le mecanisme d'attributs Eloquent, au risque
     * d'etre ecrite en base comme une colonne inexistante.
     */
    public bool $revisionEstCorrection = false;

    public static function bootRecordsRevisions(): void
    {
        static::created(function ($model) {
            $model->enregistrerRevision('created', $model->revisionValeursCreation());
        });

        static::updated(function ($model) {
            $changements = $model->revisionChangements();

            // Un enregistrement touche sans qu'aucun champ suivi ne bouge
            // (simple resauvegarde) ne merite pas une ligne de journal.
            if ($changements !== []) {
                $model->enregistrerRevision('updated', $changements, $model->revisionEstCorrection);
            }

            $model->revisionEstCorrection = false;
        });

        static::deleted(function ($model) {
            $model->enregistrerRevision('deleted', null);
        });
    }

    /**
     * Champs exclus du journal.
     *
     * Les secrets sont exclus d'office : un journal consultable ne doit jamais
     * conserver de hachage de mot de passe ni de jeton, meme sous forme
     * d'ancienne valeur.
     */
    protected function revisionIgnores(): array
    {
        return array_merge(
            [
                'id', 'created_at', 'updated_at',
                'password', 'remember_token', 'two_factor_secret',
                'two_factor_recovery_codes', 'email_verified_at',
            ],
            property_exists($this, 'revisionExcept') ? $this->revisionExcept : []
        );
    }

    /** Valeurs retenues a la creation. */
    protected function revisionValeursCreation(): array
    {
        $valeurs = [];

        foreach ($this->getAttributes() as $champ => $valeur) {
            if (! in_array($champ, $this->revisionIgnores(), true)) {
                $valeurs[$champ] = ['before' => null, 'after' => $valeur];
            }
        }

        return $valeurs;
    }

    /** Couples avant/apres des champs reellement modifies. */
    protected function revisionChangements(): array
    {
        $changements = [];

        foreach ($this->getChanges() as $champ => $apres) {
            if (in_array($champ, $this->revisionIgnores(), true)) {
                continue;
            }

            $avant = $this->getOriginal($champ);

            // getChanges() signale parfois un changement de type sans
            // changement de valeur (12 vs "12.0") : on ne le consigne pas.
            if ((string) $avant === (string) $apres) {
                continue;
            }

            $changements[$champ] = ['before' => $avant, 'after' => $apres];
        }

        return $changements;
    }

    protected function enregistrerRevision(string $action, ?array $changements, bool $correction = false): void
    {
        Revision::create([
            'revisable_type' => static::class,
            'revisable_id'   => $this->getKey(),
            'user_id'        => Auth::id(),
            'action'         => $action,
            'changes'        => $changements,
            'is_correction'  => $correction,
        ]);
    }

    /** Historique de l'objet, du plus recent au plus ancien. */
    public function revisions()
    {
        return $this->morphMany(Revision::class, 'revisable')->latest('created_at');
    }
}
