<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestion des Classes') }}
            </h2>
            <a href="{{ route('admin.classes.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                Nouvelle Classe
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
                    <x-search-bar placeholder="Rechercher une classe..." />
                    <div class="mb-4 flex sm:justify-end">
                        {{-- Exporte exactement les lignes filtrees affichees a l'ecran. --}}
                        <a href="{{ route('admin.classes.export', request()->query()) }}" class="text-indigo-600 hover:underline text-sm">Exporter (CSV)</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Programme / Niveau</th>
                                <th class="border-b py-2 px-4">Nom de la classe</th>
                                <th class="border-b py-2 px-4">Lieu</th>
                                <th class="border-b py-2 px-4">Début</th>
                                <th class="border-b py-2 px-4">Fin</th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                                <tr>
                                    <td class="border-b py-3 px-4">
                                        <div class="text-sm text-gray-500">{{ optional(optional($class->level)->program)->name ?? 'N/A' }}</div>
                                        <div class="font-medium">{{ optional($class->level)->name ?? 'Niveau Supprimé' }}</div>
                                    </td>
                                    <td class="border-b py-3 px-4 font-medium">{{ $class->name }}</td>
                                    <td class="border-b py-3 px-4 text-sm text-gray-600">{{ $class->location ?? 'Non défini' }}</td>
                                    <td class="border-b py-3 px-4">{{ \Carbon\Carbon::parse($class->start_date)->format('d/m/Y') }}</td>
                                    <td class="border-b py-3 px-4">{{ \Carbon\Carbon::parse($class->end_date)->format('d/m/Y') }}</td>
                                    <td class="border-b py-3 px-4 flex items-center gap-3">
                                        <a href="{{ route('admin.classes.assign.edit', $class) }}" class="text-green-600 hover:underline">Affecter</a>
                                        <a href="{{ route('admin.classes.edit', $class) }}" class="text-indigo-600 hover:underline">Modifier</a>
                                        <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" onsubmit="return confirm('Supprimer cette classe ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $classes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
