<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestion des Programmes') }}
            </h2>
            <a href="{{ route('admin.programs.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                Nouveau Programme
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <x-search-bar placeholder="Rechercher un programme..." />
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Nom</th>
                                <th class="border-b py-2 px-4">Description</th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($programs as $program)
                                <tr>
                                    <td class="border-b py-3 px-4 font-medium">{{ $program->name }}</td>
                                    <td class="border-b py-3 px-4 text-sm text-gray-500">{{ Str::limit($program->description, 50) }}</td>
                                    <td class="border-b py-3 px-4 flex items-center gap-3">
                                        <a href="{{ route('admin.programs.edit', $program) }}" class="text-indigo-600 hover:underline">Modifier</a>
                                        <form action="{{ route('admin.programs.destroy', $program) }}" method="POST" onsubmit="return confirm('Supprimer ce programme ? Attention, cela peut affecter les niveaux liés.');">
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
                        {{ $programs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
