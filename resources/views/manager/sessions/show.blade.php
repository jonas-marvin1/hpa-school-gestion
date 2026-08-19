<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Détail de la session') }}
            </h2>
            <a href="{{ route('manager.sessions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                &larr; Retour au planning
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Informations de la seance --}}
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
                                'scheduled' => ['Prévue',    'bg-blue-100 text-blue-800'],
                                'completed' => ['Effectuée', 'bg-green-100 text-green-800'],
                                'validated' => ['Validée',   'bg-emerald-100 text-emerald-800'],
                                'cancelled' => ['Annulée',   'bg-red-100 text-red-800'],
                            ];
                            [$label, $classes] = $badges[$session->status] ?? [$session->status, 'bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">{{ $label }}</span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Formateur</dt>
                            <dd class="text-gray-900">{{ $session->coach->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Date</dt>
                            <dd class="text-gray-900">{{ $session->start_time->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Horaires</dt>
                            <dd class="text-gray-900">{{ $session->start_time->format('H:i') }} &ndash; {{ $session->end_time->format('H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500">Rémunération</dt>
                            <dd class="text-gray-900">{{ number_format($session->amount, 0, ',', ' ') }} FCFA</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Rapport du coach : obligatoire pour valider la seance --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-baseline gap-3 mb-4">
                        <h3 class="text-lg font-semibold">Rapport du formateur</h3>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Obligatoire</span>
                    </div>

                    @if($session->sessionReport)
                        <p class="text-xs text-gray-500 mb-4">
                            Remis le {{ $session->sessionReport->created_at->format('d/m/Y à H:i') }}
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
                        <div class="rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                            Aucun rapport n'a été remis. La séance ne peut pas être validée tant qu'il manque.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Avis des apprenants : facultatifs, ils ne conditionnent pas la validation --}}
            @php
                $avis = $session->attendances->filter(fn ($a) => filled($a->feedback) || filled($a->rating));
            @endphp
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-baseline gap-3 mb-4">
                        <h3 class="text-lg font-semibold">Avis des apprenants</h3>
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Facultatif</span>
                        @if($avis->count())
                            <span class="text-sm text-gray-500">({{ $avis->count() }} sur {{ $session->attendances->count() }})</span>
                        @endif
                    </div>

                    @forelse($avis as $a)
                        <div class="border-b border-gray-100 last:border-0 py-3 first:pt-0 last:pb-0">
                            <div class="flex items-baseline justify-between gap-3 mb-1">
                                <span class="font-medium text-sm">{{ $a->student->name ?? 'Apprenant supprimé' }}</span>
                                @if($a->rating)
                                    <span class="text-amber-500 text-sm">{{ str_repeat('★', (int) $a->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $a->rating)) }}</span>
                                @endif
                            </div>
                            @if($a->feedback)
                                <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ $a->feedback }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic">Aucun avis déposé pour cette séance.</p>
                    @endforelse
                </div>
            </div>

            {{-- Feuille de presence --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @php
                        $presents = $session->attendances->where('is_present', true)->count();
                        $total    = $session->attendances->count();
                    @endphp
                    <h3 class="text-lg font-semibold mb-4">
                        Présences
                        @if($total > 0)<span class="text-sm font-normal text-gray-500">({{ $presents }}/{{ $total }})</span>@endif
                    </h3>

                    @if($total > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b py-2 px-4">Apprenant</th>
                                        <th class="border-b py-2 px-4 text-center">Présence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($session->attendances as $a)
                                        <tr>
                                            <td class="border-b py-3 px-4 font-medium">{{ $a->student->name ?? 'Apprenant supprimé' }}</td>
                                            <td class="border-b py-3 px-4 text-center">
                                                @if($a->is_present)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Présent</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Absent</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">Les présences sont enregistrées avec le rapport du formateur.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
