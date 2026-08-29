@props([
    'classes',
    'selectedClass' => null,
    'selectedStudent' => null,
])

@php
    // classeId => {studentId: nom}, pour filtrer la liste d'apprenants en
    // Alpine.js selon la classe choisie, sans requete supplementaire.
    $apprenantsParClasse = $classes->mapWithKeys(fn ($classe) => [
        (string) $classe->id => $classe->users->pluck('name', 'id'),
    ]);
@endphp

<div
    x-data="{
        classeId: @js((string) ($selectedClass ?? '')),
        apprenantId: @js((string) ($selectedStudent ?? '')),
        apprenantsParClasse: @js($apprenantsParClasse),
        get apprenantsDisponibles() {
            return this.apprenantsParClasse[this.classeId] ?? {};
        }
    }"
    x-init="$watch('classeId', () => { if (!(apprenantId in apprenantsDisponibles)) apprenantId = ''; })"
    class="contents"
>
    <div>
        <label for="course_class_id" class="block text-sm font-medium text-gray-700">Classe cible *</label>
        <select name="course_class_id" id="course_class_id" x-model="classeId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionnez une classe</option>
            @foreach($classes as $classe)
                <option value="{{ $classe->id }}">{{ $classe->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="student_id" class="block text-sm font-medium text-gray-700">Apprenant</label>
        <select name="student_id" id="student_id" x-model="apprenantId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Toute la classe</option>
            <template x-for="[id, nom] in Object.entries(apprenantsDisponibles)" :key="id">
                <option :value="id" x-text="nom"></option>
            </template>
        </select>
        <p class="text-xs text-gray-500 mt-1">Laissez « Toute la classe » pour attribuer le devoir à tous les apprenants, ou choisissez un apprenant pour une attribution individuelle.</p>
    </div>
</div>
