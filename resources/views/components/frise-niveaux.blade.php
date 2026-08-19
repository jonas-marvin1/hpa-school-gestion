@props(['echelle', 'niveau' => null, 'anime' => false])

@php
    $atteint = $niveau?->position ?? 0;
    $total   = $echelle->max('position') ?: 1;
    $avance  = (int) round($atteint / $total * 100);
@endphp

<div class="w-full">
    <div class="flex items-baseline justify-between mb-3">
        <span class="text-sm font-medium text-gray-700">
            @if($niveau)
                Niveau actuel : <span class="font-bold text-indigo-600">{{ $niveau->code }}</span>
            @else
                Niveau non encore attribué
            @endif
        </span>
        <span class="text-sm font-bold text-indigo-600">{{ $avance }}%</span>
    </div>

    {{-- Jauge de fond, puis les paliers par-dessus. Le remplissage est
         anime en largeur : au passage de niveau, la barre progresse
         visiblement au lieu de sauter d'un etat a l'autre. --}}
    <div class="relative">
        <div class="absolute top-3 left-0 right-0 h-1.5 bg-gray-200 rounded-full"></div>
        <div class="absolute top-3 left-0 h-1.5 bg-indigo-600 rounded-full {{ $anime ? 'transition-all duration-1000 ease-out' : '' }}"
             style="width: {{ $avance }}%"></div>

        <ol class="relative flex justify-between">
            @foreach($echelle as $palier)
                @php
                    $franchi = $palier->position <= $atteint;
                    $courant = $niveau && $palier->position === $atteint;
                @endphp
                <li class="flex flex-col items-center" style="flex: 1 1 0">
                    <span @class([
                        'flex h-7 w-7 items-center justify-center rounded-full border-2 text-[10px] font-bold',
                        'bg-indigo-600 border-indigo-600 text-white'      => $franchi && ! $courant,
                        'bg-white border-indigo-600 text-indigo-700 ring-4 ring-indigo-100' => $courant,
                        'bg-white border-gray-300 text-gray-400'          => ! $franchi,
                    ])
                    @if($courant) aria-current="step" @endif
                    title="{{ $palier->label }}">
                        @if($franchi && ! $courant)
                            &check;
                        @else
                            {{ $loop->iteration }}
                        @endif
                    </span>
                    <span @class([
                        'mt-2 text-[11px] font-semibold',
                        'text-indigo-700' => $courant,
                        'text-gray-700'   => $franchi && ! $courant,
                        'text-gray-400'   => ! $franchi,
                    ])>{{ $palier->code }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    @if($niveau)
        <p class="mt-4 text-xs text-gray-500">
            {{ $niveau->label }}
            @if($suivant = $niveau->suivant())
                &middot; prochain palier : <span class="font-medium text-gray-700">{{ $suivant->code }}</span>
            @else
                &middot; <span class="font-medium text-gray-700">dernier palier de l'échelle</span>
            @endif
        </p>
    @else
        <p class="mt-4 text-xs text-gray-500">
            Votre niveau sera positionné par votre formateur.
        </p>
    @endif
</div>
