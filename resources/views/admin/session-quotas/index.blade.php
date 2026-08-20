<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quotas de sessions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>- {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('admin.session-quotas.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div class="w-48">
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois</label>
                            <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($moisListe as $cle => $libelle)
                                    <option value="{{ $cle }}" {{ $mois === $cle ? 'selected' : '' }}>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-40">
                            <label for="year" class="block text-sm font-medium text-gray-700">Année</label>
                            <select name="year" id="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($years as $a)
                                    <option value="{{ $a }}" {{ $annee === $a ? 'selected' : '' }}>{{ $a }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium">Afficher</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-4">
                        Quotas et sessions du mois de <span class="font-semibold text-gray-900">{{ strtolower($moisListe[$mois]) }} {{ $annee }}</span>.
                        « Générées » compte les sessions non annulées, « Réalisées » celles marquées effectuées.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Programme</th>
                                <th class="border-b py-2 px-4">Classe</th>
                                <th class="border-b py-2 px-4 text-center">Prévues (quota)</th>
                                <th class="border-b py-2 px-4 text-center">Générées</th>
                                <th class="border-b py-2 px-4 text-center">Réalisées</th>
                                <th class="border-b py-2 px-4 text-center">Statut</th>
                                <th class="border-b py-2 px-4">Modifier le quota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lignes as $ligne)
                                <tr class="{{ $ligne->depasse ? 'bg-red-50' : ($ligne->atteint ? 'bg-amber-50' : '') }}">
                                    <td class="border-b py-3 px-4 text-sm text-gray-500">{{ optional(optional($ligne->classe->level)->program)->name ?? 'N/A' }}</td>
                                    <td class="border-b py-3 px-4 font-medium">{{ $ligne->classe->name }}</td>
                                    <td class="border-b py-3 px-4 text-center">
                                        @if($ligne->quota === null)
                                            <span class="text-gray-400">Quota non défini</span>
                                        @else
                                            {{ $ligne->quota }}
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4 text-center font-semibold">{{ $ligne->genere }}</td>
                                    <td class="border-b py-3 px-4 text-center">{{ $ligne->realise }}</td>
                                    <td class="border-b py-3 px-4 text-center">
                                        @if($ligne->depasse)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dépassement</span>
                                        @elseif($ligne->atteint)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Quota atteint</span>
                                        @elseif($ligne->quota !== null)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4">
                                        <form method="POST" action="{{ route('admin.session-quotas.store') }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="course_class_id" value="{{ $ligne->classe->id }}">
                                            <input type="hidden" name="year" value="{{ $annee }}">
                                            <input type="hidden" name="month" value="{{ $mois }}">
                                            <input type="number" name="quota" min="1" value="{{ $ligne->quota }}" placeholder="Quota" class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                            <button type="submit" class="text-indigo-600 hover:underline text-sm font-medium">Enregistrer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">Aucune classe.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
