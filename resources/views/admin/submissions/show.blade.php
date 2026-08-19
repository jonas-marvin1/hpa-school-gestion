<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Détail du rendu') }}
            </h2>
            <a href="{{ route('admin.submissions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                &larr; Retour à l'archive
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">{{ $submission->assignment->title ?? 'Devoir supprimé' }}</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Apprenant</dt>
                            <dd class="text-gray-900">{{ $submission->student->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Classe</dt>
                            <dd class="text-gray-900">{{ $submission->assignment->courseClass->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Programme</dt>
                            <dd class="text-gray-900">{{ $submission->assignment->courseClass->level->program->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Formateur</dt>
                            <dd class="text-gray-900">{{ $submission->assignment->coach->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Le rendu de l'apprenant --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-1">Rendu de l'apprenant</h3>
                    <p class="text-xs text-gray-500 mb-4">Déposé le {{ $submission->created_at->format('d/m/Y à H:i') }}</p>

                    @if(filled($submission->file_path))
                        @php
                            $ext = strtolower(pathinfo($submission->file_path, PATHINFO_EXTENSION));
                            $url = asset('storage/' . $submission->file_path);
                        @endphp
                        <div class="space-y-2">
                            @if(in_array($ext, ['mp3', 'm4a', 'mp4', 'wav', 'ogg', 'oga', 'webm', 'weba', 'aac', '3gp', '3gpp', 'opus']))
                                <x-rendu-audio :chemin="$submission->file_path" :auteur="$submission->student->name ?? null" />
                            @else
                                @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                    <a href="{{ $url }}" target="_blank" rel="noopener">
                                        <img src="{{ $url }}" alt="Rendu de {{ $submission->student->name ?? '' }}" class="max-h-96 rounded border border-gray-200">
                                    </a>
                                @elseif($ext === 'pdf')
                                    <embed src="{{ $url }}" type="application/pdf" class="w-full h-96 rounded border border-gray-200">
                                @endif
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="inline-block text-indigo-600 hover:underline">
                                    Ouvrir en plein écran ({{ strtoupper($ext) }})
                                </a>
                            @endif
                        </div>
                    @elseif(filled($submission->content_text))
                        <div class="whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-3 text-sm leading-relaxed">{{ $submission->content_text }}</div>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucun contenu enregistré.</p>
                    @endif
                </div>
            </div>

            {{-- Correction --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Correction</h3>

                    @if($submission->grade)
                        @php
                            // Une note dont la date de modification depasse la date de
                            // creation a ete revue apres coup : on le signale plutot que
                            // de laisser croire a une correction unique.
                            $revisee = $submission->grade->updated_at->gt($submission->grade->created_at->addMinute());
                        @endphp
                        <div class="flex flex-wrap items-baseline gap-x-6 gap-y-2 mb-4">
                            <span class="text-3xl font-bold {{ $submission->grade->score >= 10 ? 'text-green-600' : 'text-amber-600' }}">
                                {{ rtrim(rtrim(number_format($submission->grade->score, 1, ',', ''), '0'), ',') }}/20
                            </span>
                            <span class="text-sm text-gray-500">
                                par {{ $submission->grade->coach->name ?? 'formateur supprimé' }}
                            </span>
                        </div>

                        @if($submission->grade->feedback)
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Commentaire</h4>
                                <div class="whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-3 text-sm leading-relaxed">{{ $submission->grade->feedback }}</div>
                            </div>
                        @endif

                        @if($revisee)
                            <p class="mt-4 text-xs text-gray-500">
                                Cette note a été revue après sa première saisie. Le détail figure dans l'historique ci-dessous.
                            </p>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 italic">Ce rendu n'a pas encore été corrigé.</p>
                    @endif
                </div>
            </div>

            {{-- Historique des modifications, issu du journal (table revisions) --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Historique des modifications</h3>

                    @php
                        $libelles = [
                            'score'         => 'Note',
                            'feedback'      => 'Commentaire',
                            'content_text'  => 'Texte du rendu',
                            'file_path'     => 'Fichier joint',
                            'coach_id'      => 'Correcteur',
                            'submission_id' => 'Rendu associé',
                            'student_id'    => 'Apprenant',
                            'assignment_id' => 'Devoir',
                            'submitted_at'  => 'Date de dépôt',
                        ];
                    @endphp

                    @forelse($historique as $entree)
                        @php $r = $entree['revision']; @endphp
                        <div class="border-l-2 border-gray-200 pl-4 pb-5 last:pb-0 relative">
                            <span class="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full bg-indigo-500"></span>
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span class="text-sm font-medium">{{ $r->libelleAction() }} &middot; {{ $entree['objet'] }}</span>
                                <span class="text-xs text-gray-500">{{ $r->created_at->format('d/m/Y à H:i') }}</span>
                                <span class="text-xs text-gray-500">par {{ $r->user->name ?? 'traitement automatique' }}</span>
                            </div>

                            @if($r->action === 'updated' && filled($r->changes))
                                <ul class="mt-2 space-y-1 text-sm">
                                    @foreach($r->changes as $champ => $v)
                                        <li class="text-gray-700">
                                            <span class="font-medium">{{ $libelles[$champ] ?? $champ }}</span> :
                                            <span class="line-through text-gray-400 break-words">{{ \Illuminate\Support\Str::limit($v['before'] ?? '—', 80) }}</span>
                                            <span class="mx-1 text-gray-400">&rarr;</span>
                                            <span class="text-gray-900 break-words">{{ \Illuminate\Support\Str::limit($v['after'] ?? '—', 80) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic">
                            Aucune modification enregistrée. Le journal ne couvre que les changements survenus depuis sa mise en service.
                        </p>
                    @endforelse
                </div>
            </div>

            <p class="text-xs text-gray-500">
                Consultation seule. La note et le commentaire ne sont modifiables que par le formateur, depuis son espace de correction.
            </p>

        </div>
    </div>
</x-app-layout>
