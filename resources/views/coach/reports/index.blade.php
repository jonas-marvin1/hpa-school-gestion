<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mes Rapports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('coach.reports.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois</label>
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
                                <option value="">Tous</option>
                                <option value="todo" {{ request('status') === 'todo' ? 'selected' : '' }}>À rédiger</option>
                                <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Effectués</option>
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

            <!-- Reports List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        {{-- whitespace-nowrap retire : la modale de detail est imbriquee dans
                             une cellule de ce tableau et en heritait, ce qui affichait le resume
                             de progression sur une seule ligne interminable. --}}
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr>
                                    <th class="border-b py-2 px-4">Date</th>
                                    <th class="border-b py-2 px-4">Horaires</th>
                                    <th class="border-b py-2 px-4">Classe</th>
                                    <th class="border-b py-2 px-4 text-center">Statut</th>
                                    <th class="border-b py-2 px-4 text-center">Présences</th>
                                    <th class="border-b py-2 px-4">Soumis le</th>
                                    <th class="border-b py-2 px-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $session)
                                    @php
                                        $presentCount = $session->attendances->where('is_present', true)->count();
                                        $totalCount = $session->attendances->count();
                                        $hasReport = $session->sessionReport !== null;
                                    @endphp
                                    <tr class="hover:bg-gray-50" x-data="{ openModal: false }">
                                        <td class="border-b py-4 px-4 font-medium">{{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}</td>
                                        <td class="border-b py-4 px-4 text-gray-600">
                                            {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                                        </td>
                                        <td class="border-b py-4 px-4">{{ $session->courseClass->name ?? 'N/A' }}</td>
                                        <td class="border-b py-4 px-4 text-center">
                                            @if($hasReport)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Effectué</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">À rédiger</span>
                                            @endif
                                        </td>
                                        <td class="border-b py-4 px-4 text-center">{{ $hasReport ? $presentCount . '/' . $totalCount : '—' }}</td>
                                        <td class="border-b py-4 px-4 text-gray-600">
                                            {{-- Garde sur sessionReport : les seances a rediger n'en ont pas. --}}
                                            {{ $hasReport ? \Carbon\Carbon::parse($session->sessionReport->created_at)->format('d/m/Y H:i') : '—' }}
                                        </td>
                                        <td class="border-b py-4 px-4 text-center">
                                            @if($hasReport)
                                                <button @click="openModal = true" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Détails</button>
                                            @else
                                                <a href="{{ route('coach.sessions.report.create', $session) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-md text-xs font-medium hover:bg-indigo-700">Rédiger le rapport</a>
                                            @endif

                                            <!-- Modal -->
                                            @if($hasReport)
                                            <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                                                    <div x-show="openModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openModal = false" aria-hidden="true"></div>

                                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                                    <div x-show="openModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full whitespace-normal">
                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                            <div class="sm:flex sm:items-start">
                                                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                                        Rapport de session
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
                                                                            <span class="text-sm font-medium text-gray-500">Présences</span>
                                                                            <span class="text-sm text-gray-900">{{ $presentCount }}/{{ $totalCount }}</span>
                                                                        </div>
                                                                        <div class="border-b pb-2">
                                                                            <span class="text-sm font-medium text-gray-500 block mb-1">Résumé de la progression</span>
                                                                            {{-- div + whitespace-pre-wrap : en span, le texte long restait
                                                                                 sur une seule ligne et les paragraphes etaient perdus. --}}
                                                                            <div class="text-sm text-gray-900 whitespace-pre-wrap break-words max-h-72 overflow-y-auto">{{ $session->sessionReport->progress }}</div>
                                                                        </div>
                                                                        @if($session->sessionReport->observations)
                                                                            <div class="pb-2">
                                                                                <span class="text-sm font-medium text-gray-500 block mb-1">Observations</span>
                                                                                <div class="text-sm text-gray-900 whitespace-pre-wrap break-words max-h-72 overflow-y-auto">{{ $session->sessionReport->observations }}</div>
                                                                            </div>
                                                                        @endif
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
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-gray-500">Aucun rapport trouvé.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $reports->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
