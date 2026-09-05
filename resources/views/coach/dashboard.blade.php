<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tableau de bord Formateur') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Recapitulatif chiffre. Le detail de chaque rubrique vit dans sa
                 propre page : cet ecran sert a mesurer, pas a lister. Chaque
                 carte mene directement au detail correspondant. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-kpi-carte titre="Rémunération du mois"
                             :valeur="number_format($kpis['remuneration_mois'], 0, ',', ' ')"
                             unite="FCFA" couleur="indigo"
                             :lien="route('coach.payments.index')"
                             :detail="$kpis['remuneration_due'] > 0 ? number_format($kpis['remuneration_due'], 0, ',', ' ') . ' FCFA en attente' : 'Rien en attente'" />

                <x-kpi-carte titre="Rapports à faire"
                             :valeur="$kpis['rapports_en_attente']"
                             :couleur="$kpis['rapports_en_attente'] > 0 ? 'amber' : 'green'"
                             :lien="route('coach.reports.index')"
                             detail="Séances passées sans rapport" />

                <x-kpi-carte titre="Devoirs à corriger"
                             :valeur="$kpis['devoirs_a_corriger']"
                             :couleur="$kpis['devoirs_a_corriger'] > 0 ? 'amber' : 'green'"
                             :lien="route('coach.assignments.index')"
                             detail="Rendus en attente de note" />

                <x-kpi-carte titre="Prochains cours"
                             :valeur="$kpis['prochains_cours']"
                             couleur="blue"
                             :lien="route('coach.cours.index')"
                             detail="Séances à venir" />

                <x-kpi-carte titre="Rapports effectués"
                             :valeur="$kpis['rapports_effectues']"
                             couleur="green"
                             :lien="route('coach.reports.index')"
                             detail="Depuis le début" />

                <x-kpi-carte titre="Mes programmes"
                             :valeur="$kpis['programmes']"
                             couleur="purple"
                             :lien="route('coach.programmes.index')"
                             detail="Programmes enseignés" />

                <x-kpi-carte titre="Mes classes actives"
                             :valeur="$kpis['classes_actives']"
                             couleur="purple"
                             :lien="route('coach.classes.index')"
                             detail="Classes rattachées" />

                <x-kpi-carte titre="Messagerie"
                             valeur="—"
                             couleur="gray"
                             :lien="route('messages.index')"
                             detail="Consulter mes messages" />
            </div>

            {{-- Un seul rappel opérationnel : le prochain cours. Le reste est
                 accessible depuis les cartes ci-dessus. --}}
            @if($upcomingSessions->count())
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-baseline justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide">Mon prochain cours</h3>
                            <a href="{{ route('coach.cours.index') }}" class="text-sm text-indigo-600 hover:underline">Tout voir</a>
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
                            </div>
                            <a href="{{ route('coach.sessions.show', $s) }}" class="text-sm text-indigo-600 hover:underline shrink-0">Détails</a>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
