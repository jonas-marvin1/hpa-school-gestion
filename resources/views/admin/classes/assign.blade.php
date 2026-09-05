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
                    <form action="{{ route('admin.classes.assign.update', ['class' => $courseClass->id]) }}" method="POST">
                        @csrf

                        {{-- Chaque liste a sa propre recherche : chercher un formateur ne
                             doit pas masquer les apprenants, et inversement. Le filtrage
                             se fait dans le navigateur, sans rechargement — les cases
                             deja cochees restent donc cochees, meme masquees. --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                            <!-- Formateurs -->
                            <div x-data="listeFiltrable('coach')">
                                <div class="flex items-baseline justify-between border-b pb-2 mb-3">
                                    <h3 class="font-bold text-lg">Formateurs</h3>
                                    <span class="text-xs text-gray-500" x-text="resume"></span>
                                </div>

                                <div class="relative mb-3">
                                    <input type="search" x-model="recherche" placeholder="Rechercher un formateur (nom ou e-mail)…"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-9">
                                    <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <div class="max-h-96 overflow-y-auto pr-1 space-y-1">
                                    @forelse($coaches as $coach)
                                        <div class="flex items-center py-1" data-personne
                                             data-nom="{{ Str::lower($coach->name . ' ' . $coach->email) }}">
                                            <input type="radio" name="coach_id" id="user_{{ $coach->id }}" value="{{ $coach->id }}"
                                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                {{ in_array($coach->id, $assignedUserIds) ? 'checked' : '' }} required>
                                            <label for="user_{{ $coach->id }}" class="ml-2 text-sm text-gray-700">
                                                {{ $coach->name }} <span class="text-gray-400">({{ $coach->email }})</span>
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">Aucun formateur enregistré.</p>
                                    @endforelse
                                </div>

                                <p class="text-sm text-gray-500 italic mt-2" x-show="aucunResultat" x-cloak>
                                    Aucun formateur ne correspond à cette recherche.
                                </p>
                            </div>

                            <!-- Apprenants -->
                            <div x-data="listeFiltrable('student')">
                                <div class="flex items-baseline justify-between border-b pb-2 mb-3">
                                    <h3 class="font-bold text-lg">Apprenants</h3>
                                    <span class="text-xs text-gray-500" x-text="resume"></span>
                                </div>

                                <div class="relative mb-3">
                                    <input type="search" x-model="recherche" placeholder="Rechercher un apprenant (nom ou e-mail)…"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pl-9">
                                    <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <div class="flex items-center gap-3 mb-2 text-xs">
                                    <button type="button" @click="toutCocher(true)" class="text-indigo-600 hover:underline">Tout cocher (visibles)</button>
                                    <button type="button" @click="toutCocher(false)" class="text-gray-600 hover:underline">Tout décocher (visibles)</button>
                                </div>

                                <div class="max-h-96 overflow-y-auto pr-1 space-y-1">
                                    @forelse($students as $student)
                                        <div class="flex items-center py-1" data-personne
                                             data-nom="{{ Str::lower($student->name . ' ' . $student->email) }}">
                                            <input type="checkbox" name="student_ids[]" id="user_{{ $student->id }}" value="{{ $student->id }}"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                {{ in_array($student->id, $assignedUserIds) ? 'checked' : '' }}>
                                            <label for="user_{{ $student->id }}" class="ml-2 text-sm text-gray-700">
                                                {{ $student->name }} <span class="text-gray-400">({{ $student->email }})</span>
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">Aucun apprenant enregistré.</p>
                                    @endforelse
                                </div>

                                <p class="text-sm text-gray-500 italic mt-2" x-show="aucunResultat" x-cloak>
                                    Aucun apprenant ne correspond à cette recherche.
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Enregistrer les affectations
                            </button>
                            <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function listeFiltrable(role) {
            return {
                recherche: '',
                visibles: 0,
                total: 0,

                init() {
                    this.lignes = Array.from(this.$el.querySelectorAll('[data-personne]'));
                    this.total = this.lignes.length;
                    this.visibles = this.total;
                    this.$watch('recherche', () => this.filtrer());
                },

                filtrer() {
                    // Accents ignores : taper "prenom" doit trouver "prénom".
                    const q = this.normaliser(this.recherche);
                    let n = 0;

                    this.lignes.forEach(ligne => {
                        const correspond = q === '' || this.normaliser(ligne.dataset.nom).includes(q);
                        ligne.style.display = correspond ? '' : 'none';
                        if (correspond) n++;
                    });

                    this.visibles = n;
                },

                normaliser(texte) {
                    return (texte || "").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
                },

                // N'agit que sur les lignes affichees : permet de cocher d'un coup
                // le resultat d'une recherche sans toucher au reste.
                toutCocher(etat) {
                    this.lignes.forEach(ligne => {
                        if (ligne.style.display === 'none') return;
                        const c = ligne.querySelector('input[type="checkbox"]');
                        if (c) c.checked = etat;
                    });
                },

                get aucunResultat() { return this.visibles === 0 && this.total > 0; },

                get resume() {
                    return this.recherche === ''
                        ? this.total + (this.total > 1 ? ' au total' : '')
                        : this.visibles + ' sur ' + this.total;
                },
            };
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
