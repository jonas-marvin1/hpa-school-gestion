<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    // Titre d'onglet propre a chaque page d'authentification (connexion,
    // mot de passe oublie, inscription...) : ce layout etant partage par
    // toutes, un titre fixe afficherait le meme intitule partout.
    public function __construct(public ?string $titre = null)
    {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
