<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mes Devoirs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Titre du devoir</th>
                                <th class="border-b py-2 px-4">Classe</th>
                                <th class="border-b py-2 px-4">Date limite</th>
                                <th class="border-b py-2 px-4">Statut</th>
                                <th class="border-b py-2 px-4 text-center">Note</th>
                                <th class="border-b py-2 px-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                @php
                                    $submission = $assignment->submissions->first();
                                    $isLate = \Carbon\Carbon::parse($assignment->due_date)->isPast();
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="border-b py-3 px-4 font-medium">{{ $assignment->title }}</td>
                                    <td class="border-b py-3 px-4 text-sm text-gray-600">{{ $assignment->courseClass->name ?? 'N/A' }}</td>
                                    <td class="border-b py-3 px-4 text-sm {{ $isLate && !$submission ? 'text-red-600 font-semibold' : '' }}">
                                        {{ \Carbon\Carbon::parse($assignment->due_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="border-b py-3 px-4">
                                        @if($submission)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Rendu le {{ \Carbon\Carbon::parse($submission->created_at)->format('d/m/Y') }}
                                            </span>
                                        @elseif($isLate)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                En retard
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                À faire
                                            </span>
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4 text-center font-bold">
                                        @if($submission && $submission->grade)
                                            <span class="{{ $submission->grade->score >= 10 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $submission->grade->score }}/20
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4 text-right">
                                        <a href="{{ route('student.assignments.show', $assignment) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Consulter</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-500">Vous n'avez aucun devoir pour le moment.</td>
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
