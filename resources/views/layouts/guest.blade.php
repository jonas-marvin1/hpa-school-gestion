<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Titre explicite, independant de config('app.name') : sa valeur
             en production n'est pas garantie, et c'est ce texte que
             l'utilisateur voit dans son onglet et ses favoris. Parametrable
             car ce layout est partage par toutes les pages d'authentification
             (connexion, mot de passe oublie, inscription...) : un titre fixe
             afficherait le meme intitule partout. --}}
        <title>{{ $titre ?? 'HPA School Gestion' }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-slate-100">
        {{-- Meme liseré orange que la barre de navigation de l'application
             connectee (cf. layouts/navigation.blade.php) : les pages
             d'authentification restent reconnaissables comme faisant partie
             de la meme application, avant meme la connexion. --}}
        <div class="h-1 bg-hpa-orange"></div>

        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <a href="/">
                <x-application-logo class="h-16" />
            </a>

            <div class="mt-4 text-center">
                <h1 class="text-xl font-bold text-hpa-blue">Espace de gestion</h1>
                <p class="text-sm text-gray-500">Planning, apprenants, évaluations et paies</p>
            </div>

            <div class="w-full max-w-md mt-6 px-6 py-8 bg-white border border-gray-200 shadow-md rounded-xl">
                {{ $slot }}
            </div>

            {{-- Slot optionnel pour un texte propre a une page precise (ex.
                 contact en cas de probleme de connexion) : en dehors de la
                 carte, donc pas melange avec le contenu du formulaire. --}}
            {{ $below ?? '' }}

            <p class="mt-6 text-xs text-gray-400 text-center">
                High Performance Academy — Espace réservé aux membres de l'école
            </p>
        </div>
    </body>
</html>
