<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Plan de paiement') }} &mdash; {{ $student->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-md text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $erreur)
                            <li>{{ $erreur }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Situation actuelle, uniquement si un plan existe deja --}}
            @if($plan)
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wide mb-5">Situation</h3>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            <div>
                                <p class="text-xs text-gray-500">Coût total</p>
                                <p class="text-xl font-bold">{{ number_format($plan->total_amount, 0, ',', ' ') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Déjà réglé</p>
                                <p class="text-xl font-bold text-green-600">{{ number_format($plan->montantRegle(), 0, ',', ' ') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Reste à payer</p>
                                <p class="text-xl font-bold {{ $plan->soldeRestant() > 0 ? 'text-amber-600' : 'text-green-600' }}">
                                    {{ number_format($plan->soldeRestant(), 0, ',', ' ') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Prochaine échéance</p>
                                @if($prochaine = $plan->prochaineEcheance())
                                    <p class="text-xl font-bold {{ $prochaine->due_date->lt(now()->startOfDay()) ? 'text-red-600' : 'text-gray-800' }}">
                                        {{ $prochaine->due_date->format('d/m/Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ number_format($prochaine->amount, 0, ',', ' ') }} {{ $plan->currency }}</p>
                                @else
                                    <p class="text-xl font-bold text-green-600">Soldé</p>
                                @endif
                            </div>
                        </div>

                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-green-600 h-3 rounded-full transition-all duration-700" style="width: {{ $plan->progression() }}%"></div>
                        </div>
                        <p class="text-right text-xs text-gray-500 mt-2">{{ $plan->progression() }}% de la formation réglée</p>

                        @if($plan->echeances->count())
                            <div class="mt-6 overflow-x-auto">
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr>
                                            <th class="border-b py-2 px-3">Échéance</th>
                                            <th class="border-b py-2 px-3 text-right">Montant</th>
                                            <th class="border-b py-2 px-3 text-center">État</th>
                                            <th class="border-b py-2 px-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($plan->echeances as $e)
                                            <tr x-data="{ openAnnuler: false, openReactiver: false }">
                                                <td class="border-b py-2 px-3">{{ $e->due_date->format('d/m/Y') }}</td>
                                                <td class="border-b py-2 px-3 text-right font-medium">{{ number_format($e->amount, 0, ',', ' ') }}</td>
                                                <td class="border-b py-2 px-3 text-center">
                                                    @if($e->status === 'paid')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Réglée</span>
                                                    @elseif($e->status === 'cancelled')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">Annulée</span>
                                                        {{-- Une echeance annulee reste visible, avec son motif : elle ne
                                                             disparait pas, elle est seulement marquee comme telle. --}}
                                                        @if($e->notes)
                                                            <p class="text-xs text-gray-500 mt-1 whitespace-pre-line">{{ $e->notes }}</p>
                                                        @endif
                                                    @elseif($e->due_date->lt(now()->startOfDay()))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">En retard</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">À venir</span>
                                                    @endif
                                                </td>
                                                <td class="border-b py-2 px-3 text-center">
                                                    @if($e->status === 'pending')
                                                        <div class="flex flex-col items-center gap-1">
                                                            <form method="POST" action="{{ route('admin.echeances.payee', $e) }}" class="inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="text-indigo-600 hover:underline">Marquer réglée</button>
                                                            </form>
                                                            <button type="button" @click="openAnnuler = true" class="text-red-600 hover:underline">Annuler</button>

                                                            {{-- Fenetre de l'application, sur le modele de
                                                                 x-prolonger-delai-modal : ni prompt() ni confirm(),
                                                                 le motif reste facultatif (correctif du 14/09/2026). --}}
                                                            <div x-show="openAnnuler" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
                                                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                                    <div x-show="openAnnuler" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openAnnuler = false" aria-hidden="true"></div>

                                                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                                                    <div x-show="openAnnuler" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full whitespace-normal">
                                                                        <form method="POST" action="{{ route('admin.echeances.annuler', $e) }}">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Annuler l'échéance</h3>
                                                                                <p class="text-sm text-gray-500 mb-4">
                                                                                    {{ $e->due_date->format('d/m/Y') }} — {{ number_format($e->amount, 0, ',', ' ') }} {{ $plan->currency }}
                                                                                </p>

                                                                                <div class="mb-2">
                                                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Motif (facultatif)</label>
                                                                                    <textarea name="motif" rows="3" maxlength="255" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                                                                </div>
                                                                            </div>
                                                                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                                                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:w-auto sm:text-sm">
                                                                                    Confirmer l'annulation
                                                                                </button>
                                                                                <button type="button" @click="openAnnuler = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                                                                                    Fermer
                                                                                </button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @elseif($e->status === 'cancelled')
                                                        <button type="button" @click="openReactiver = true" class="text-indigo-600 hover:underline">Réactiver</button>

                                                        <div x-show="openReactiver" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
                                                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                                <div x-show="openReactiver" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openReactiver = false" aria-hidden="true"></div>

                                                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                                                <div x-show="openReactiver" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full whitespace-normal">
                                                                    <form method="POST" action="{{ route('admin.echeances.reactiver', $e) }}">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Réactiver l'échéance</h3>
                                                                            <p class="text-sm text-gray-500 mb-4">
                                                                                {{ $e->due_date->format('d/m/Y') }} — {{ number_format($e->amount, 0, ',', ' ') }} {{ $plan->currency }}
                                                                            </p>
                                                                        </div>
                                                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                                                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:w-auto sm:text-sm">
                                                                                Confirmer la réactivation
                                                                            </button>
                                                                            <button type="button" @click="openReactiver = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                                                                                Fermer
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-400">{{ $e->paid_date?->format('d/m/Y') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Saisie du plan. Les echeances sont gerees par Alpine : leur nombre
                 varie d'un apprenant a l'autre, elles ne peuvent pas etre figees. --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6"
                     x-data="planPaiement({
                        total: {{ old('total_amount', $plan->total_amount ?? 0) }},
                        avance: {{ old('advance_amount', $plan->advance_amount ?? 0) }},
                        dejaRegle: {{ $plan ? (float) $plan->echeances->where('status', 'paid')->sum('amount') : 0 }},
                        lignes: {{ json_encode(old('echeances', $plan
                            ? $plan->echeances->where('status', '!=', 'paid')->map(fn($e) => ['amount' => (float) $e->amount, 'due_date' => $e->due_date->format('Y-m-d')])->values()
                            : [['amount' => '', 'due_date' => '']])) }}
                     })">

                    <h3 class="text-lg font-semibold mb-1">{{ $plan ? 'Modifier le plan' : 'Créer le plan de paiement' }}</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Renseignez le coût total, l'avance versée à l'inscription, puis répartissez
                        le solde en échéances. Les rappels partiront automatiquement avant chacune.
                        @if($plan && $plan->echeances->where('status', 'paid')->count())
                            <br><span class="text-amber-700">Les échéances déjà réglées sont conservées et ne figurent pas ci-dessous.</span>
                        @endif
                    </p>

                    <form method="POST" action="{{ route('admin.students.plan.store', $student) }}">
                        @csrf

                        {{-- Le programme n'est plus saisi : il decoule de la classe de
                             l'apprenant. On l'affiche pour information seulement. --}}
                        <div class="mb-5 text-sm">
                            <span class="text-gray-500">Programme suivi :</span>
                            @if($programme)
                                <span class="font-medium text-gray-900">{{ $programme->name }}</span>
                                <span class="text-gray-400">— déduit de la classe de l'apprenant</span>
                            @else
                                <span class="text-amber-700">aucun — cet apprenant n'est affecté à aucune classe</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="total_amount" class="block text-sm font-medium text-gray-700">Coût total de la formation *</label>
                                <input type="number" step="1" min="0" name="total_amount" id="total_amount" required
                                       x-model.number="total"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="advance_amount" class="block text-sm font-medium text-gray-700">Avance versée *</label>
                                <input type="number" step="1" min="0" name="advance_amount" id="advance_amount" required
                                       x-model.number="avance"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-gray-700">Échéances</h4>
                            <button type="button" @click="ajouter()" class="text-sm text-indigo-600 hover:underline">+ Ajouter une échéance</button>
                        </div>

                        <div class="space-y-3 mb-4">
                            <template x-for="(ligne, i) in lignes" :key="i">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="flex-1 min-w-[10rem]">
                                        <label class="block text-xs text-gray-500" :for="'ech-date-' + i">Date d'échéance</label>
                                        <input type="date" :name="'echeances[' + i + '][due_date]'" :id="'ech-date-' + i" x-model="ligne.due_date" required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="flex-1 min-w-[10rem]">
                                        <label class="block text-xs text-gray-500" :for="'ech-montant-' + i">Montant</label>
                                        <input type="number" step="1" min="1" :name="'echeances[' + i + '][amount]'" :id="'ech-montant-' + i" x-model.number="ligne.amount" required
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <button type="button" @click="retirer(i)" x-show="lignes.length > 1"
                                            class="px-3 py-2 text-sm text-red-600 hover:underline">Retirer</button>
                                </div>
                            </template>
                        </div>

                        {{-- Controle de coherence en direct : l'administrateur voit
                             immediatement si son echeancier ne boucle pas, sans
                             avoir a soumettre pour le decouvrir. --}}
                        <div class="rounded-md border p-4 mb-6 text-sm"
                             :class="equilibre ? 'bg-green-50 border-green-300 text-green-800' : 'bg-amber-50 border-amber-300 text-amber-800'">
                            <div class="flex flex-wrap justify-between gap-2">
                                <span>Reste à répartir : <strong x-text="format(resteARepartir)"></strong></span>
                                <span>Total des échéances saisies : <strong x-text="format(sommeEcheances)"></strong></span>
                            </div>
                            <p class="mt-2" x-show="!equilibre" x-cloak>
                                Écart de <strong x-text="format(Math.abs(resteARepartir - sommeEcheances))"></strong> :
                                le plan doit se boucler pour être enregistré.
                            </p>
                            <p class="mt-2" x-show="equilibre" x-cloak>Le plan est équilibré.</p>
                        </div>

                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Note interne</label>
                            <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('notes', $plan->notes ?? '') }}</textarea>
                        </div>

                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                            {{ $plan ? 'Enregistrer les modifications' : 'Créer le plan' }}
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function planPaiement(donnees) {
            return {
                total: donnees.total,
                avance: donnees.avance,
                dejaRegle: donnees.dejaRegle || 0,
                lignes: donnees.lignes.length ? donnees.lignes : [{ amount: '', due_date: '' }],

                get sommeEcheances() {
                    return this.lignes.reduce((s, l) => s + (parseFloat(l.amount) || 0), 0);
                },
                // Les echeances deja reglees sont conservees et ne figurent pas
                // dans le formulaire : elles sont donc deduites du reste a repartir.
                get resteARepartir() {
                    return this.total - this.avance - this.dejaRegle;
                },
                get equilibre() {
                    return Math.abs(this.resteARepartir - this.sommeEcheances) < 1;
                },
                ajouter() {
                    this.lignes.push({ amount: '', due_date: '' });
                },
                retirer(i) {
                    this.lignes.splice(i, 1);
                },
                format(n) {
                    return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0));
                },
            };
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
