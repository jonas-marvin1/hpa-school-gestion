<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestion des Classes & Affectations') }}
        </h2>
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
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Nom de la Classe</th>
                                <th class="border-b py-2 px-4">Programme</th>
                                <th class="border-b py-2 px-4">Niveau</th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classes as $class)
                                <tr>
                                    <td class="border-b py-2 px-4">{{ $class->name }}</td>
                                    <td class="border-b py-2 px-4">{{ optional(optional($class->level)->program)->name ?? 'N/A' }}</td>
                                    <td class="border-b py-2 px-4">{{ optional($class->level)->name ?? 'N/A' }}</td>
                                    <td class="border-b py-2 px-4">
                                        <a href="{{ route('manager.classes.assign.edit', $class) }}" class="text-blue-600 hover:underline">Gérer les affectations</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-500">Aucune classe disponible. (Demandez à l'administrateur d'en créer).</td>
                                </tr>
                            @endforelse
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
