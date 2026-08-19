<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Détail de la session') }}
            </h2>
            <a href="{{ route('coach.sessions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                &larr; Retour au planning
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Informations generales --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $session->courseClass->name ?? 'Classe supprimée' }}</h3>
                            @if($session->courseClass?->level)
                                <p class="text-sm text-gray-500">
                                    {{ $session->courseClass->level->program->name ?? '' }}
                                    @if($session->courseClass->level->program) &middot; @endif
                                    {{ $session->courseClass->level->name }}
                                </p>
                            @endif
                        </div>
                        @php
                            $badges = [
                                'scheduled' => ['Prévue', 'bg-blue-100 text-blue-800'],
                                'completed' => ['Effectuée', 'bg-green-100 text-green-800'],
                                'validated' => ['Validée', 'bg-emerald-100 text-emerald-800'],
                                'cancelled' => ['Annulée', 'bg-red-100 text-red-800'],
                            ];
                            [$label, $classes] = $badges[$session->status] ?? [$session->status, 'bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">{{ $label }}</span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Date</dt>
                            <dd class="text-gray-900">{{ $session->start_time->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Horaires</dt>
                            <dd class="text-gray-900">{{ $session->start_time->format('H:i') }} &ndash; {{ $session->end_time->format('H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Durée</dt>
                            <dd class="text-gray-900">{{ $session->start_time->diffInMinutes($session->end_time) }} min</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Type d'intervention</dt>
                            <dd class="text-gray-900">{{ $session->intervention_type ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Rémunération</dt>
                            <dd class="text-gray-900">{{ number_format($session->amount, 0, ',', ' ') }}</dd>
                        </div>
                        @if($session->online_link)
                            <div class="sm:col-span-2 lg:col-span-3">
                                <dt class="font-medium text-gray-500">Lien de la séance</dt>
                                <dd>
                                    <a href="{{ $session->online_link }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline break-all">{{ $session->online_link }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Rapport de seance --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Rapport de séance</h3>

                    @if($session->sessionReport)
                        <p class="text-xs text-gray-500 mb-4">
                            Soumis le {{ $session->sessionReport->created_at->format('d/m/Y à H:i') }}
                        </p>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Résumé de la progression</h4>
                                <div class="text-sm text-gray-900 whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-3 leading-relaxed">{{ $session->sessionReport->progress }}</div>
                            </div>
                            @if($session->sessionReport->observations)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 mb-1">Observations</h4>
                                    <div class="text-sm text-gray-900 whitespace-pre-wrap break-words rounded border border-gray-200 bg-gray-50 p-3 leading-relaxed">{{ $session->sessionReport->observations }}</div>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic mb-4">Aucun rapport n'a encore été rédigé pour cette séance.</p>
                        <p class="text-sm text-gray-600 mb-4">
                            Saisie possible de
                            <span class="font-medium">{{ $session->debutFenetreRapport()->format('d/m/Y H:i') }}</span>
                            à
                            <span class="font-medium">{{ $session->finFenetreRapport()->format('H:i') }}</span>
                            ({{ \App\Models\ClassSession::MARGE_RAPPORT_MINUTES }} min avant et après la séance).
                        </p>
                        @if($session->fenetreRapportOuverte())
                            <a href="{{ route('coach.sessions.report.create', $session) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                                Rédiger le rapport
                            </a>
                        @else
                            <span class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-500 rounded-md text-sm font-medium cursor-not-allowed">
                                Rédaction fermée
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Presences --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $presents = $session->attendances->where('is_present', true)->count();
                        $total = $session->attendances->count();
                    @endphp
                    <h3 class="text-lg font-semibold mb-4">
                        Présences
                        @if($total > 0)
                            <span class="text-sm font-normal text-gray-500">({{ $presents }}/{{ $total }})</span>
                        @endif
                    </h3>

                    @if($total > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b py-2 px-4">Apprenant</th>
                                        <th class="border-b py-2 px-4 text-center">Présence</th>
                                        <th class="border-b py-2 px-4">Retour de l'apprenant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($session->attendances as $attendance)
                                        <tr>
                                            <td class="border-b py-3 px-4 font-medium">{{ $attendance->student->name ?? 'Apprenant supprimé' }}</td>
                                            <td class="border-b py-3 px-4 text-center">
                                                @if($attendance->is_present)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Présent</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Absent</span>
                                                @endif
                                            </td>
                                            <td class="border-b py-3 px-4 text-gray-700">
                                                @if($attendance->feedback)
                                                    @if($attendance->rating)
                                                        <div class="text-amber-500 mb-1">{{ str_repeat('★', (int) $attendance->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $attendance->rating)) }}</div>
                                                    @endif
                                                    <div class="whitespace-pre-wrap break-words">{{ $attendance->feedback }}</div>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">Les présences seront enregistrées avec le rapport de séance.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
