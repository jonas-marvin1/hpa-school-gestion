<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Évaluations : {{ $assignment->title }} ({{ $assignment->courseClass->name }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="overflow-x-auto">
                        {{-- whitespace-nowrap retire : il empechait tout retour a la ligne,
                             donc l'affichage du rendu complet. --}}
                        <table class="w-full text-left border-collapse mt-4 align-top">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Apprenant</th>
                                <th class="border-b py-2 px-4">Statut de soumission</th>
                                <th class="border-b py-2 px-4 min-w-[420px]">Rendu</th>
                                <th class="border-b py-2 px-4 text-center">Note (/20)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                @php
                                    $submission = $submissions->get($student->id);
                                    $grade = $submission ? $submission->grade : null;
                                @endphp
                                <tr>
                                    <td class="border-b py-4 px-4 font-medium">{{ $student->name }}</td>
                                    <td class="border-b py-4 px-4">
                                        @if($submission)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Soumis le {{ \Carbon\Carbon::parse($submission->created_at)->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">En attente</span>
                                        @endif
                                    </td>
                                    <td class="border-b py-4 px-4 text-sm align-top">
                                        @if($submission)
                                            @if($assignment->type === 'text')
                                                {{-- Rendu complet. Auparavant "max-w-xs truncate" reduisait le
                                                     devoir a une seule ligne coupee, ce qui rendait la correction
                                                     impossible. whitespace-pre-wrap conserve les paragraphes. --}}
                                                <div class="whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-3 text-gray-800 leading-relaxed max-h-96 overflow-y-auto">{{ $submission->content_text }}</div>
                                            @elseif($assignment->type === 'link')
                                                <a href="{{ $submission->content_text }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">{{ $submission->content_text }}</a>
                                            @elseif(empty($submission->file_path))
                                                {{-- Types "qcm" et "online" : pas de fichier joint, le rendu
                                                     est dans le texte s'il y en a un. --}}
                                                @if(filled($submission->content_text))
                                                    <div class="whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-3 text-gray-800 leading-relaxed max-h-96 overflow-y-auto">{{ $submission->content_text }}</div>
                                                @else
                                                    <span class="text-gray-400 italic">Aucun fichier joint</span>
                                                @endif
                                            @else
                                                @php
                                                    $ext = strtolower(pathinfo($submission->file_path, PATHINFO_EXTENSION));
                                                    $url = asset('storage/' . $submission->file_path);
                                                @endphp
                                                <div class="space-y-2">
                                                    {{-- Apercu direct dans la page : le coach lit ou ecoute le
                                                         devoir sans avoir a telecharger puis rouvrir le fichier. --}}
                                                    @if(in_array($ext, ['mp3', 'm4a', 'mp4', 'wav', 'ogg', 'oga', 'webm', 'weba', 'aac', '3gp', '3gpp', 'opus']))
                                                        <x-rendu-audio :chemin="$submission->file_path" :auteur="$student->name" />
                                                    @elseif(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                        <a href="{{ $url }}" target="_blank" rel="noopener">
                                                            <img src="{{ $url }}" alt="Rendu de {{ $student->name }}" class="max-h-96 rounded border border-gray-200">
                                                        </a>
                                                    @elseif($ext === 'pdf')
                                                        <embed src="{{ $url }}" type="application/pdf" class="w-full h-96 rounded border border-gray-200">
                                                        {{-- Plus d'attribut "download" : il forcait le telechargement
                                                             au lieu d'afficher le rendu. Le composant audio porte
                                                             deja ses propres liens, d'ou l'exclusion ci-dessus. --}}
                                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-block text-blue-600 hover:underline">
                                                            Ouvrir en plein écran ({{ strtoupper($ext) }})
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="border-b py-4 px-4 min-w-[250px]">
                                        @if($submission)
                                            <form action="{{ route('coach.evaluations.store', $submission) }}" method="POST" class="flex flex-col gap-2">
                                                @csrf
                                                <input type="number" name="score" min="0" max="20" step="0.5" value="{{ $grade->score ?? '' }}" placeholder="Note" required class="w-24 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                                {{-- textarea et non input : une correction tient rarement
                                                     sur une seule ligne. --}}
                                                <textarea name="feedback" rows="4" placeholder="Commentaire de correction..." class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ $grade->feedback ?? '' }}</textarea>
                                                <button type="submit" class="bg-indigo-600 text-white px-3 py-2 rounded hover:bg-indigo-700 text-xs self-start">Enregistrer la note</button>
                                            </form>
                                        @else
                                            <div class="text-center text-gray-400 text-sm italic">En attente de soumission</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-500">Aucun étudiant dans cette classe.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
