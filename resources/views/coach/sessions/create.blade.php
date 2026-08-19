<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Déclarer une Session (Reporting)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('coach.sessions.store') }}" x-data="sessionForm()">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Classe -->
                            <div>
                                <x-input-label for="course_class_id" :value="__('Classe Concernée')" />
                                <select id="course_class_id" name="course_class_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required x-model="selectedClassId" @change="updateStudents">
                                    <option value="">Sélectionnez une classe</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('course_class_id')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Heure début -->
                                <div>
                                    <x-input-label for="start_time" :value="__('Heure de début')" />
                                    <x-text-input id="start_time" class="block mt-1 w-full" type="datetime-local" name="start_time" :value="old('start_time')" required />
                                    <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                                </div>

                                <!-- Heure fin -->
                                <div>
                                    <x-input-label for="end_time" :value="__('Heure de fin')" />
                                    <x-text-input id="end_time" class="block mt-1 w-full" type="datetime-local" name="end_time" :value="old('end_time')" required />
                                    <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Présences</h3>
                            <div class="bg-gray-50 p-4 rounded-md border border-gray-200">
                                <template x-if="students.length === 0">
                                    <p class="text-sm text-gray-500">Veuillez sélectionner une classe pour afficher les apprenants.</p>
                                </template>
                                <template x-if="students.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="student in students" :key="student.id">
                                            <div class="flex items-center">
                                                <input type="checkbox" :id="'student_'+student.id" :name="'attendances['+student.id+']'" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" checked>
                                                <label :for="'student_'+student.id" class="ml-2 text-sm text-gray-700" x-text="student.name"></label>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mb-6">
                            <x-input-label for="progress" :value="__('Progression du cours')" />
                            <textarea id="progress" name="progress" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3" required>{{ old('progress') }}</textarea>
                            <x-input-error :messages="$errors->get('progress')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="observations" :value="__('Observations (Optionnel)')" />
                            <textarea id="observations" name="observations" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2">{{ old('observations') }}</textarea>
                            <x-input-error :messages="$errors->get('observations')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="recommendations" :value="__('Recommandations (Optionnel)')" />
                            <textarea id="recommendations" name="recommendations" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2">{{ old('recommendations') }}</textarea>
                            <x-input-error :messages="$errors->get('recommendations')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('Enregistrer la session') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script to inject class users into JS -->
    @php
        $classesWithStudents = $classes->mapWithKeys(function($class) {
            return [$class->id => $class->users()->role('student')->get(['users.id', 'users.name'])];
        });
    @endphp
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sessionForm', () => ({
                classesData: @json($classesWithStudents),
                selectedClassId: '{{ old("course_class_id", "") }}',
                students: [],

                init() {
                    if (this.selectedClassId) {
                        this.updateStudents();
                    }
                },

                updateStudents() {
                    if (this.selectedClassId && this.classesData[this.selectedClassId]) {
                        this.students = this.classesData[this.selectedClassId];
                    } else {
                        this.students = [];
                    }
                }
            }))
        })
    </script>
</x-app-layout>
