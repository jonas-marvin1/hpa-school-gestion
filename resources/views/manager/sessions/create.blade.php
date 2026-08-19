<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nouvelle Session
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

                    <form action="{{ route('manager.sessions.store') }}" method="POST">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Classe -->
                            <div>
                                <label for="course_class_id" class="block text-sm font-medium text-gray-700">Classe</label>
                                <x-searchable-select name="course_class_id" :options="$classes" :selected="old('course_class_id')" placeholder="Rechercher une classe..." required id="course_class_id" />
                            </div>

                            <!-- Formateur -->
                            <div>
                                <label for="coach_id" class="block text-sm font-medium text-gray-700">Formateur</label>
                                <x-searchable-select name="coach_id" :options="$coaches" :selected="old('coach_id')" placeholder="Rechercher un formateur..." required id="coach_id" />
                            </div>

                            <!-- Heure de début -->
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700">Heure de début</label>
                                <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Heure de fin -->
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700">Heure de fin</label>
                                <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Montant de la session -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Montant de la session (FCFA) *</label>
                                <input type="number" step="0.01" min="0" name="amount" id="amount" value="{{ old('amount') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Type d'intervention -->
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Type d'intervention *</span>
                                <div class="mt-2 flex items-center gap-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="intervention_type" value="in_person" {{ old('intervention_type') == 'in_person' ? 'checked' : '' }} required class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2">Présentiel</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="intervention_type" value="online" {{ old('intervention_type') == 'online' ? 'checked' : '' }} required class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2">En ligne</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Lien de visioconférence -->
                            <div class="md:col-span-2">
                                <label for="online_link" class="block text-sm font-medium text-gray-700">Lien de la visioconférence (Si en ligne)</label>
                                <input type="url" name="online_link" id="online_link" value="{{ old('online_link') }}" placeholder="https://meet.google.com/..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Laissez vide si le cours est en présentiel.</p>
                            </div>

                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Programmer la session
                            </button>
                            <a href="{{ route('manager.sessions.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
