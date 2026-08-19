<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tableau de bord Administrateur (KPI)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Alertes -->
            @if(count($alerts) > 0)
                <div class="mb-6 space-y-3">
                    @foreach($alerts as $alert)
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        {{ $alert['message'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-indigo-500">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate">Total Apprenants</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $kpis['total_students'] }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-green-500">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate">Total Formateurs</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $kpis['total_coaches'] }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-blue-500">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate">Classes Actives</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $kpis['total_classes'] }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-purple-500">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate">Sessions (Toutes / Terminées)</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $kpis['total_sessions'] }} / <span class="text-green-600">{{ $kpis['completed_sessions'] }}</span></div>
                    </div>
                </div>
                
                <!-- Statistiques Financières -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-red-500">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate">Paies en attente (FCFA)</div>
                        <div class="mt-1 text-3xl font-semibold text-red-600">{{ number_format($kpis['pending_payments'], 0, ',', ' ') }}</div>
                    </div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-teal-500">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 truncate">Paies réglées (FCFA)</div>
                        <div class="mt-1 text-3xl font-semibold text-teal-600">{{ number_format($kpis['paid_payments'], 0, ',', ' ') }}</div>
                    </div>
                </div>

            </div>

            {{-- Encaissements a relancer. Place avant les actions rapides :
                 c'est l'information qui declenche une action dans la journee. --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-wrap items-baseline justify-between gap-3 mb-5">
                        <h3 class="text-lg font-bold">Paiements à relancer</h3>
                        <span class="text-sm text-gray-500">{{ now()->translatedFormat('l j F Y') }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Échéances du jour</p>
                            <p class="text-2xl font-bold text-amber-900 mt-1">{{ number_format($totalDuJour, 0, ',', ' ') }} <span class="text-sm font-medium">FCFA</span></p>
                            <p class="text-xs text-amber-800 mt-1">{{ $echeancesDuJour->count() }} apprenant{{ $echeancesDuJour->count() > 1 ? 's' : '' }} concerné{{ $echeancesDuJour->count() > 1 ? 's' : '' }}</p>
                        </div>
                        <div class="rounded-lg border {{ $echeancesEnRetard->count() ? 'border-red-300 bg-red-50' : 'border-green-300 bg-green-50' }} p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide {{ $echeancesEnRetard->count() ? 'text-red-800' : 'text-green-800' }}">En retard</p>
                            <p class="text-2xl font-bold {{ $echeancesEnRetard->count() ? 'text-red-900' : 'text-green-900' }} mt-1">{{ number_format($totalEnRetard, 0, ',', ' ') }} <span class="text-sm font-medium">FCFA</span></p>
                            <p class="text-xs {{ $echeancesEnRetard->count() ? 'text-red-800' : 'text-green-800' }} mt-1">{{ $echeancesEnRetard->count() }} échéance{{ $echeancesEnRetard->count() > 1 ? 's' : '' }} dépassée{{ $echeancesEnRetard->count() > 1 ? 's' : '' }}</p>
                        </div>
                    </div>

                    @php $aRelancer = $echeancesEnRetard->concat($echeancesDuJour); @endphp

                    @if($aRelancer->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b py-2 px-3">Apprenant</th>
                                        <th class="border-b py-2 px-3">Échéance</th>
                                        <th class="border-b py-2 px-3 text-right">Montant</th>
                                        <th class="border-b py-2 px-3 text-right">Reste dû</th>
                                        <th class="border-b py-2 px-3 text-center">État</th>
                                        <th class="border-b py-2 px-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aRelancer as $e)
                                        @php $retard = $e->due_date->lt(today()); @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="border-b py-2 px-3 font-medium">{{ $e->student->name ?? '—' }}</td>
                                            <td class="border-b py-2 px-3 {{ $retard ? 'text-red-700 font-medium' : '' }}">
                                                {{ $e->due_date->format('d/m/Y') }}
                                                @if($retard)
                                                    <span class="text-xs">({{ (int) $e->due_date->diffInDays(today()) }} j)</span>
                                                @endif
                                            </td>
                                            <td class="border-b py-2 px-3 text-right font-medium">{{ number_format($e->amount, 0, ',', ' ') }}</td>
                                            <td class="border-b py-2 px-3 text-right text-gray-600">
                                                {{ $e->paymentPlan ? number_format($e->paymentPlan->soldeRestant(), 0, ',', ' ') : '—' }}
                                            </td>
                                            <td class="border-b py-2 px-3 text-center">
                                                @if($retard)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">En retard</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Aujourd'hui</span>
                                                @endif
                                            </td>
                                            <td class="border-b py-2 px-3 text-center whitespace-nowrap">
                                                <a href="{{ route('admin.students.plan.edit', $e->student_id) }}" class="text-indigo-600 hover:underline">Voir le plan</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucune échéance à relancer aujourd'hui.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Actions Rapides</h3>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Gérer les utilisateurs</a>
                        <a href="{{ route('admin.classes.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Gérer la structure</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
