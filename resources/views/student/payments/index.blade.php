<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Historique des Paiements') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Prochain paiement -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-medium text-gray-500">Prochain paiement</h3>
                            @if($nextPayment)
                                <p class="text-2xl font-bold text-gray-800 mt-2">{{ $nextPayment->due_date->format('d/m/Y') }}</p>
                                <p class="text-sm text-gray-500">{{ number_format($nextPayment->amount, 0, ',', ' ') }} FCFA</p>
                            @else
                                <p class="text-xl font-bold text-green-600 mt-2">Aucun paiement en attente</p>
                            @endif
                        </div>
                        <div class="bg-blue-50 p-3 rounded-full">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Barre de progression -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-500 mb-4">Progression des paiements</h3>
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>{{ $paidPayments }} payés</span>
                            <span>{{ $totalPayments }} au total</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                            <div class="bg-green-600 h-4 rounded-full transition-all duration-500" style="width: {{ $progressPercentage }}%"></div>
                        </div>
                        <p class="text-right text-xs text-gray-500 font-medium">{{ $progressPercentage }}% achevé</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('student.payments.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois d'échéance</label>
                            <select name="month" id="month" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tous les mois</option>
                                @for($m=1; $m<=12; $m++)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Tous les statuts</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payé</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>En retard</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historique -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr>
                                    <th class="border-b py-2 px-4">Échéance</th>
                                    <th class="border-b py-2 px-4">Programme</th>
                                    <th class="border-b py-2 px-4 text-right">Montant</th>
                                    <th class="border-b py-2 px-4 text-center">Statut</th>
                                    <th class="border-b py-2 px-4 text-center">Date de paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border-b py-4 px-4 font-medium">{{ $payment->due_date->format('d/m/Y') }}</td>
                                        <td class="border-b py-4 px-4">{{ $payment->program->name ?? 'N/A' }}</td>
                                        <td class="border-b py-4 px-4 text-right font-bold">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                        <td class="border-b py-4 px-4 text-center">
                                            @if($payment->status === 'paid')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Payé
                                                </span>
                                            @elseif($payment->status === 'overdue' || ($payment->status === 'pending' && $payment->due_date->isPast()))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    En retard
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    En attente
                                                </span>
                                            @endif
                                        </td>
                                        <td class="border-b py-4 px-4 text-center text-gray-500">
                                            {{ $payment->paid_date ? $payment->paid_date->format('d/m/Y') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-gray-500">Aucun historique de paiement.</td>
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
</x-app-layout>
