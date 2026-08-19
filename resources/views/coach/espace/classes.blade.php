<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mes classes') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-5">
                        <span class="font-semibold text-gray-900">{{ $classes->count() }}</span>
                        classe{{ $classes->count() > 1 ? 's' : '' }}.
                    </p>

                    @if($classes->count())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($classes as $classe)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <h3 class="font-semibold text-gray-900">{{ $classe->name }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $classe->level->program->name ?? '—' }}
                                        @if($classe->level) &middot; {{ $classe->level->name }} @endif
                                    </p>

                                    <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
                                        <div>
                                            <dt class="text-[11px] text-gray-500">Apprenants</dt>
                                            <dd class="font-bold text-gray-900">{{ $classe->nb_apprenants }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[11px] text-gray-500">Séances</dt>
                                            <dd class="font-bold text-gray-900">{{ $classe->nb_seances }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-[11px] text-gray-500">Effectuées</dt>
                                            <dd class="font-bold text-green-600">{{ $classe->nb_effectuees }}</dd>
                                        </div>
                                    </dl>

                                    @php $pct = $classe->nb_seances > 0 ? (int) round($classe->nb_effectuees / $classe->nb_seances * 100) : 0; @endphp
                                    <div class="mt-3 w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="text-right text-[11px] text-gray-500 mt-1">{{ $pct }}% du cursus</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucune classe ne vous est rattachée pour l'instant.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
