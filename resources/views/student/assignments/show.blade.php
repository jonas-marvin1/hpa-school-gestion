<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Devoir : {{ $assignment->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>- {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-start mb-4 border-b pb-4">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">{{ $assignment->title }}</h3>
                            <p class="text-sm text-gray-500 mt-1">Matière / Classe : {{ $assignment->courseClass->name }}</p>
                        </div>
                        <div class="text-right">
                            <span class="block text-sm font-semibold text-gray-600">À rendre avant le :</span>
                            <span class="block text-lg font-bold {{ \Carbon\Carbon::parse($assignment->due_date)->isPast() && !$submission ? 'text-red-600' : 'text-gray-900' }}">
                                {{ \Carbon\Carbon::parse($assignment->due_date)->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>

                    <div class="prose max-w-none text-gray-700 mb-6">
                        <h4 class="font-semibold text-gray-900 mb-2">Consignes :</h4>
                        <div class="bg-gray-50 p-4 rounded-md">
                            {!! nl2br(e($assignment->description)) !!}
                        </div>
                        @if($assignment->attachment)
                            <div class="mt-4">
                                <h4 class="font-semibold text-gray-900 mb-2">Fichier joint (Sujet / Exercices) :</h4>
                                <a href="{{ asset('storage/' . $assignment->attachment) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Télécharger la pièce jointe
                                </a>
                            </div>
                        @endif

                        @if($assignment->type === 'link' && $assignment->evaluation_link)
                            <div class="mt-4">
                                <h4 class="font-semibold text-gray-900 mb-2">Lien d'évaluation externe :</h4>
                                <a href="{{ $assignment->evaluation_link }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    Ouvrir le projet / l'évaluation
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Submission Section -->
            @if($submission)
                <!-- Already Submitted -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-green-700 mb-2">Devoir rendu</h3>
                        <p class="text-sm text-gray-600 mb-4">Vous avez soumis ce devoir le {{ \Carbon\Carbon::parse($submission->created_at)->format('d/m/Y à H:i') }}.</p>
                        
                        <div class="bg-gray-50 p-4 rounded-md text-sm text-gray-700 mb-4">
                            @if($assignment->type === 'text')
                                <strong>Votre réponse :</strong><br>
                                {!! nl2br(e($submission->content_text)) !!}
                            @elseif($assignment->type === 'link')
                                <strong>Lien soumis :</strong><br>
                                <a href="{{ $submission->content_text }}" target="_blank" class="text-blue-600 hover:underline break-all">{{ $submission->content_text }}</a>
                            @else
                                <strong>Fichier joint :</strong><br>
                                <a href="#" class="text-blue-600 hover:underline">Télécharger mon fichier</a>
                            @endif
                        </div>

                        <!-- Grading Section -->
                        @if($submission->grade)
                            <div class="mt-6 border-t pt-4">
                                <h4 class="font-bold text-gray-900 mb-2">Évaluation du Formateur</h4>
                                <div class="flex items-center gap-4 mb-2">
                                    <span class="text-2xl font-bold {{ $submission->grade->score >= 10 ? 'text-green-600' : 'text-red-600' }}">{{ $submission->grade->score }} / 20</span>
                                </div>
                                @if($submission->grade->feedback)
                                    <div class="text-sm italic text-gray-600 bg-yellow-50 p-3 rounded border border-yellow-200">
                                        "{{ $submission->grade->feedback }}"
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="mt-6 border-t pt-4">
                                <p class="text-sm text-gray-500 italic">En attente d'évaluation par le formateur.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- Form to Submit -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Soumettre votre travail</h3>
                        
                        <form action="{{ route('student.assignments.submit', $assignment) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            @if($assignment->type === 'text')
                                <div class="mb-4">
                                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Votre réponse (Texte) *</label>
                                    <textarea name="content" id="content" rows="6" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('content') }}</textarea>
                                </div>
                            @elseif($assignment->type === 'link')
                                <div class="mb-4">
                                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Votre lien (URL) *</label>
                                    <input type="url" name="content" id="content" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://..." value="{{ old('content') }}">
                                </div>
                            @elseif($assignment->type === 'file')
                                <div class="mb-4">
                                    <label for="file" class="block text-sm font-medium text-gray-700 mb-1">Fichier à joindre (PDF, DOCX, ZIP...) *</label>
                                    <input type="file" name="file" id="file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                            @elseif($assignment->type === 'audio')
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Votre enregistrement audio *</label>
                                    <x-enregistreur-audio />
                                </div>
                            @endif

                            <div class="mt-6">
                                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md font-semibold text-sm hover:bg-indigo-700 transition" onclick="return confirm('Êtes-vous sûr de vouloir soumettre ? (Action irréversible)')">
                                    Envoyer mon devoir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>

</x-app-layout>
