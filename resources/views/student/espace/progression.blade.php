<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Ma progression') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide mb-6">Mon niveau d'anglais</h3>
                    <x-frise-niveaux :echelle="$echelleNiveaux" :niveau="$niveauActuel" :anime="true" />
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide mb-6">Mes indicateurs</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @php
                            $moyenne = $averageGrade !== null ? round($averageGrade, 1) : null;
                            $jauges = [
                                ['Avancement du cursus', $courseProgress, 'bg-indigo-600', 'text-indigo-600', $sessionsDone . ' séance' . ($sessionsDone > 1 ? 's' : '') . ' sur ' . $sessionsTotal],
                                ['Devoirs rendus', $assignmentProgress, 'bg-blue-600', 'text-blue-600', $assignmentsDone . ' sur ' . $assignmentsTotal . ' demandé' . ($assignmentsTotal > 1 ? 's' : '')],
                                ['Assiduité', $attendanceRate, $attendanceRate >= 80 ? 'bg-green-600' : 'bg-red-500', $attendanceRate >= 80 ? 'text-green-600' : 'text-red-600', 'Sur ' . $totalSessions . ' cours'],
                            ];
                        @endphp

                        @foreach($jauges as [$titre, $valeur, $couleurBarre, $couleurTexte, $detail])
                            <div>
                                <div class="flex items-baseline justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">{{ $titre }}</span>
                                    <span class="text-sm font-bold {{ $couleurTexte }}">{{ $valeur }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="{{ $couleurBarre }} h-3 rounded-full transition-all duration-500" style="width: {{ $valeur }}%"></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">{{ $detail }}</p>
                            </div>
                        @endforeach

                        <div>
                            <div class="flex items-baseline justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Moyenne générale</span>
                                <span class="text-sm font-bold {{ $moyenne !== null && $moyenne >= 10 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $moyenne !== null ? $moyenne . '/20' : '—' }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="{{ $moyenne !== null && $moyenne >= 10 ? 'bg-green-600' : 'bg-amber-500' }} h-3 rounded-full transition-all duration-500"
                                     style="width: {{ $moyenne !== null ? min(100, $moyenne / 20 * 100) : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">
                                {{ $moyenne !== null ? "Sur l'ensemble des devoirs notés" : 'Aucun devoir noté pour l\'instant' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide mb-4">
                        Mes notes <span class="text-gray-400 normal-case">({{ $notes->count() }})</span>
                    </h3>

                    @if($notes->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b py-2 px-3">Devoir</th>
                                        <th class="border-b py-2 px-3">Rendu le</th>
                                        <th class="border-b py-2 px-3 text-center">Note</th>
                                        <th class="border-b py-2 px-3">Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notes as $n)
                                        <tr class="hover:bg-gray-50">
                                            <td class="border-b py-2 px-3 font-medium">{{ $n->assignment->title ?? '—' }}</td>
                                            <td class="border-b py-2 px-3 text-gray-600">{{ $n->created_at->format('d/m/Y') }}</td>
                                            <td class="border-b py-2 px-3 text-center">
                                                <span class="font-bold {{ $n->grade->score >= 10 ? 'text-green-600' : 'text-amber-600' }}">
                                                    {{ rtrim(rtrim(number_format($n->grade->score, 1, ',', ''), '0'), ',') }}/20
                                                </span>
                                            </td>
                                            <td class="border-b py-2 px-3 text-gray-700 max-w-md">
                                                <div class="whitespace-pre-wrap break-words">{{ $n->grade->feedback ?: '—' }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucun devoir noté pour l'instant.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
