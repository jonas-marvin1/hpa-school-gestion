<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Derriere un proxy (Codespaces, hebergement mutualise), Laravel
        // deduit la racine des URL de la requete recue et genere des liens
        // vers localhost. Forcer la racine sur APP_URL corrige les
        // redirections et les liens envoyes par courriel.
        //
        // Le garde-fou est essentiel : « http://localhost » est la valeur par
        // defaut livree avec Laravel. Sans ce test, un .env incomplet en
        // production ferait pointer tous les liens du site vers localhost,
        // sur toutes les pages a la fois.
        $racine = config('app.url');

        if ($racine && $racine !== 'http://localhost') {
            URL::forceRootUrl($racine);
        }
    }
}
