<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mon emploi du temps') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide mb-4">
                        À venir <span class="text-gray-400 normal-case">({{ $aVenir->count() }})</span>
                    </h3>

                    @forelse($aVenir as $s)
                        @php $aujourdhui = $s->start_time->isToday(); @endphp
                        <div class="flex flex-wrap items-center gap-4 rounded-lg border {{ $aujourdhui ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200' }} p-4 mb-3 last:mb-0">
                            <div class="text-center shrink-0 w-16">
                                <p class="text-2xl font-bold {{ $aujourdhui ? 'text-indigo-700' : 'text-gray-900' }}">{{ $s->start_time->format('d') }}</p>
                                <p class="text-xs uppercase text-gray-500">{{ $s->start_time->translatedFormat('M') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">{{ $s->courseClass->name ?? '—' }}</p>
                                <p class="text-sm text-gray-700">
                                    {{ $s->start_time->translatedFormat('l') }}
                                    de {{ $s->start_time->format('H:i') }} à {{ $s->end_time->format('H:i') }}
                                    @if($aujourdhui)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Aujourd'hui</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">Formateur : {{ $s->coach->name ?? '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic">Aucun cours programmé pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide mb-4">
                        Séances passées <span class="text-gray-400 normal-case">({{ $passees->total() }})</span>
                    </h3>

                    @if($passees->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b py-2 px-3">Date</th>
                                        <th class="border-b py-2 px-3">Horaires</th>
                                        <th class="border-b py-2 px-3">Classe</th>
                                        <th class="border-b py-2 px-3">Formateur</th>
                                        <th class="border-b py-2 px-3 text-center">État</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($passees as $s)
                                        <tr class="hover:bg-gray-50">
                                            <td class="border-b py-2 px-3">{{ $s->start_time->format('d/m/Y') }}</td>
                                            <td class="border-b py-2 px-3 text-gray-600">{{ $s->start_time->format('H:i') }} – {{ $s->end_time->format('H:i') }}</td>
                                            <td class="border-b py-2 px-3">{{ $s->courseClass->name ?? '—' }}</td>
                                            <td class="border-b py-2 px-3 text-gray-600">{{ $s->coach->name ?? '—' }}</td>
                                            <td class="border-b py-2 px-3 text-center">
                                                @if($s->status === 'cancelled')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Annulée</span>
                                                @elseif(in_array($s->status, ['completed', 'validated']))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Effectuée</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Prévue</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $passees->links() }}</div>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucune séance passée.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
