<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Modifier la Session
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

                    @if($session->payment_id)
                        <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded relative">
                            Cette session est déjà facturée (une fiche de paie a été générée) et ne peut plus être modifiée.
                        </div>
                    @endif

                    <form action="{{ route('manager.sessions.update', $session) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Classe -->
                            <div>
                                <label for="course_class_id" class="block text-sm font-medium text-gray-700">Classe</label>
                                <x-searchable-select name="course_class_id" :options="$classes" :selected="old('course_class_id', $session->course_class_id)" placeholder="Rechercher une classe..." required id="course_class_id" />
                            </div>

                            <!-- Formateur -->
                            <div>
                                <label for="coach_id" class="block text-sm font-medium text-gray-700">Formateur</label>
                                <x-searchable-select name="coach_id" :options="$coaches" :selected="old('coach_id', $session->coach_id)" placeholder="Rechercher un formateur..." required id="coach_id" />
                            </div>

                            <!-- Heure de début -->
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700">Heure de début</label>
                                <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time', $session->start_time->format('Y-m-d\TH:i')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Heure de fin -->
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700">Heure de fin</label>
                                <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time', $session->end_time->format('Y-m-d\TH:i')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Montant de la session -->
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700">Montant de la session (FCFA) *</label>
                                <input type="number" step="0.01" min="0" name="amount" id="amount" value="{{ old('amount', $session->amount) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Type d'intervention -->
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Type d'intervention *</span>
                                <div class="mt-2 flex items-center gap-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="intervention_type" value="in_person" {{ old('intervention_type', $session->intervention_type) == 'in_person' ? 'checked' : '' }} required class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2">Présentiel</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="intervention_type" value="online" {{ old('intervention_type', $session->intervention_type) == 'online' ? 'checked' : '' }} required class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2">En ligne</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Lien de visioconférence -->
                            <div class="md:col-span-2">
                                <label for="online_link" class="block text-sm font-medium text-gray-700">Lien de la visioconférence (Si en ligne)</label>
                                <input type="url" name="online_link" id="online_link" value="{{ old('online_link', $session->online_link) }}" placeholder="https://meet.google.com/..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Laissez vide si le cours est en présentiel.</p>
                            </div>
                            
                            <!-- Statut -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                                <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="scheduled" {{ old('status', $session->status) === 'scheduled' ? 'selected' : '' }}>Prévue</option>
                                    <option value="completed" {{ old('status', $session->status) === 'completed' ? 'selected' : '' }}>Effectuée</option>
                                    <option value="validated" {{ old('status', $session->status) === 'validated' ? 'selected' : '' }}>Validée</option>
                                    <option value="cancelled" {{ old('status', $session->status) === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                </select>
                            </div>

                        </div>

                        @if($session->sessionReport)
                        <div class="mt-8 bg-blue-50 p-6 rounded-lg border border-blue-200">
                            <h3 class="font-bold text-lg mb-4 text-blue-800 border-b border-blue-200 pb-2">Rapport du Formateur</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-semibold text-gray-700">Progression / Résumé :</span>
                                    <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{{ $session->sessionReport->progress ?? 'Aucun résumé' }}</p>
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-700">Observations :</span>
                                    <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{{ $session->sessionReport->observations ?? 'Aucune observation' }}</p>
                                </div>
                            </div>
                            
                            <!-- Présences -->
                            <div class="mt-4 border-t border-blue-200 pt-4">
                                <span class="font-semibold text-gray-700">Présences marquées par le formateur :</span>
                                @if($session->attendances->count() > 0)
                                    <ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($session->attendances as $attendance)
                                            <li class="text-sm flex items-center">
                                                @if($attendance->is_present)
                                                    <span class="text-green-600 mr-2">✓</span>
                                                @else
                                                    <span class="text-red-600 mr-2">✗</span>
                                                @endif
                                                {{ $attendance->student->name ?? 'Étudiant inconnu' }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-gray-500 mt-1">Aucune présence renseignée.</p>
                                @endif
                            </div>

                            <!-- Avis des étudiants -->
                            <div class="mt-4 border-t border-blue-200 pt-4">
                                <span class="font-semibold text-gray-700">Avis des étudiants :</span>
                                @php
                                    $feedbacks = $session->attendances->filter(function($att) { return !is_null($att->feedback); });
                                @endphp
                                @if($feedbacks->count() > 0)
                                    <div class="mt-3 space-y-3">
                                        @foreach($feedbacks as $attendance)
                                            <div class="bg-white p-3 rounded border border-gray-200">
                                                <span class="font-semibold text-sm text-indigo-700">{{ $attendance->student->name ?? 'Étudiant inconnu' }} :</span>
                                                <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{{ $attendance->feedback }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 mt-1">Aucun avis laissé par les étudiants pour le moment.</p>
                                @endif
                            </div>

                            <div class="mt-4 p-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 text-sm">
                                <strong>Action requise :</strong> Pour valider ce rapport, changez le statut ci-dessus en <strong>"Validée"</strong> puis enregistrez.
                            </div>
                        </div>
                        @endif

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Mettre à jour la session
                            </button>
                            <a href="{{ route('manager.sessions.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
