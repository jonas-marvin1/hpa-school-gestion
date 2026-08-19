@props(['titre', 'valeur', 'unite' => null, 'detail' => null, 'couleur' => 'indigo', 'lien' => null])

@php
    // Le liseré de bas distingue les rubriques au premier coup d'œil, sans
    // colorer toute la carte — ce qui rendrait la grille illisible.
    $liseres = [
        'indigo' => 'bg-indigo-500', 'green'  => 'bg-green-500',
        'amber'  => 'bg-amber-500',  'red'    => 'bg-red-500',
        'blue'   => 'bg-blue-500',   'purple' => 'bg-purple-500',
        'gray'   => 'bg-gray-400',
    ];
    $textes = [
        'indigo' => 'text-indigo-700', 'green'  => 'text-green-700',
        'amber'  => 'text-amber-700',  'red'    => 'text-red-700',
        'blue'   => 'text-blue-700',   'purple' => 'text-purple-700',
        'gray'   => 'text-gray-700',
    ];

    $balise = $lien ? 'a' : 'div';
@endphp

<{{ $balise }} @if($lien) href="{{ $lien }}" @endif
    class="block bg-white overflow-hidden shadow-sm sm:rounded-lg {{ $lien ? 'transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-400' : '' }}">
    <div class="p-5">
        <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">{{ $titre }}</p>
        <p class="mt-2 text-3xl font-bold {{ $textes[$couleur] ?? $textes['indigo'] }}">
            {{ $valeur }}@if($unite)<span class="ml-1 text-sm font-medium text-gray-500">{{ $unite }}</span>@endif
        </p>
        @if($detail)
            <p class="mt-1 text-xs text-gray-500">{{ $detail }}</p>
        @endif
    </div>
    <div class="h-1 {{ $liseres[$couleur] ?? $liseres['indigo'] }}"></div>
</{{ $balise }}>
