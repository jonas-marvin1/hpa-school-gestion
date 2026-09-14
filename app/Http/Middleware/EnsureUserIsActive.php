<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Coupe l'acces a l'application des la requete suivante si le compte
 * connecte est repasse a "inactive" pendant la session.
 *
 * Pourquoi un second verrou en plus de LoginRequest : desactiver un compte
 * ne suffit pas a couper une session deja ouverte, qui resterait valide
 * plusieurs heures sans ce controle.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Les routes de connexion et de deconnexion sont exclues : la
        // deconnexion faite ici doit se terminer sur la page de connexion
        // sans qu'une seconde deconnexion (celle du controleur logout, ou
        // le middleware "guest" de la page de connexion) ne boucle dessus.
        if (! $user || $user->status === 'active' || $request->routeIs('login', 'logout')) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => "Votre compte a été désactivé. Contactez l'administration de l'école.",
        ]);
    }
}
