<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mes Devoirs') }}
            </h2>
            <a href="{{ route('coach.assignments.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                Nouveau Devoir
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
                    <x-search-bar placeholder="Rechercher par titre..." />
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Titre</th>
                                <th class="border-b py-2 px-4">Classe</th>
                                <th class="border-b py-2 px-4">Attribution</th>
                                <th class="border-b py-2 px-4">Type</th>
                                <th class="border-b py-2 px-4">Date limite</th>
                                <th class="border-b py-2 px-4">Créé par</th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td class="border-b py-2 px-4 font-medium">{{ $assignment->title }}</td>
                                    <td class="border-b py-2 px-4">{{ $assignment->courseClass->name ?? 'N/A' }}</td>
                                    <td class="border-b py-2 px-4">{{ $assignment->student->name ?? 'Toute la classe' }}</td>
                                    <td class="border-b py-2 px-4 uppercase text-xs">{{ $assignment->type }}</td>
                                    <td class="border-b py-2 px-4">
                                        {{ \Carbon\Carbon::parse($assignment->due_date)->format('d/m/Y') }}
                                        @if(\Carbon\Carbon::parse($assignment->due_date)->isPast())
                                            <span class="text-red-500 text-xs ml-2">(Expiré)</span>
                                        @endif
                                    </td>
                                    <td class="border-b py-2 px-4">
                                        {{ $assignment->coach->name ?? 'N/A' }}
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 uppercase ml-1">
                                            {{ $assignment->coach->roles->pluck('name')->first() ?? '?' }}
                                        </span>
                                    </td>
                                    <td class="border-b py-2 px-4 flex items-center gap-3">
                                        <a href="{{ route('coach.evaluations.index', $assignment) }}" class="text-green-600 hover:underline">Évaluer ({{ $assignment->submissions()->count() }})</a>
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('coach.assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Supprimer ce devoir ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-sm">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">Aucun devoir créé pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $assignments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
