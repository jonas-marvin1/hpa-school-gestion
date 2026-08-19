<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Créer un nouveau devoir
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

                    <form action="{{ route('coach.assignments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Titre -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700">Titre du devoir *</label>
                                <input type="text" name="title" id="title" value="{{ old('title') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Classe -->
                            <div>
                                <label for="course_class_id" class="block text-sm font-medium text-gray-700">Classe cible *</label>
                                <select name="course_class_id" id="course_class_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionnez une classe</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('course_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Type -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Type de rendu attendu *</label>
                                <select name="type" id="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="document.getElementById('link_container').style.display = this.value === 'link' ? 'block' : 'none';">
                                    <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Texte (Saisie en ligne)</option>
                                    <option value="file" {{ old('type') == 'file' ? 'selected' : '' }}>Fichier (Upload)</option>
                                    <option value="qcm" {{ old('type') == 'qcm' ? 'selected' : '' }}>QCM</option>
                                    <option value="link" {{ old('type') == 'link' ? 'selected' : '' }}>Lien d'évaluation</option>
                                    <option value="audio" {{ old('type') == 'audio' ? 'selected' : '' }}>Audio (lecture, prononciation, oral)</option>
                                </select>
                            </div>

                            <!-- Lien d'évaluation -->
                            <div id="link_container" style="display: {{ old('type') == 'link' ? 'block' : 'none' }};">
                                <label for="evaluation_link" class="block text-sm font-medium text-gray-700">Lien du devoir externe (Optionnel/Requis pour le type Lien)</label>
                                <input type="url" name="evaluation_link" id="evaluation_link" value="{{ old('evaluation_link') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://...">
                            </div>

                            <!-- Date limite -->
                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700">Date limite *</label>
                                <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Fichier joint (optionnel) -->
                            <div class="md:col-span-2">
                                <label for="attachment" class="block text-sm font-medium text-gray-700">Fichier joint au sujet (Optionnel)</label>
                                <input type="file" name="attachment" id="attachment" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="text-xs text-gray-500 mt-1">Vous pouvez joindre le sujet ou des documents pour aider les étudiants à faire le devoir.</p>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Consignes et Description *</label>
                                <textarea name="description" id="description" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            </div>

                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Créer le devoir
                            </button>
                            <a href="{{ route('coach.assignments.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
