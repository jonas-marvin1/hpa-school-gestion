<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Execution de commandes de maintenance depuis le navigateur.
 *
 * Raison d'etre : l'hebergement mutualise ne donne pas acces a un terminal.
 * Sans cela, il serait impossible de vider les caches, de creer le lien de
 * stockage ou de preparer le jeu de recette.
 *
 * Trois garde-fous, car cette route est par nature sensible :
 *  1. elle n'existe que si MAINTENANCE_TOKEN est renseigne dans .env ;
 *  2. le jeton doit etre fourni et long, la comparaison est a temps constant ;
 *  3. seules les commandes de la liste blanche ci-dessous sont acceptees.
 *
 * A retirer du .env une fois la recette terminee : sans jeton, la route
 * repond 404 comme si elle n'existait pas.
 */
class MaintenanceController extends Controller
{
    /**
     * Commandes autorisees. Aucune autre ne peut etre lancee, quel que soit
     * le parametre transmis : c'est ce qui empeche cette route de devenir
     * une porte d'entree vers des commandes arbitraires.
     */
    private const COMMANDES = [
        'lien-stockage'   => ['storage:link',   'Crée le lien vers les fichiers déposés (audio, pièces jointes)'],
        'vider-cache'     => ['optimize:clear', 'Vide les caches de configuration, de routes et de vues'],
        'migrer'          => ['migrate',        'Applique les migrations en attente'],
        'jeu-de-recette'  => ['demo:prepare',   'Prépare le jeu de données de recette'],
        'nettoyer-jeu'    => ['demo:prepare',   'Retire le jeu de données de recette'],
        'envoyer-rappels' => ['reminders:send', 'Déclenche l\'envoi des rappels'],
    ];

    public function __invoke(Request $request, string $action = null)
    {
        $this->verifierAcces($request);

        if ($action === null) {
            return response($this->sommaire($request->query('token')))
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        abort_unless(isset(self::COMMANDES[$action]), 404);

        [$commande] = self::COMMANDES[$action];
        $options = $action === 'nettoyer-jeu' ? ['--clean' => true] : [];

        // Tracer qui lance quoi : cette route modifie l'application.
        Log::info('Maintenance : ' . $commande, ['ip' => $request->ip(), 'action' => $action]);

        try {
            Artisan::call($commande, $options);
            $sortie = trim(Artisan::output()) ?: 'Commande exécutée.';
            $etat = 'ok';
        } catch (\Throwable $e) {
            $sortie = $e->getMessage();
            $etat = 'erreur';
        }

        return response($this->resultat($action, $commande, $sortie, $etat, $request->query('token')))
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /** Le jeton doit exister, etre suffisamment long, et correspondre. */
    private function verifierAcces(Request $request): void
    {
        $attendu = config('app.maintenance_token');

        // Absent du .env : la route se comporte comme si elle n'existait pas.
        abort_if(blank($attendu), 404);

        // Un jeton court serait devinable par force brute.
        abort_if(strlen($attendu) < 32, 404);

        $fourni = (string) $request->query('token');

        // hash_equals : comparaison a temps constant, pour ne pas laisser
        // deduire le jeton caractere par caractere.
        abort_unless(hash_equals($attendu, $fourni), 404);
    }

    private function sommaire(?string $token): string
    {
        $liens = '';

        foreach (self::COMMANDES as $action => [$commande, $libelle]) {
            $url = url("/maintenance/{$action}") . '?token=' . urlencode((string) $token);
            $liens .= '<li><a href="' . e($url) . '">' . e($libelle) . '</a>'
                    . ' <code>' . e($commande) . '</code></li>';
        }

        return $this->gabarit('Maintenance', "<ul>{$liens}</ul>"
            . '<p class="note">Retirez <code>MAINTENANCE_TOKEN</code> du fichier .env '
            . 'une fois la recette terminée : cette page deviendra alors introuvable.</p>');
    }

    private function resultat(string $action, string $commande, string $sortie, string $etat, ?string $token): string
    {
        $retour = url('/maintenance') . '?token=' . urlencode((string) $token);

        return $this->gabarit(
            $etat === 'ok' ? 'Terminé' : 'Échec',
            '<p><code>' . e($commande) . '</code></p>'
            . '<pre class="' . $etat . '">' . e($sortie) . '</pre>'
            . '<p><a href="' . e($retour) . '">&larr; Retour</a></p>'
        );
    }

    private function gabarit(string $titre, string $corps): string
    {
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>' . e($titre) . '</title><style>'
            . 'body{font-family:Segoe UI,system-ui,sans-serif;max-width:44rem;margin:3rem auto;padding:0 1.5rem;color:#16202B;line-height:1.6}'
            . 'h1{font-size:1.4rem}ul{padding-left:1.1rem}li{margin:.5rem 0}'
            . 'code{background:#F4F6F8;padding:.1em .4em;border-radius:4px;font-size:.85em}'
            . 'pre{background:#F4F6F8;border:1px solid #E4E7EC;border-radius:8px;padding:1rem;overflow-x:auto;white-space:pre-wrap}'
            . 'pre.erreur{background:#FBE9EC;border-color:#F0B8C1}'
            . '.note{color:#5B6675;font-size:.9rem;border-left:3px solid #C8102E;padding-left:1rem}'
            . 'a{color:#C8102E}</style></head><body><h1>' . e($titre) . '</h1>' . $corps . '</body></html>';
    }
}
