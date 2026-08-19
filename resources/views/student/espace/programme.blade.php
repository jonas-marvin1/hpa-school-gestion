<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mon programme') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="space-y-3">
                        @forelse($programmes as $entree)
                            <x-programme-detail
                                :programme="$entree->programme"
                                :sous-titre="'Classe : ' . $entree->classes->implode(', ') . ($entree->niveau ? ' — niveau ' . $entree->niveau : '')"
                                :ouvert="true" />
                        @empty
                            <p class="text-sm text-gray-500 italic">
                                Vous n'êtes affecté à aucune classe pour l'instant. Votre programme
                                apparaîtra ici dès votre inscription à une classe.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
