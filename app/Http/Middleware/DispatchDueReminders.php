<?php

namespace App\Http\Middleware;

use App\Console\Commands\SendDueReminders;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Declenche l'envoi des rappels a l'occasion des visites.
 *
 * Pourquoi : la planification Laravel ne s'execute que si une tache cron
 * appelle `schedule:run`. Tant que ce cron n'est pas en place, aucun rappel
 * ne part. Ce middleware fait office de declencheur de secours.
 *
 * Trois precautions :
 *  - un verrou empeche deux visites simultanees de lancer l'envoi en double ;
 *  - un intervalle minimum evite de relancer le traitement a chaque requete ;
 *  - l'envoi se fait apres la reponse, la visite n'en est donc pas ralentie.
 *
 * Le jour ou le cron sera configure, ce middleware devient inoffensif :
 * l'intervalle sera deja respecte, il ne fera rien.
 */
class DispatchDueReminders
{
    /** Intervalle minimum entre deux passages, en secondes. */
    private const INTERVALLE_SECONDES = 120;

    private const CLE_DERNIER_PASSAGE = 'rappels:dernier-passage';
    private const CLE_VERROU = 'rappels:verrou';

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /** Execute apres l'envoi de la reponse au visiteur. */
    public function terminate(Request $request, $response): void
    {
        if (! $this->doitTourner()) {
            return;
        }

        // Verrou court : si une autre requete est deja en train d'envoyer,
        // celle-ci passe son tour au lieu d'attendre.
        $verrou = Cache::lock(self::CLE_VERROU, 60);

        if (! $verrou->get()) {
            return;
        }

        try {
            Cache::put(self::CLE_DERNIER_PASSAGE, now()->timestamp, now()->addDay());
            Artisan::call(SendDueReminders::class);
        } catch (\Throwable $e) {
            // Un incident d'envoi ne doit jamais casser la navigation :
            // l'erreur part dans les logs et le prochain passage reessaiera.
            Log::error('Envoi des rappels impossible : ' . $e->getMessage());
        } finally {
            $verrou->release();
        }
    }

    private function doitTourner(): bool
    {
        $dernier = Cache::get(self::CLE_DERNIER_PASSAGE);

        return $dernier === null
            || (now()->timestamp - (int) $dernier) >= self::INTERVALLE_SECONDES;
    }
}
