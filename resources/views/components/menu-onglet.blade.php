@props(['titre', 'liens' => [], 'actif' => false])

{{--
    Onglet de navigation regroupant plusieurs pages liées.

    Aligner sept rubriques dans la barre la rendrait illisible. Les regrouper
    par nature garde une barre courte tout en laissant chaque page accessible
    en deux clics.

    L'onglet reste visuellement actif tant qu'on se trouve sur l'une de ses
    pages : l'utilisateur sait toujours dans quelle rubrique il navigue.
--}}
<div class="relative h-16 flex items-center" x-data="{ ouvert: false }" @click.outside="ouvert = false" @keydown.escape="ouvert = false">
    <button type="button" @click="ouvert = !ouvert"
        @class([
            'inline-flex items-center gap-1 h-16 px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none',
            'border-indigo-400 text-gray-900'                                              => $actif,
            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'   => ! $actif,
        ])
        :aria-expanded="ouvert">
        {{ $titre }}
        <svg class="h-4 w-4 transition-transform" :class="ouvert && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="ouvert" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-0 top-14 z-50 w-60 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
        @foreach($liens as [$libelle, $url, $motif])
            <a href="{{ $url }}"
               @class([
                   'block px-4 py-2 text-sm transition',
                   'bg-indigo-50 text-indigo-700 font-medium' => request()->routeIs($motif),
                   'text-gray-700 hover:bg-gray-50'           => ! request()->routeIs($motif),
               ])>{{ $libelle }}</a>
        @endforeach
    </div>
</div>
