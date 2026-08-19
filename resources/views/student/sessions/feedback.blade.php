<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Donner mon avis sur la session du {{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-6 p-4 bg-gray-50 rounded-md border border-gray-200">
                        <h3 class="text-lg font-semibold mb-2">Détails de la session</h3>
                        <p><strong>Classe :</strong> {{ $session->courseClass->name }}</p>
                        {{-- Le formateur est porte par la seance (class_sessions.coach_id),
                             pas par la classe : CourseClass n'a pas de relation coach(),
                             donc l'ancien appel renvoyait toujours "Non assigne". --}}
                        <p><strong>Formateur :</strong> {{ $session->coach->name ?? 'Non assigné' }}</p>
                        <p><strong>Date :</strong> {{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }} de {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} à {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>- {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('student.sessions.feedback.store', $session) }}" method="POST">
                        @csrf
                        
                        <div class="space-y-6">
                            <div>
                                <label for="rating" class="block text-sm font-medium text-gray-700">Note (sur 5) *</label>
                                <select name="rating" id="rating" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled selected>Sélectionnez une note...</option>
                                    <option value="5" {{ old('rating') == '5' ? 'selected' : '' }}>5 - Excellent</option>
                                    <option value="4" {{ old('rating') == '4' ? 'selected' : '' }}>4 - Très bien</option>
                                    <option value="3" {{ old('rating') == '3' ? 'selected' : '' }}>3 - Bien</option>
                                    <option value="2" {{ old('rating') == '2' ? 'selected' : '' }}>2 - Passable</option>
                                    <option value="1" {{ old('rating') == '1' ? 'selected' : '' }}>1 - Insuffisant</option>
                                </select>
                            </div>

                            <div>
                                <label for="feedback" class="block text-sm font-medium text-gray-700">Votre avis ou commentaire sur cette session *</label>
                                <p class="text-xs text-gray-500 mb-2">Ce commentaire sera soumis à l'administrateur pour validation avant d'être visible.</p>
                                <textarea name="feedback" id="feedback" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Comment s'est passée la session ? Avez-vous rencontré des difficultés ?...">{{ old('feedback') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Envoyer mon avis
                            </button>
                            <a href="{{ route('student.dashboard') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
