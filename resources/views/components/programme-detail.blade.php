@props(['programme', 'sousTitre' => null, 'ouvert' => false])

{{--
    Fiche d'un programme : nom toujours visible, description complete dépliable.

    Les descriptions font couramment plus de 2 000 caractères. Les afficher
    d'emblee noierait le reste de l'espace ; les tronquer priverait de
    l'information. Un depliage resout les deux, et <details> le fait sans
    JavaScript — donc sans risque d'affichage casse.
--}}
<details class="group rounded-lg border border-gray-200 bg-white" @if($ouvert) open @endif>
    <summary class="flex items-start justify-between gap-3 cursor-pointer list-none p-4 hover:bg-gray-50 rounded-lg">
        <div class="min-w-0">
            <p class="font-semibold text-gray-900">{{ $programme->name }}</p>
            @if($sousTitre)
                <p class="text-sm text-gray-500 mt-0.5">{{ $sousTitre }}</p>
            @endif
        </div>

        <span class="flex items-center gap-1.5 text-sm text-indigo-600 whitespace-nowrap shrink-0">
            <span class="group-open:hidden">Voir le programme</span>
            <span class="hidden group-open:inline">Réduire</span>
            <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </span>
    </summary>

    <div class="px-4 pb-4 -mt-1">
        @if(filled($programme->description))
            <div class="rounded-md bg-gray-50 border border-gray-200 p-4 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap break-words max-h-[28rem] overflow-y-auto">{{ $programme->description }}</div>
        @else
            <p class="text-sm text-gray-500 italic">Aucune description n'a encore été rédigée pour ce programme.</p>
        @endif
    </div>
</details>
