<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Mes prochains cours') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-5">
                        <span class="font-semibold text-gray-900">{{ $seances->total() }}</span>
                        séance{{ $seances->total() > 1 ? 's' : '' }} à venir.
                    </p>

                    @if($seances->count())
                        <div class="space-y-3">
                            @foreach($seances as $s)
                                @php $aujourdhui = $s->start_time->isToday(); @endphp
                                <div class="flex flex-wrap items-center gap-4 rounded-lg border {{ $aujourdhui ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200' }} p-4">
                                    <div class="text-center shrink-0 w-16">
                                        <p class="text-2xl font-bold {{ $aujourdhui ? 'text-indigo-700' : 'text-gray-900' }}">{{ $s->start_time->format('d') }}</p>
                                        <p class="text-xs uppercase text-gray-500">{{ $s->start_time->translatedFormat('M') }}</p>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-900">{{ $s->courseClass->name ?? '—' }}</p>
                                        <p class="text-sm text-gray-500">{{ $s->courseClass->level->program->name ?? '' }}</p>
                                        <p class="text-sm text-gray-700 mt-0.5">
                                            {{ $s->start_time->translatedFormat('l') }}
                                            de {{ $s->start_time->format('H:i') }} à {{ $s->end_time->format('H:i') }}
                                            @if($aujourdhui)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">Aujourd'hui</span>
                                            @endif
                                        </p>
                                    </div>
                                    <a href="{{ route('coach.sessions.show', $s) }}" class="text-sm text-indigo-600 hover:underline shrink-0">Détails</a>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5">{{ $seances->links() }}</div>
                    @else
                        <p class="text-sm text-gray-500 italic">Aucun cours programmé pour le moment.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
