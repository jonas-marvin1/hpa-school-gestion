<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mes programmes') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-5">
                        <span class="font-semibold text-gray-900">{{ $programmes->count() }}</span>
                        programme{{ $programmes->count() > 1 ? 's' : '' }} sur
                        {{ $programmes->count() > 1 ? 'lesquels' : 'lequel' }} vous intervenez.
                    </p>

                    <div class="space-y-3">
                        @forelse($programmes as $entree)
                            <x-programme-detail
                                :programme="$entree->programme"
                                :sous-titre="($entree->classes->count() > 1 ? 'Classes : ' : 'Classe : ') . $entree->classes->implode(', ')"
                                :ouvert="$programmes->count() === 1" />
                        @empty
                            <p class="text-sm text-gray-500 italic">
                                Aucun programme : vous n'avez encore aucune séance rattachée à une classe.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
