<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Paiements attendus') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('admin.student-payments.index') }}" class="flex flex-wrap gap-4 items-end">
                        <div class="w-40">
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois</label>
                            <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $mois === $m ? 'selected' : '' }}>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="w-32">
                            <label for="year" class="block text-sm font-medium text-gray-700">Année</label>
                            <select name="year" id="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($years as $a)
                                    <option value="{{ $a }}" {{ $annee === $a ? 'selected' : '' }}>{{ $a }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1 min-w-[200px]">
                            <label for="search" class="block text-sm font-medium text-gray-700">Apprenant</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nom de l'apprenant..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les statuts</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>En retard</option>
                            </select>
                        </div>

                        <div>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium">Afficher</button>
                            @if(request()->hasAny(['search', 'status']))
                                <a href="{{ route('admin.student-payments.index', ['month' => $mois, 'year' => $annee]) }}" class="ml-2 text-gray-600 hover:underline text-sm">Réinitialiser</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Vision previsionnelle du mois : toujours calculee sur l'ensemble
                 des echeances du mois (recherche apprenant comprise), pas
                 seulement la categorie affichee dans le tableau via le filtre
                 statut. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total attendu</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totalAttendu, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 border-l-4 border-green-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Déjà réglé</p>
                    <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($totalRegle, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 border-l-4 border-yellow-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">À venir</p>
                    <p class="mt-1 text-2xl font-bold text-yellow-700">{{ number_format($totalAVenir, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 border-l-4 border-red-500">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">En retard</p>
                    <p class="mt-1 text-2xl font-bold text-red-700">{{ number_format($totalEnRetard, 0, ',', ' ') }} FCFA</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-4">
                        <span class="font-semibold text-gray-900">{{ $paiements->total() }}</span>
                        échéance{{ $paiements->total() > 1 ? 's' : '' }} attendue{{ $paiements->total() > 1 ? 's' : '' }}
                        @if($paiements->hasPages())
                            <span class="text-gray-400">— page {{ $paiements->currentPage() }} sur {{ $paiements->lastPage() }}</span>
                        @endif
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Apprenant</th>
                                <th class="border-b py-2 px-4">Programme</th>
                                <th class="border-b py-2 px-4 text-right">Montant</th>
                                <th class="border-b py-2 px-4">Date prévue</th>
                                <th class="border-b py-2 px-4 text-center">Statut</th>
                                <th class="border-b py-2 px-4">Date de paiement</th>
                                <th class="border-b py-2 px-4">Plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paiements as $paiement)
                                <tr>
                                    <td class="border-b py-3 px-4 font-medium">{{ $paiement->student->name ?? 'N/A' }}</td>
                                    <td class="border-b py-3 px-4 text-sm text-gray-600">{{ $paiement->program->name ?? 'N/A' }}</td>
                                    <td class="border-b py-3 px-4 text-right">{{ number_format($paiement->amount, 0, ',', ' ') }} FCFA</td>
                                    <td class="border-b py-3 px-4">{{ $paiement->due_date->format('d/m/Y') }}</td>
                                    <td class="border-b py-3 px-4 text-center">
                                        @if($paiement->status === 'paid')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payé</span>
                                        @elseif($paiement->due_date->isPast())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">En retard</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En attente</span>
                                        @endif
                                    </td>
                                    <td class="border-b py-3 px-4 text-gray-500">{{ $paiement->paid_date ? $paiement->paid_date->format('d/m/Y') : '-' }}</td>
                                    <td class="border-b py-3 px-4">
                                        @if($paiement->student)
                                            <a href="{{ route('admin.students.plan.edit', $paiement->student) }}" class="text-indigo-600 hover:underline">Voir le plan</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-500">Aucune échéance attendue sur cette période.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-4">
                        {{ $paiements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
