<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'HPA School Gestion') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700|outfit:400,500,700,800" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Outfit', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-gradient {
            background: linear-gradient(to right, #60a5fa, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .bg-grid {
            background-image: 
                linear-gradient(to right, rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.6;
            animation: pulse 8s infinite alternate;
        }
        @keyframes pulse {
            0% { transform: scale(1) translate(0, 0); }
            50% { transform: scale(1.1) translate(20px, -20px); }
            100% { transform: scale(0.9) translate(-20px, 20px); }
        }
    </style>
</head>
<body class="antialiased bg-gray-950 text-white min-h-screen relative overflow-x-hidden">

    <!-- Decorative Background -->
    <div class="fixed inset-0 bg-grid z-0 pointer-events-none"></div>
    <div class="blob bg-blue-600/40 w-96 h-96 rounded-full top-[-10%] left-[-10%]"></div>
    <div class="blob bg-purple-600/30 w-[500px] h-[500px] rounded-full bottom-[-20%] right-[-10%] animation-delay-2000"></div>

    <div class="relative z-10 min-h-screen flex flex-col">
        <!-- Navigation -->
        <header class="w-full py-6 px-6 sm:px-12 flex justify-between items-center glass-panel sticky top-0 z-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/30">
                    HPA
                </div>
                <span class="font-heading font-bold text-xl tracking-tight text-white hidden sm:block">School Gestion</span>
            </div>

            @if (Route::has('login'))
                <nav class="flex gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-medium px-5 py-2.5 rounded-full bg-white/10 hover:bg-white/20 transition-all duration-300 ring-1 ring-white/10">Tableau de bord</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium px-5 py-2.5 text-gray-300 hover:text-white transition-colors duration-300">Connexion</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="font-medium px-5 py-2.5 rounded-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white shadow-lg shadow-purple-500/25 transition-all duration-300 hover:scale-105 transform">S'inscrire</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <!-- Hero Section -->
        <main class="flex-grow flex items-center justify-center px-6 py-20 lg:py-32">
            <div class="max-w-5xl mx-auto text-center space-y-10">
                
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-panel border-purple-500/30 text-purple-300 text-sm font-medium mb-4 mx-auto hover:bg-white/10 transition-colors cursor-default">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-purple-500"></span>
                    </span>
                    Version 1.0 est maintenant disponible
                </div>

                <h1 class="font-heading text-5xl sm:text-7xl lg:text-8xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-white via-gray-200 to-gray-500 leading-tight">
                    Gérez votre école <br class="hidden sm:block"/>
                    <span class="text-gradient">simplement.</span>
                </h1>

                <p class="text-lg sm:text-xl text-gray-400 max-w-2xl mx-auto leading-relaxed">
                    Une plateforme tout-en-un pour les administrateurs, les managers, les coachs et les étudiants. Planifiez, évaluez et suivez la progression en temps réel.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-5 pt-8">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white text-black font-semibold text-lg hover:bg-gray-100 transition-all duration-300 hover:-translate-y-1 shadow-[0_0_40px_-10px_rgba(255,255,255,0.3)]">
                            Accéder à mon espace
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white text-black font-semibold text-lg hover:bg-gray-100 transition-all duration-300 hover:-translate-y-1 shadow-[0_0_40px_-10px_rgba(255,255,255,0.3)]">
                            Se connecter
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl glass-panel text-white font-semibold text-lg hover:bg-white/10 transition-all duration-300 hover:-translate-y-1">
                                Créer un compte
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </main>

        <!-- Features Section -->
        <section class="py-20 px-6 sm:px-12 max-w-7xl mx-auto w-full border-t border-white/5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="glass-panel p-8 rounded-3xl hover:bg-white/[0.08] transition-all duration-500 group">
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-white">Gestion des Utilisateurs</h3>
                    <p class="text-gray-400 leading-relaxed">Administration complète des rôles. De l'étudiant à l'administrateur, chaque profil a son espace dédié.</p>
                </div>
                <!-- Card 2 -->
                <div class="glass-panel p-8 rounded-3xl hover:bg-white/[0.08] transition-all duration-500 group">
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-white">Suivi Pédagogique</h3>
                    <p class="text-gray-400 leading-relaxed">Assignation de devoirs, pointage des présences, génération de rapports et attribution de notes fluides.</p>
                </div>
                <!-- Card 3 -->
                <div class="glass-panel p-8 rounded-3xl hover:bg-white/[0.08] transition-all duration-500 group">
                    <div class="w-14 h-14 rounded-2xl bg-pink-500/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-white">Gestion Financière</h3>
                    <p class="text-gray-400 leading-relaxed">Configuration des règles de paiement, suivi des transactions et facturation automatisée en toute sécurité.</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-white/5 py-8 text-center text-gray-500 text-sm">
            <p>&copy; {{ date('Y') }} HPA School Gestion. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>
