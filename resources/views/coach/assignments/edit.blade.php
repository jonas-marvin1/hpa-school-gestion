<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier le devoir: {{ $assignment->title }}
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

                    <form action="{{ route('coach.assignments.update', $assignment) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Titre -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700">Titre du devoir *</label>
                                <input type="text" name="title" id="title" value="{{ old('title', $assignment->title) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Classe et apprenant -->
                            <x-classe-apprenant-select
                                :classes="$classes"
                                :selected-class="old('course_class_id', $assignment->course_class_id)"
                                :selected-student="old('student_id', $assignment->student_id)"
                            />

                            <!-- Type -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Type de rendu attendu *</label>
                                <select name="type" id="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="document.getElementById('link_container').style.display = this.value === 'link' ? 'block' : 'none';">
                                    <option value="text" {{ old('type', $assignment->type) == 'text' ? 'selected' : '' }}>Texte (Saisie en ligne)</option>
                                    <option value="file" {{ old('type', $assignment->type) == 'file' ? 'selected' : '' }}>Fichier (Upload)</option>
                                    <option value="qcm" {{ old('type', $assignment->type) == 'qcm' ? 'selected' : '' }}>QCM</option>
                                    <option value="link" {{ old('type', $assignment->type) == 'link' ? 'selected' : '' }}>Lien d'évaluation</option>
                                    <option value="audio" {{ old('type', $assignment->type) == 'audio' ? 'selected' : '' }}>Audio (lecture, prononciation, oral)</option>
                                </select>
                            </div>

                            <!-- Lien d'évaluation -->
                            <div id="link_container" style="display: {{ old('type', $assignment->type) == 'link' ? 'block' : 'none' }};">
                                <label for="evaluation_link" class="block text-sm font-medium text-gray-700">Lien du devoir externe (Optionnel/Requis pour le type Lien)</label>
                                <input type="url" name="evaluation_link" id="evaluation_link" value="{{ old('evaluation_link', $assignment->evaluation_link) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://...">
                            </div>

                            <!-- Date limite -->
                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700">Date limite *</label>
                                <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $assignment->due_date ? $assignment->due_date->format('Y-m-d') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Consignes et Description *</label>
                                <textarea name="description" id="description" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $assignment->description) }}</textarea>
                            </div>

                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Mettre à jour le devoir
                            </button>
                            <a href="{{ route('coach.assignments.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
