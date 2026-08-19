<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($class) ? 'Modifier la classe' : 'Créer une classe' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                    <!-- Note: the variable passed could be named 'class', we use it with care -->
                    <form action="{{ isset($class) ? route('admin.classes.update', $class) : route('admin.classes.store') }}" method="POST">
                        @csrf
                        @if(isset($class))
                            @method('PUT')
                        @endif
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Level -->
                            <div class="md:col-span-2">
                                <label for="level_id" class="block text-sm font-medium text-gray-700">Niveau rattaché *</label>
                                <select name="level_id" id="level_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Sélectionnez un niveau</option>
                                    @foreach($levels as $level)
                                        <option value="{{ $level->id }}" {{ old('level_id', $class->level_id ?? '') == $level->id ? 'selected' : '' }}>
                                            {{ optional($level->program)->name }} - {{ $level->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Name -->
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">Nom de la classe *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $class->name ?? '') }}" required placeholder="Ex: Promotion 2024 - A" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700">Date de début *</label>
                                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', isset($class) ? \Carbon\Carbon::parse($class->start_date)->format('Y-m-d') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700">Date de fin *</label>
                                <input type="date" name="end_date" id="end_date" value="{{ old('end_date', isset($class) ? \Carbon\Carbon::parse($class->end_date)->format('Y-m-d') : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Location -->
                            <div class="md:col-span-2">
                                <label for="location" class="block text-sm font-medium text-gray-700">Lieu</label>
                                <input type="text" name="location" id="location" value="{{ old('location', $class->location ?? '') }}" placeholder="Ex: Salle A, En ligne..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                {{ isset($class) ? 'Mettre à jour' : 'Créer la classe' }}
                            </button>
                            <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
