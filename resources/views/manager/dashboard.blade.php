<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Espace Gestionnaire') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Statistiques Rapides -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-indigo-600 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <h3 class="font-bold text-sm mb-1 opacity-80">Classes Actives</h3>
                        <p class="text-3xl">{{ $totalClasses }}</p>
                    </div>
                </div>
                <div class="bg-blue-600 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <h3 class="font-bold text-sm mb-1 opacity-80">Sessions (Terminées / Total)</h3>
                        <p class="text-3xl">{{ $kpis['completed_sessions'] }} <span class="text-xl opacity-70">/ {{ $kpis['total_sessions'] }}</span></p>
                    </div>
                </div>
                <div class="bg-red-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <h3 class="font-bold text-sm mb-1 opacity-80">Paies en attente (FCFA)</h3>
                        <p class="text-2xl font-bold">{{ number_format($kpis['pending_payments'], 0, ',', ' ') }}</p>
                    </div>
                </div>
                <div class="bg-teal-500 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-white">
                        <h3 class="font-bold text-sm mb-1 opacity-80">Paies réglées (FCFA)</h3>
                        <p class="text-2xl font-bold">{{ number_format($kpis['paid_payments'], 0, ',', ' ') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Gestion des Classes et Affectations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex flex-col h-full">
                        <h3 class="font-bold text-lg mb-2">Classes & Affectations</h3>
                        <p class="text-sm text-gray-600 mb-4 flex-grow">Inscrire et affecter les apprenants et formateurs aux classes.</p>
                        <a href="{{ route('manager.classes.index') }}" class="text-indigo-600 hover:underline">Gérer les affectations &rarr;</a>
                    </div>
                </div>

                <!-- Planification (Emplois du temps) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex flex-col h-full">
                        <h3 class="font-bold text-lg mb-2">Emplois du temps (Sessions)</h3>
                        <p class="text-sm text-gray-600 mb-4 flex-grow">Créer et gérer les sessions de cours pour les classes.</p>
                        <a href="{{ route('manager.sessions.index') }}" class="text-indigo-600 hover:underline">Voir le planning &rarr;</a>
                    </div>
                </div>

            </div>
            
            <!-- Prochaines sessions (Vue Rapide) -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">Prochaines Sessions (Les 5 prochaines)</h3>
                    @if($upcomingSessions->count() > 0)
                        <ul class="space-y-3">
                            @foreach($upcomingSessions as $session)
                                <li class="flex justify-between items-center bg-gray-50 p-3 rounded">
                                    <div>
                                        <span class="font-semibold">{{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}</span> à {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}
                                        <span class="mx-2 text-gray-400">|</span>
                                        Classe : <strong>{{ optional($session->courseClass)->name ?? 'N/A' }}</strong>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ optional($session->coach)->name ?? 'Formateur non assigné' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500">Aucune session à venir.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
