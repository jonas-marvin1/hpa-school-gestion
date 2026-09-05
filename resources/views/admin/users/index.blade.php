<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestion des Utilisateurs') }}
            </h2>
            <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                Nouvel Utilisateur
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-wrap gap-4 items-end"
                          x-data="{ periode: '{{ $periode }}' }">
                        {{-- Le sens du tri est porte par les entetes de colonne :
                             on le conserve ici pour qu'un filtrage ne le remette
                             pas silencieusement a sa valeur par defaut. --}}
                        <input type="hidden" name="tri" value="{{ $tri }}">

                        <div class="flex-1 min-w-[200px]">
                            <label for="search" class="block text-sm font-medium text-gray-700">Recherche</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nom, Email...">
                        </div>

                        <div class="w-48">
                            <label for="role" class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les rôles</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->name }}" {{ request('role') == $r->name ? 'selected' : '' }}>{{ ucfirst($r->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les statuts</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactif</option>
                            </select>
                        </div>

                        <div class="w-48">
                            <label for="periode" class="block text-sm font-medium text-gray-700">Date de création</label>
                            <select name="periode" id="periode" x-model="periode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Toutes les dates</option>
                                @foreach($periodes as $cle => $libelle)
                                    <option value="{{ $cle }}" {{ $periode === $cle ? 'selected' : '' }}>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Les bornes ne concernent que la periode personnalisee :
                             les afficher en permanence laisserait croire qu'elles
                             s'appliquent aussi aux raccourcis. --}}
                        <div class="flex gap-2" x-show="periode === 'personnalise'" x-cloak>
                            <div class="w-40">
                                <label for="du" class="block text-sm font-medium text-gray-700">Du</label>
                                <input type="date" name="du" id="du" value="{{ request('du') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div class="w-40">
                                <label for="au" class="block text-sm font-medium text-gray-700">Au</label>
                                <input type="date" name="au" id="au" value="{{ request('au') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="w-40">
                            <label for="granularite" class="block text-sm font-medium text-gray-700">Répartition</label>
                            <select name="granularite" id="granularite" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($granularites as $cle => $libelle)
                                    <option value="{{ $cle }}" {{ $granularite === $cle ? 'selected' : '' }}>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium">Filtrer</button>
                            @if(request()->hasAny(['search', 'role', 'status', 'periode', 'du', 'au']))
                                <a href="{{ route('admin.users.index') }}" class="ml-2 text-gray-600 hover:underline text-sm">Réinitialiser</a>
                            @endif
                            {{-- Exporte exactement les lignes filtrees affichees a l'ecran. --}}
                            <a href="{{ route('admin.users.export', request()->query()) }}" class="ml-2 text-indigo-600 hover:underline text-sm">Exporter (CSV)</a>
                        </div>
                    </form>

                    {{-- Creations de comptes sur le perimetre filtre. Le tableau
                         suit la granularite choisie : jour, mois ou annee. --}}
                    <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50/60 p-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-3 mb-3">
                            <h3 class="text-sm font-semibold text-indigo-900 uppercase tracking-wider">
                                Créations de comptes — {{ strtolower($granularites[$granularite]) }}
                            </h3>
                            <p class="text-sm text-indigo-800">
                                <span class="text-2xl font-bold">{{ $users->total() }}</span>
                                utilisateur{{ $users->total() > 1 ? 's' : '' }} au total
                                @if(request()->hasAny(['search', 'role', 'status', 'periode', 'du', 'au']))
                                    <span class="text-indigo-600">pour ce filtre</span>
                                @endif
                            </p>
                        </div>

                        @if($sansDate > 0)
                            <p class="mb-3 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                                {{ $sansDate }} compte{{ $sansDate > 1 ? 's' : '' }} sans date de création (import antérieur)
                                {{ $sansDate > 1 ? 'ne figurent' : 'ne figure' }} pas dans cette répartition.
                            </p>
                        @endif

                        @if(count($repartition) === 0)
                            <p class="text-sm text-indigo-700">Aucune création de compte sur ce périmètre.</p>
                        @else
                            <div class="overflow-y-auto max-h-64">
                                <table class="w-full text-left text-sm">
                                    <thead class="sticky top-0 bg-indigo-50">
                                        <tr class="text-indigo-900">
                                            <th class="py-2 px-3 font-semibold">Période</th>
                                            <th class="py-2 px-3 font-semibold text-right">Créations</th>
                                            <th class="py-2 px-3 font-semibold text-right">Évolution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($repartition as $ligne)
                                            <tr class="border-t border-indigo-100">
                                                <td class="py-2 px-3 text-gray-800">{{ $ligne['libelle'] }}</td>
                                                <td class="py-2 px-3 text-right font-semibold text-gray-900">{{ $ligne['total'] }}</td>
                                                <td class="py-2 px-3 text-right">
                                                    @if($ligne['variation'] === null)
                                                        <span class="text-gray-400">—</span>
                                                    @elseif($ligne['variation'] > 0)
                                                        <span class="text-green-700">+{{ $ligne['variation'] }}</span>
                                                    @elseif($ligne['variation'] < 0)
                                                        <span class="text-red-700">{{ $ligne['variation'] }}</span>
                                                    @else
                                                        <span class="text-gray-500">=</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-2 text-xs text-indigo-700">
                                L'évolution compare chaque période à la précédente de la liste.
                            </p>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Nom</th>
                                <th class="border-b py-2 px-4">Email</th>
                                <th class="border-b py-2 px-4">Rôle</th>
                                <th class="border-b py-2 px-4">Statut</th>
                                <th class="border-b py-2 px-4">
                                    {{-- Entete cliquable : inverse le tri sans perdre
                                         les filtres, et revient a la premiere page. --}}
                                    <a href="{{ request()->fullUrlWithQuery(['tri' => $tri === 'recent' ? 'ancien' : 'recent', 'page' => null]) }}"
                                       class="inline-flex items-center gap-1 hover:text-indigo-600">
                                        Date de création
                                        <span class="text-xs text-gray-400">{{ $tri === 'recent' ? '▼' : '▲' }}</span>
                                    </a>
                                </th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td class="border-b py-3 px-4">{{ $user->name }}</td>
                                    <td class="border-b py-3 px-4">{{ $user->email }}</td>
                                    <td class="border-b py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 uppercase">
                                            {{ $user->roles->pluck('name')->first() ?? 'Sans rôle' }}
                                        </span>
                                    </td>
                                    <td class="border-b py-3 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} uppercase">
                                            {{ $user->status === 'active' ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="border-b py-3 px-4 text-gray-700">
                                        @if($user->created_at)
                                            {{ $user->created_at->format('d/m/Y') }}
                                            <span class="text-xs text-gray-400">{{ $user->created_at->format('H:i') }}</span>
                                        @else
                                            <span class="text-gray-400" title="Compte importé sans date de création">—</span>
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4 flex items-center gap-3">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:underline">Modifier</a>
                                        @if($user->hasRole('student'))
                                            <a href="{{ route('students.level.edit', $user) }}" class="text-indigo-600 hover:underline">Niveau</a>
                                            <a href="{{ route('admin.students.plan.edit', $user) }}" class="text-indigo-600 hover:underline">Paiement</a>
                                        @endif
                                        @if(!$user->hasRole('admin'))
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-500">Aucun utilisateur ne correspond à ces filtres.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Evite que les champs de periode personnalisee n'apparaissent une
         fraction de seconde avant l'initialisation d'Alpine. --}}
    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
