<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Rapport de session : {{ $session->courseClass->name }} le {{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>- {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('coach.sessions.report.store', $session) }}" method="POST">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <!-- Rapport Pédagogique -->
                            <div class="space-y-4">
                                <h3 class="font-bold text-lg border-b pb-2">Rapport Pédagogique</h3>
                                
                                <div>
                                    <label for="progress_summary" class="block text-sm font-medium text-gray-700">Résumé de la progression *</label>
                                    <textarea name="progress_summary" id="progress_summary" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Thèmes abordés, objectifs atteints...">{{ old('progress_summary') }}</textarea>
                                </div>
                                
                                <div>
                                    <label for="observations" class="block text-sm font-medium text-gray-700">Observations (Optionnel)</label>
                                    <textarea name="observations" id="observations" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Remarques générales, difficultés rencontrées, etc.">{{ old('observations') }}</textarea>
                                </div>
                            </div>

                            <!-- Présences -->
                            <div>
                                <h3 class="font-bold text-lg border-b pb-2 mb-4">Présences</h3>
                                <p class="text-sm text-gray-500 mb-4">Cochez les étudiants qui étaient présents à cette session.</p>
                                
                                <div class="bg-gray-50 p-4 rounded-md">
                                    @forelse($students as $student)
                                        <div class="mb-3 flex items-center">
                                            <input type="checkbox" name="attendances[{{ $student->id }}]" id="student_{{ $student->id }}" value="1" 
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5"
                                                {{ old('attendances.'.$student->id) ? 'checked' : '' }}>
                                            <label for="student_{{ $student->id }}" class="ml-3 text-sm font-medium text-gray-700">
                                                {{ $student->name }}
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-sm text-yellow-600">Aucun étudiant assigné à cette classe.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-4 border-t pt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Valider le rapport et les présences
                            </button>
                            <a href="{{ route('coach.dashboard') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
