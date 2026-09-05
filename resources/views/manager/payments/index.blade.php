@php
    // Regler ou supprimer une fiche est reserve a l'administrateur : les
    // routes correspondantes sont protegees (cf. routes/web.php). On masque
    // ici les commandes correspondantes pour ne pas proposer au manager des
    // actions qui se solderaient par un 403.
    $peutRegler = auth()->user()->hasRole('admin');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestion des Paies (Par Session)') }}
            </h2>
            <form action="{{ route('manager.payments.store') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 font-medium text-sm" onclick="return confirm('Générer les fiches de paie pour toutes les sessions validées et non facturées ?')">
                    Générer les paiements en attente
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <!-- Total à payer card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 flex justify-between items-center bg-indigo-50 border-l-4 border-indigo-500 rounded">
                    <div>
                        <h3 class="text-sm font-semibold text-indigo-700 uppercase tracking-wider">
                            Total à payer (Fiches en attente)
                        </h3>
                        <p class="text-3xl font-bold text-indigo-900 mt-2">{{ number_format($totalToPay, 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full text-indigo-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Advanced Search Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('manager.payments.index') }}" class="grid grid-cols-1 md:grid-cols-7 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Recherche (Nom)</label>
                            <input type="text" name="search_coach" value="{{ request('search_coach') }}" placeholder="Rechercher formateur..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Formateur (Sélection)</label>
                            <select name="coach_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Tous les formateurs</option>
                                @foreach($coaches as $coach)
                                    <option value="{{ $coach->id }}" {{ request('coach_id') == $coach->id ? 'selected' : '' }}>
                                        {{ $coach->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Classe</label>
                            <select name="class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Toutes les classes</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id }}" {{ request('class_id') == $classe->id ? 'selected' : '' }}>
                                        {{ $classe->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Tous les statuts</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mois</label>
                            <select name="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Tous les mois</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Année</label>
                            <select name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">Toutes les années</option>
                                {{-- Annees fournies par le controleur d'apres les fiches
                                     existantes : plus de plage figee a maintenir. --}}
                                @foreach($years as $annee)
                                    <option value="{{ $annee }}" {{ request('year') == $annee ? 'selected' : '' }}>{{ $annee }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Filtrer</button>
                            <a href="{{ route('manager.payments.index') }}" class="w-full bg-gray-200 text-gray-800 px-4 py-2 rounded-md text-center hover:bg-gray-300">Réinitialiser</a>
                            {{-- Exporte exactement les lignes filtrees affichees a l'ecran. --}}
                            <a href="{{ route('manager.payments.export', request()->query()) }}" class="w-full bg-gray-200 text-gray-800 px-4 py-2 rounded-md text-center hover:bg-gray-300">Exporter (CSV)</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="selectionFiches()">
                <div class="p-6 text-gray-900">

                    {{-- Compteur de resultats : indique le total du filtre, pas
                         seulement la page affichee. --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold text-gray-900">{{ $payments->total() }}</span>
                            fiche{{ $payments->total() > 1 ? 's' : '' }}
                            @if(request()->hasAny(['search_coach', 'coach_id', 'class_id', 'status', 'month', 'year']))
                                <span class="text-gray-500">pour ce filtre</span>
                            @endif
                            @if($payments->hasPages())
                                <span class="text-gray-400">— page {{ $payments->currentPage() }} sur {{ $payments->lastPage() }}</span>
                            @endif
                            @if($peutRegler)
                                {{-- Portee de la case "tout selectionner" : la page
                                     affichee, pas l'ensemble du filtre (delibere, cf.
                                     fiche du 03/09/2026). Cette ligne a de la place et
                                     n'impose aucune largeur au tableau, contrairement a
                                     l'en-tete de colonne (cf. commentaire sur cette
                                     cellule plus bas). --}}
                                <span class="text-gray-400">— La case en tête de tableau sélectionne les {{ $payments->count() }} fiche{{ $payments->count() > 1 ? 's' : '' }} de cette page.</span>
                            @endif
                        </p>
                    </div>

                    @if($peutRegler)
                    {{-- Barre d'action groupee : n'apparait qu'une fois une fiche
                         cochee, pour ne pas encombrer l'ecran le reste du temps.
                         Le contenu s'adapte a la composition de la selection. --}}
                    <div x-show="nbSelection > 0" x-cloak
                         class="flex flex-wrap items-center justify-between gap-3 mb-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3">
                        <p class="text-sm text-indigo-900">
                            <span class="font-semibold" x-text="nbSelection"></span>
                            <span x-text="nbSelection > 1 ? 'fiches sélectionnées' : 'fiche sélectionnée'"></span>
                            {{-- Le montant ne porte que sur les fiches en attente : c'est
                                 la seule chose que le bouton "Regler" ci-dessous facture
                                 reellement, les fiches payees/annulees n'y entrent pas. --}}
                            <span class="text-indigo-700">— <span x-text="nbEnAttente"></span> en attente pour <span class="font-semibold" x-text="montantFormate"></span> FCFA</span>
                        </p>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="toutDecocher()" class="text-sm text-indigo-700 hover:underline">Tout désélectionner</button>

                            {{-- Le reglement ne porte que sur les fiches en attente de la
                                 selection : les autres statuts n'ont rien a regler. --}}
                            {{-- Alpine compile @submit comme une expression, pas comme un
                                 corps de fonction : un "return" en tete la rend invalide,
                                 Alpine l'ignore silencieusement et le formulaire part sans
                                 confirmation. Il faut .prevent (la valeur de retour de
                                 l'expression n'est jamais lue) puis soumettre nous-memes
                                 via $el.submit() si l'utilisateur confirme. --}}
                            <form method="POST" action="{{ route('manager.payments.payMany') }}"
                                  @submit.prevent="if (confirm('Confirmer le règlement de ' + nbEnAttente + ' fiche(s) ?')) $el.submit()">
                                @csrf
                                {{-- Les identifiants sont injectes ici plutot que dans le
                                     tableau : imbriquer un formulaire dans un autre serait
                                     invalide, les lignes ayant deja leurs propres formulaires. --}}
                                <template x-for="id in idsEnAttente" :key="id">
                                    <input type="hidden" name="payment_ids[]" :value="id">
                                </template>
                                <button type="submit" :disabled="nbEnAttente === 0"
                                        :class="nbEnAttente === 0 ? 'bg-indigo-300 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'"
                                        class="inline-flex items-center px-4 py-2 text-white rounded-md text-sm font-medium">
                                    <span x-text="'Régler ' + nbEnAttente + (nbEnAttente > 1 ? ' fiches en attente' : ' fiche en attente')"></span>
                                </button>
                            </form>

                            {{-- Suppression groupee : les fiches payees de la selection
                                 sont ignorees cote serveur (cf. PaymentController::destroyMany).
                                 Le bouton est desactive si la selection n'en contient aucune
                                 autre, pour ne pas envoyer une requete sans effet. Meme
                                 piege Alpine que ci-dessus : .prevent + $el.submit(). --}}
                            <form method="POST" action="{{ route('manager.payments.destroyMany') }}"
                                  @submit.prevent="if (confirm(messageConfirmationSuppression())) $el.submit()">
                                @csrf
                                <template x-for="id in idsSupprimables" :key="id">
                                    <input type="hidden" name="payment_ids[]" :value="id">
                                </template>
                                <button type="submit" :disabled="nbSupprimables === 0"
                                        :class="nbSupprimables === 0 ? 'bg-red-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700'"
                                        class="inline-flex items-center px-4 py-2 text-white rounded-md text-sm font-medium">
                                    <span x-text="'Supprimer ' + nbSupprimables + (nbSupprimables > 1 ? ' fiches' : ' fiche')"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600">
                                @if($peutRegler)
                                <th class="border-b py-3 px-4 w-10">
                                    {{-- Le libelle "Tout selectionner (N fiches...)" a ete
                                         retire d'ici : place dans cette cellule, il portait
                                         la premiere colonne a 357px et faisait deborder le
                                         tableau de 302px (mesure a 1280/1366/1536/1920px de
                                         large, le conteneur etant plafonne par max-w-7xl), ce
                                         qui tronquait la colonne Montant et poussait Action
                                         hors ecran. Sans lui la colonne retombe a 52px. Le
                                         texte vit maintenant dans la ligne de comptage
                                         au-dessus du tableau, qui n'impose aucune largeur de
                                         colonne ; title et aria-label gardent l'information
                                         accessible depuis la case elle-meme. --}}
                                    <input type="checkbox" @change="toutCocher($event.target.checked)"
                                           :checked="toutesCochees" :indeterminate="nbSelection > 0 && !toutesCochees"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           title="Tout sélectionner (fiches de cette page)"
                                           aria-label="Tout sélectionner : les {{ $payments->count() }} fiche{{ $payments->count() > 1 ? 's' : '' }} de cette page">
                                </th>
                                @endif
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider">Date</th>
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider">Horaire</th>
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider">Classe</th>
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider">Formateur</th>
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider text-center">Statut</th>
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider text-right">Montant</th>
                                @if($peutRegler)
                                <th class="border-b py-3 px-4 font-semibold uppercase tracking-wider text-center">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                @php
                                    $session = $payment->sessions->first();
                                @endphp
                                <tr class="hover:bg-gray-50" :class="estSelectionnee({{ $payment->id }}) && 'bg-indigo-50'">
                                    @if($peutRegler)
                                    <td class="border-b py-3 px-4">
                                        {{-- Toutes les lignes sont selectionnables, quel que soit
                                             le statut : le statut de chaque fiche est porte par
                                             data-statut, pour que la barre d'action groupee sache
                                             ce qui est reglable et ce qui est supprimable. --}}
                                        <input type="checkbox" value="{{ $payment->id }}"
                                               data-fiche data-montant="{{ (float) $payment->total_amount }}" data-statut="{{ $payment->status }}"
                                               @change="basculer({{ $payment->id }}, {{ (float) $payment->total_amount }}, '{{ $payment->status }}', $event.target.checked)"
                                               :checked="estSelectionnee({{ $payment->id }})"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    @endif
                                    <td class="border-b py-3 px-4">
                                        {{ $session ? $session->start_time->format('d/m/Y') : str_pad($payment->month, 2, '0', STR_PAD_LEFT).'/'.$payment->year }}
                                    </td>
                                    <td class="border-b py-3 px-4">
                                        {{ $session ? $session->start_time->format('H:i') . ' - ' . $session->end_time->format('H:i') : '-' }}
                                    </td>
                                    <td class="border-b py-3 px-4">
                                        {{ $session && $session->courseClass ? $session->courseClass->name : 'N/A' }}
                                    </td>
                                    <td class="border-b py-3 px-4">
                                        {{ $payment->coach->name ?? 'Inconnu' }}
                                    </td>
                                    <td class="border-b py-3 px-4 text-center">
                                        @if($payment->status === 'paid' || $payment->status === 'validated')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Payé
                                            </span>
                                        @elseif($payment->status === 'cancelled')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Annulé
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                En attente
                                            </span>
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4 text-right font-bold">
                                        {{ number_format($payment->total_amount, 0, ',', ' ') }}
                                    </td>
                                    @if($peutRegler)
                                    <td class="border-b py-3 px-4 text-center">
                                        <div class="flex flex-wrap justify-center gap-2">
                                            @if($payment->status === 'pending')
                                                <form action="{{ route('manager.payments.update', $payment) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium px-2 py-1 bg-indigo-50 border border-indigo-200 rounded" title="Payer la fiche" onclick="return confirm('Confirmer le paiement de cette fiche ?')">
                                                        Payer
                                                    </button>
                                                </form>
                                            @endif

                                            <form action="{{ route('manager.payments.destroy', $payment) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-medium px-2 py-1 bg-red-50 border border-red-200 rounded" title="Supprimer la fiche" onclick="return confirm('Voulez-vous vraiment supprimer cette fiche de paie ? Les sessions redeviendront non facturées.')">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $peutRegler ? 8 : 6 }}" class="py-4 text-center text-gray-500">Aucune fiche de paie trouvée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectionFiches() {
            return {
                selection: [],
                montants: {},
                statuts: {},

                get nbSelection() { return this.selection.length; },

                // Uniquement les fiches en attente : c'est la seule chose que le
                // bouton "Regler" facture reellement, les fiches payees/annulees
                // de la selection ne doivent pas gonfler ce montant.
                get montantTotal() {
                    return this.idsEnAttente.reduce((s, id) => s + (this.montants[id] || 0), 0);
                },

                get montantFormate() {
                    return new Intl.NumberFormat('fr-FR').format(Math.round(this.montantTotal));
                },

                // $root et non $el : $el designe l'element sur lequel l'expression
                // courante est evaluee (la case cochee elle-meme pour un @change
                // dans l'en-tete), alors que $root est la racine du composant
                // x-data. Avec $el, "Tout selectionner" ne trouvait aucune ligne
                // et ne faisait rien, sans erreur visible (bug en production
                // depuis la livraison du 13/08/2026, cf. docs/fiches/2026-09-03.md).
                get cochables() {
                    return Array.from(this.$root.querySelectorAll('[data-fiche]'));
                },

                get toutesCochees() {
                    const n = this.cochables.length;
                    return n > 0 && this.selection.length === n;
                },

                // Decompte par statut, utilise par la barre d'action groupee pour
                // savoir combien de fiches selectionnees sont reglables (pending)
                // ou supprimables (pending + cancelled) : une fiche payee n'entre
                // dans aucun des deux, cf. PaymentController::destroyMany.
                get idsEnAttente() {
                    return this.selection.filter(id => this.statuts[id] === 'pending');
                },

                get idsAnnulees() {
                    return this.selection.filter(id => this.statuts[id] === 'cancelled');
                },

                get idsSupprimables() {
                    return this.selection.filter(id => this.statuts[id] === 'pending' || this.statuts[id] === 'cancelled');
                },

                get nbEnAttente() { return this.idsEnAttente.length; },
                get nbAnnulees() { return this.idsAnnulees.length; },
                get nbSupprimables() { return this.idsSupprimables.length; },

                messageConfirmationSuppression() {
                    const n = this.nbSupprimables;
                    const attente = this.nbEnAttente;
                    const annulees = this.nbAnnulees;
                    return 'Supprimer ' + n + (n > 1 ? ' fiches' : ' fiche')
                        + ' (' + attente + ' en attente, ' + annulees + (annulees > 1 ? ' annulées' : ' annulée') + ') ?'
                        + ' Les sessions correspondantes redeviendront facturables.';
                },

                estSelectionnee(id) { return this.selection.includes(id); },

                basculer(id, montant, statut, coche) {
                    if (coche) {
                        if (!this.selection.includes(id)) {
                            this.selection.push(id);
                            this.montants[id] = montant;
                            this.statuts[id] = statut;
                        }
                    } else {
                        this.selection = this.selection.filter(i => i !== id);
                    }
                },

                toutCocher(etat) {
                    this.cochables.forEach(c => {
                        const id = parseInt(c.value, 10);
                        this.basculer(id, parseFloat(c.dataset.montant) || 0, c.dataset.statut, etat);
                        c.checked = etat;
                    });
                },

                toutDecocher() { this.toutCocher(false); },
            };
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>