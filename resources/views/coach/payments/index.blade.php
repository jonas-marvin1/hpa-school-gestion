<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mes Rémunérations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Monthly Total Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-500">Rémunération du mois ({{ str_pad($monthToCalculate, 2, '0', STR_PAD_LEFT) }}/{{ $yearToCalculate }})</h3>
                            <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($monthlyTotal, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div class="bg-indigo-50 p-3 rounded-full">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>
                
                <!-- Total to Pay Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex justify-between items-center bg-indigo-50 border-l-4 border-indigo-500 rounded">
                        <div>
                            <h3 class="text-lg font-medium text-indigo-700">Total à payer à ce jour</h3>
                            <p class="text-3xl font-bold text-indigo-900 mt-2">{{ number_format($totalToPay, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div class="bg-indigo-100 p-3 rounded-full text-indigo-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('coach.payments.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois</label>
                            {{-- Le mois affiche suit le filtre applique, et non le mois
                                 courant : le menu montrait « 08 » alors que la liste
                                 affichait toutes les periodes. --}}
                            <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tous les mois</option>
                                @for($m=1; $m<=12; $m++)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Année</label>
                            {{-- Annees fournies par le controleur : celles ayant des
                                 seances, l'annee en cours et les annees a venir. --}}
                            <select name="year" id="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Toutes les années</option>
                                @foreach($years as $annee)
                                    <option value="{{ $annee }}" {{ request('year') == $annee ? 'selected' : '' }}>{{ $annee }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="class_id" class="block text-sm font-medium text-gray-700">Classe</label>
                            <select name="class_id" id="class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tous les statuts</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>Paie générée</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sessions List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr>
                                    <th class="border-b py-2 px-4">Date</th>
                                    <th class="border-b py-2 px-4">Horaires</th>
                                    <th class="border-b py-2 px-4">Classe</th>
                                    <th class="border-b py-2 px-4">Formateur</th>
                                    <th class="border-b py-2 px-4 text-right">Rémunération</th>
                                    <th class="border-b py-2 px-4 text-center">Statut</th>
                                    <th class="border-b py-2 px-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sessions as $session)
                                    <tr class="hover:bg-gray-50" x-data="{ openModal: false }">
                                        <td class="border-b py-4 px-4 font-medium">{{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}</td>
                                        <td class="border-b py-4 px-4 text-gray-600">
                                            {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                                        </td>
                                        <td class="border-b py-4 px-4">{{ $session->courseClass->name ?? 'N/A' }}</td>
                                        <td class="border-b py-4 px-4">{{ $session->coach->name ?? 'N/A' }}</td>
                                        <td class="border-b py-4 px-4 text-right font-bold text-gray-800">{{ number_format($sessionDetails[$session->id] ?? 0, 0, ',', ' ') }}</td>
                                        <td class="border-b py-4 px-4 text-center">
                                            @if($session->payment && $session->payment->status === 'paid')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Payé
                                                </span>
                                            @elseif($session->payment)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Paie générée
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    En attente
                                                </span>
                                            @endif
                                        </td>
                                        <td class="border-b py-4 px-4 text-center">
                                            <button @click="openModal = true" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Détails</button>
                                            
                                            <!-- Modal -->
                                            <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                    
                                                    <div x-show="openModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openModal = false" aria-hidden="true"></div>

                                                    <!-- This element is to trick the browser into centering the modal contents. -->
                                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                                    <div x-show="openModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                            <div class="sm:flex sm:items-start">
                                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                                        Détails de la session
                                                                    </h3>
                                                                    <div class="mt-4 space-y-3">
                                                                        <div class="flex justify-between border-b pb-2">
                                                                            <span class="text-sm font-medium text-gray-500">Classe</span>
                                                                            <span class="text-sm text-gray-900">{{ $session->courseClass->name ?? 'N/A' }}</span>
                                                                        </div>
                                                                        <div class="flex justify-between border-b pb-2">
                                                                            <span class="text-sm font-medium text-gray-500">Date</span>
                                                                            <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}</span>
                                                                        </div>
                                                                        <div class="flex justify-between border-b pb-2">
                                                                            <span class="text-sm font-medium text-gray-500">Horaires</span>
                                                                            <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}</span>
                                                                        </div>
                                                                        <div class="flex justify-between border-b pb-2">
                                                                            <span class="text-sm font-medium text-gray-500">Type d'intervention</span>
                                                                            <span class="text-sm text-gray-900">{{ $session->intervention_type === 'online' ? 'En ligne' : 'Présentiel' }}</span>
                                                                        </div>
                                                                        <div class="flex justify-between pt-2">
                                                                            <span class="text-sm font-bold text-gray-700">Rémunération</span>
                                                                            <span class="text-sm font-bold text-gray-900">{{ number_format($sessionDetails[$session->id] ?? 0, 0, ',', ' ') }} FCFA</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                            <button type="button" @click="openModal = false" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                                Fermer
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-gray-500">Aucune session trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $sessions->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
