<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Affectations pour : {{ $courseClass->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('manager.classes.assign.update', ['courseClass' => $courseClass->id]) }}" method="POST">
                        @csrf
                        
                        <div class="mb-6">
                            <input type="text" id="user-search" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Rechercher un formateur ou un apprenant (Nom, Email)...">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Formateurs -->
                            <div>
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Formateurs (Coaches)</h3>
                                @forelse($coaches as $coach)
                                    <div class="mb-2 flex items-center user-item">
                                        <input type="radio" name="coach_id" id="user_{{ $coach->id }}" value="{{ $coach->id }}" 
                                            class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            {{ in_array($coach->id, $assignedUserIds) ? 'checked' : '' }} required>
                                        <label for="user_{{ $coach->id }}" class="ml-2 text-sm text-gray-700 user-label">{{ $coach->name }} ({{ $coach->email }})</label>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Aucun formateur enregistré.</p>
                                @endforelse
                            </div>

                            <!-- Apprenants -->
                            <div>
                                <h3 class="font-bold text-lg mb-4 border-b pb-2">Apprenants (Students)</h3>
                                @forelse($students as $student)
                                    <div class="mb-2 flex items-center user-item">
                                        <input type="checkbox" name="student_ids[]" id="user_{{ $student->id }}" value="{{ $student->id }}" 
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            {{ in_array($student->id, $assignedUserIds) ? 'checked' : '' }}>
                                        <label for="user_{{ $student->id }}" class="ml-2 text-sm text-gray-700 user-label">{{ $student->name }} ({{ $student->email }})</label>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Aucun apprenant enregistré.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Enregistrer les affectations
                            </button>
                            <a href="{{ route('manager.classes.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('user-search');
            const userItems = document.querySelectorAll('.user-item');

            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                
                userItems.forEach(function(item) {
                    const label = item.querySelector('.user-label').textContent.toLowerCase();
                    if (label.includes(term)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
</x-app-layout>
