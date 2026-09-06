<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mon Espace Apprenant') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Alertes : echeance de paiement proche/depassee et devoirs a rendre. --}}
            <x-alertes-echeances />

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            {{-- Recapitulatif chiffre, sur le meme principe que le tableau de bord
                 admin : cet ecran mesure, il ne liste pas. Le detail de chaque
                 rubrique vit dans sa page, atteignable en cliquant la carte. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-kpi-carte titre="Mon niveau d'anglais"
                             :valeur="$kpis['niveau'] ?? '—'"
                             couleur="purple"
                             :lien="route('student.progression.index')"
                             :detail="$niveauActuel?->label ?? 'Niveau non encore évalué'" />

                <x-kpi-carte titre="Avancement du cursus"
                             :valeur="$kpis['progression_cursus']"
                             unite="%" couleur="indigo"
                             :lien="route('student.progression.index')"
                             :detail="$sessionsDone . ' séance' . ($sessionsDone > 1 ? 's' : '') . ' sur ' . $sessionsTotal" />

                <x-kpi-carte titre="Devoirs à rendre"
                             :valeur="$kpis['devoirs_a_rendre']"
                             :couleur="$kpis['devoirs_a_rendre'] > 0 ? 'amber' : 'green'"
                             :lien="route('student.assignments.index')"
                             :detail="$kpis['devoirs_rendus'] . ' déjà rendu' . ($kpis['devoirs_rendus'] > 1 ? 's' : '')" />

                <x-kpi-carte titre="Moyenne générale"
                             :valeur="$kpis['moyenne'] !== null ? $kpis['moyenne'] : '—'"
                             :unite="$kpis['moyenne'] !== null ? '/20' : null"
                             :couleur="$kpis['moyenne'] === null ? 'gray' : ($kpis['moyenne'] >= 10 ? 'green' : 'amber')"
                             :lien="route('student.progression.index')"
                             :detail="$kpis['moyenne'] !== null ? 'Sur les devoirs notés' : 'Aucun devoir noté'" />

                <x-kpi-carte titre="Taux de présence"
                             :valeur="$kpis['assiduite']"
                             unite="%"
                             :couleur="$kpis['assiduite'] >= 80 ? 'green' : 'red'"
                             :detail="'Sur ' . $totalSessions . ' cours'" />

                <x-kpi-carte titre="Prochains cours"
                             :valeur="$kpis['prochains_cours']"
                             couleur="blue"
                             :lien="route('student.emploi.index')"
                             detail="Séances à venir" />

                <x-kpi-carte titre="Mes programmes"
                             :valeur="$kpis['programmes']"
                             couleur="purple"
                             :lien="route('student.programme.index')"
                             detail="Programmes suivis" />

                @php
                    // Le « prochain » paiement est le plus ancien impaye : il peut donc
                    // deja etre echu. L'annoncer comme a venir induirait en erreur.
                    $paiementEnRetard = isset($nextPayment) && $nextPayment->due_date->lt(now()->startOfDay());
                @endphp
                <x-kpi-carte :titre="$paiementEnRetard ? 'Paiement en retard' : 'Prochain paiement'"
                             :valeur="$nextPayment ? number_format($nextPayment->amount, 0, ',', ' ') : 'À jour'"
                             :unite="$nextPayment ? 'FCFA' : null"
                             :couleur="$nextPayment ? ($paiementEnRetard ? 'red' : 'amber') : 'green'"
                             :lien="route('student.payments.index')"
                             :detail="$nextPayment
                                 ? ($paiementEnRetard ? 'Échu depuis le ' : 'Échéance du ') . $nextPayment->due_date->format('d/m/Y')
                                 : 'Aucun montant dû'" />
            </div>

            {{-- Rappel operationnel : les avis de seance sont facultatifs mais
                 attendus, on les garde en vue tant qu'ils ne sont pas donnes. --}}
            @if($pendingFeedbacks->count() > 0)
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 shadow-sm sm:rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-yellow-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3 w-full">
                            <h3 class="text-sm font-medium text-yellow-800">Avis en attente</h3>
                            <ul class="mt-2 space-y-2 text-sm text-yellow-700">
                                @foreach($pendingFeedbacks as $session)
                                    <li class="flex flex-wrap items-center justify-between gap-2">
                                        <span><strong>{{ $session->courseClass->name }}</strong> — {{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}</span>
                                        <a href="{{ route('student.sessions.feedback.create', $session) }}" class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                                            Donner mon avis
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Un seul rappel : le prochain cours. Le reste de l'emploi du temps
                 est dans l'onglet dedie. --}}
            @if($upcomingSessions->count())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-baseline justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Mon prochain cours</h3>
                            <a href="{{ route('student.emploi.index') }}" class="text-sm text-indigo-600 hover:underline">Tout voir</a>
                        </div>

                        @php $s = $upcomingSessions->first(); @endphp
                        <div class="flex flex-wrap items-center gap-4 rounded-lg border {{ $s->start_time->isToday() ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200' }} p-4">
                            <div class="text-center shrink-0 w-16">
                                <p class="text-2xl font-bold {{ $s->start_time->isToday() ? 'text-indigo-700' : 'text-gray-900' }}">{{ $s->start_time->format('d') }}</p>
                                <p class="text-xs uppercase text-gray-500">{{ $s->start_time->translatedFormat('M') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">{{ $s->courseClass->name ?? '—' }}</p>
                                <p class="text-sm text-gray-700">
                                    {{ $s->start_time->translatedFormat('l') }}
                                    de {{ $s->start_time->format('H:i') }} à {{ $s->end_time->format('H:i') }}
                                    @if($s->start_time->isToday())
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Aujourd'hui</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">
                                    @if($s->intervention_type === 'online' && $s->online_link)
                                        <a href="{{ $s->online_link }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline font-medium">Rejoindre le cours (en ligne)</a>
                                    @elseif($s->intervention_type === 'online')
                                        En ligne
                                    @else
                                        Présentiel{{ ($s->courseClass->location ?? null) ? ' — ' . $s->courseClass->location : '' }}
                                    @endif
                                    &middot; Coach : {{ $s->coach->name ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
