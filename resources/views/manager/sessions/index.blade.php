<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Emplois du temps (Sessions)') }}
            </h2>
            <a href="{{ route('manager.sessions.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium">
                Nouvelle Session
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('manager.sessions.index') }}" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-md border">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Recherche</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Classe ou formateur..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Formateur</label>
                            <select name="coach_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Tous</option>
                                @foreach($coaches as $coach)
                                    <option value="{{ $coach->id }}" {{ request('coach_id') == $coach->id ? 'selected' : '' }}>
                                        {{ $coach->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Tous</option>
                                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Prévue</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Effectuée</option>
                                <option value="validated" {{ request('status') == 'validated' ? 'selected' : '' }}>Validée</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 font-medium text-sm w-full md:w-auto">
                                Filtrer
                            </button>
                            <a href="{{ route('manager.sessions.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 font-medium text-sm w-full md:w-auto text-center">
                                Réinitialiser
                            </a>
                            {{-- Exporte exactement les lignes filtrees affichees a l'ecran. --}}
                            <a href="{{ route('manager.sessions.export', request()->query()) }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 font-medium text-sm w-full md:w-auto text-center">
                                Exporter (CSV)
                            </a>
                        </div>
                    </form>

                    {{-- Nombre total de lignes correspondant au filtre, et non
                         le seul contenu de la page affichee. --}}
                    <p class="text-sm text-gray-600 mb-4">
                        <span class="font-semibold text-gray-900">{{ $sessions->total() }}</span>
                        séance{{ $sessions->total() > 1 ? 's' : '' }}
                        @if(request()->hasAny(['search', 'status', 'coach_id']))
                            <span class="text-gray-500">pour ce filtre</span>
                        @endif
                        @if($sessions->hasPages())
                            <span class="text-gray-400">— page {{ $sessions->currentPage() }} sur {{ $sessions->lastPage() }}</span>
                        @endif
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Date</th>
                                <th class="border-b py-2 px-4">Horaires</th>
                                <th class="border-b py-2 px-4">Classe</th>
                                <th class="border-b py-2 px-4">Formateur</th>
                                <th class="border-b py-2 px-4 text-right">Montant</th>
                                <th class="border-b py-2 px-4">Statut</th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="border-b py-2 px-4">{{ $session->start_time->format('d/m/Y') }}</td>
                                    <td class="border-b py-2 px-4">
                                        {{ $session->start_time->format('H:i') }} - 
                                        {{ $session->end_time->format('H:i') }}
                                    </td>
                                    <td class="border-b py-2 px-4">{{ $session->courseClass->name ?? 'N/A' }}</td>
                                    <td class="border-b py-2 px-4">{{ $session->coach->name ?? 'N/A' }}</td>
                                    <td class="border-b py-2 px-4 text-right">{{ number_format($session->amount, 0, ',', ' ') }}</td>
                                    <td class="border-b py-2 px-4">
                                        @if($session->status === 'scheduled')
                                            <span class="px-2 py-1 text-xs text-blue-700 bg-blue-100 rounded-full">Prévue</span>
                                        @elseif($session->status === 'completed')
                                            <span class="px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full" title="En attente de validation">Effectuée</span>
                                        @elseif($session->status === 'validated')
                                            <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Validée</span>
                                        @else
                                            <span class="px-2 py-1 text-xs text-gray-700 bg-gray-100 rounded-full">Annulée</span>
                                        @endif
                                    </td>
                                    <td class="border-b py-2 px-4 flex gap-2">
                                        <a href="{{ route('manager.sessions.show', $session) }}" class="text-indigo-600 hover:underline">Détails</a>
                                        <a href="{{ route('manager.sessions.edit', $session) }}" class="text-indigo-600 hover:underline">Modifier</a>
                                        <form action="{{ route('manager.sessions.destroy', $session) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette session ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">Aucune session programmée.</td>
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
