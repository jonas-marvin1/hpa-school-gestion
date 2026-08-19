<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Niveau d\'anglais') }} &mdash; {{ $student->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <x-frise-niveaux :echelle="$echelle" :niveau="$student->englishLevel" :anime="true" />
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-1">
                        {{ $student->englishLevel ? 'Faire évoluer le niveau' : 'Attribuer le niveau initial' }}
                    </h3>
                    <p class="text-sm text-gray-500 mb-5">
                        Le changement est décidé par le formateur ou l'administrateur. Il est enregistré
                        dans l'historique ci-dessous, avec son auteur.
                    </p>

                    <form method="POST" action="{{ route('students.level.update', $student) }}" class="flex flex-wrap items-end gap-4">
                        @csrf
                        @method('PUT')

                        <div class="min-w-[16rem]">
                            <label for="english_level_id" class="block text-sm font-medium text-gray-700">Palier</label>
                            <select name="english_level_id" id="english_level_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($echelle as $palier)
                                    <option value="{{ $palier->id }}" {{ $student->english_level_id == $palier->id ? 'selected' : '' }}>
                                        {{ $palier->label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('english_level_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                            Enregistrer le niveau
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Historique de progression</h3>

                    @forelse($historique as $etape)
                        <div class="border-l-2 border-gray-200 pl-4 pb-4 last:pb-0 relative">
                            <span class="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full bg-indigo-500"></span>
                            <p class="text-sm">
                                @if($etape->avant)
                                    <span class="font-medium">{{ $etape->avant }}</span>
                                    <span class="mx-1 text-gray-400">&rarr;</span>
                                    <span class="font-bold text-indigo-700">{{ $etape->apres }}</span>
                                @else
                                    Niveau initial : <span class="font-bold text-indigo-700">{{ $etape->apres }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $etape->date->format('d/m/Y à H:i') }} &middot; par {{ $etape->auteur }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic">
                            Aucun changement enregistré pour le moment.
                        </p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
