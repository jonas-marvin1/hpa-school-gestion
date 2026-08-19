<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Évaluations — archive des devoirs rendus') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filtres. Le volume de rendus est appele a croitre : on filtre
                 cote base, jamais en chargeant tout puis en triant a l'ecran. --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.submissions.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                        <div class="lg:col-span-3">
                            <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="Nom de l'apprenant ou titre du devoir…"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="class_id" class="block text-sm font-medium text-gray-700">Classe</label>
                            <select name="class_id" id="class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">Apprenant</label>
                            <select name="student_id" id="student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les apprenants</option>
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="coach_id" class="block text-sm font-medium text-gray-700">Formateur</label>
                            <select name="coach_id" id="coach_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les formateurs</option>
                                @foreach($coaches as $c)
                                    <option value="{{ $c->id }}" {{ request('coach_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="program_id" class="block text-sm font-medium text-gray-700">Programme</label>
                            <select name="program_id" id="program_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les programmes</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="graded" class="block text-sm font-medium text-gray-700">Correction</label>
                            <select name="graded" id="graded" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous</option>
                                <option value="yes" {{ request('graded') === 'yes' ? 'selected' : '' }}>Corrigés</option>
                                <option value="no"  {{ request('graded') === 'no'  ? 'selected' : '' }}>En attente de correction</option>
                            </select>
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-medium hover:bg-gray-700">Filtrer</button>
                            @if(request()->hasAny(['search', 'class_id', 'student_id', 'coach_id', 'program_id', 'graded']))
                                <a href="{{ route('admin.submissions.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:underline">Réinitialiser</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-500 mb-4">
                        {{ $submissions->total() }} rendu{{ $submissions->total() > 1 ? 's' : '' }}.
                        Cette archive est en consultation seule : la correction relève du formateur.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr>
                                    <th class="border-b py-2 px-4">Rendu le</th>
                                    <th class="border-b py-2 px-4">Apprenant</th>
                                    <th class="border-b py-2 px-4">Devoir</th>
                                    <th class="border-b py-2 px-4">Classe</th>
                                    <th class="border-b py-2 px-4">Formateur</th>
                                    <th class="border-b py-2 px-4 text-center">Note</th>
                                    <th class="border-b py-2 px-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($submissions as $s)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border-b py-3 px-4 whitespace-nowrap">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="border-b py-3 px-4 font-medium">{{ $s->student->name ?? '—' }}</td>
                                        <td class="border-b py-3 px-4">{{ $s->assignment->title ?? '—' }}</td>
                                        <td class="border-b py-3 px-4">{{ $s->assignment->courseClass->name ?? '—' }}</td>
                                        <td class="border-b py-3 px-4">{{ $s->assignment->coach->name ?? '—' }}</td>
                                        <td class="border-b py-3 px-4 text-center">
                                            @if($s->grade)
                                                <span class="font-semibold">{{ rtrim(rtrim(number_format($s->grade->score, 1, ',', ''), '0'), ',') }}/20</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">En attente</span>
                                            @endif
                                        </td>
                                        <td class="border-b py-3 px-4 text-center">
                                            <a href="{{ route('admin.submissions.show', $s) }}" class="text-indigo-600 hover:underline">Consulter</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-gray-500">Aucun rendu ne correspond à ces critères.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $submissions->links() }}</div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
