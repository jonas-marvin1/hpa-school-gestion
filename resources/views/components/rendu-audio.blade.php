@props(['chemin', 'auteur' => null])

@php
    use Illuminate\Support\Facades\Storage;

    $url      = asset('storage/' . $chemin);
    $ext      = strtoupper(pathinfo($chemin, PATHINFO_EXTENSION));
    $present  = Storage::disk('public')->exists($chemin);
    $taille   = $present ? Storage::disk('public')->size($chemin) : 0;
@endphp

@if(! $present)
    {{-- Sans ce controle, le lecteur s'affichait normalement mais restait bloque
         sur 00:00, sans que rien n'explique pourquoi. Mieux vaut nommer la panne. --}}
    <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
        <p class="font-medium">Enregistrement introuvable sur le serveur.</p>
        <p class="mt-1 text-xs">
            Le rendu est bien référencé, mais le fichier n'est pas accessible.
            Cela vient généralement du lien de stockage : lancez
            <span class="font-mono">storage:link</span> depuis la page de maintenance.
        </p>
        <p class="mt-1 text-xs font-mono break-all opacity-75">{{ $chemin }}</p>
    </div>
@elseif($taille < 1024)
    <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
        <p class="font-medium">Enregistrement vide ({{ $taille }} octets).</p>
        <p class="mt-1 text-xs">L'apprenant doit le refaire : rien n'a été capté au moment du dépôt.</p>
    </div>
@else
    <div class="space-y-2">
        {{-- preload="metadata" : la duree s'affiche sans telecharger toute la
             piste. Sur un ecran listant plusieurs rendus, cela evite de charger
             des dizaines de fichiers d'un coup tout en montrant leur longueur. --}}
        <audio controls preload="metadata" class="w-full max-w-lg"
               @if($auteur) aria-label="Enregistrement de {{ $auteur }}" @endif>
            <source src="{{ $url }}">
            Votre navigateur ne peut pas lire ce fichier audio.
        </audio>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
            <span class="text-gray-500">Format {{ $ext }} &middot; {{ number_format($taille / 1024, 0, ',', ' ') }} Ko</span>
            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Ouvrir dans un nouvel onglet</a>
            <a href="{{ $url }}" download class="text-indigo-600 hover:underline">Télécharger</a>
        </div>
    </div>
@endif
