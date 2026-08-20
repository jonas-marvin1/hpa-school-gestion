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
                {{ __('Détails de la Fiche de Paie') }} - {{ str_pad($payment->month, 2, '0', STR_PAD_LEFT) }}/{{ $payment->year }}
            </h2>
            <div class="flex gap-2">
                @if($peutRegler && $payment->status === 'pending')
                    <form action="{{ route('manager.payments.update', $payment) }}" method="POST" class="inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 font-medium text-sm" onclick="return confirm('Confirmer que cette fiche est payée ?')">
                            Payer la Fiche
                        </button>
                    </form>
                    <form action="{{ route('manager.payments.destroy', $payment) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 font-medium text-sm" onclick="return confirm('Voulez-vous vraiment supprimer cette fiche de paie ? Les sessions redeviendront non facturées.')">
                            Supprimer
                        </button>
                    </form>
                @endif
                <a href="{{ route('manager.payments.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300 font-medium text-sm">
                    Retour
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <!-- Informations Générales -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-2">Informations Générales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Formateur</p>
                            <p class="font-medium text-lg">{{ $payment->coach->name ?? 'Inconnu' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Période</p>
                            <p class="font-medium text-lg">{{ str_pad($payment->month, 2, '0', STR_PAD_LEFT) }} / {{ $payment->year }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date de génération</p>
                            <p class="font-medium text-lg">{{ $payment->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Statut</p>
                            <p class="font-medium text-lg">
                                @if($payment->status === 'paid' || $payment->status === 'validated')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payé</span>
                                    <span class="text-sm text-gray-500 ml-2">(par {{ $payment->validator->name ?? '?' }})</span>
                                @elseif($payment->status === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Annulé</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">En attente</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Résumé Financier -->
            <div class="bg-indigo-50 border border-indigo-100 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-indigo-900 mb-4 border-b border-indigo-200 pb-2">Résumé</h3>
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-indigo-700">Total des sessions validées</p>
                            <p class="text-3xl font-bold text-indigo-900">{{ $payment->total_sessions }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-indigo-700">Montant Total Calculé</p>
                            <p class="text-3xl font-bold text-indigo-900">{{ number_format($payment->total_amount, 0, ',', ' ') }} FCFA</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détail des Sessions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4 border-b pb-2">Détail des Sessions</h3>
                    
                    @if($sessions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead>
                                    <tr>
                                        <th class="border-b py-2 px-4">Date</th>
                                        <th class="border-b py-2 px-4">Horaires</th>
                                        <th class="border-b py-2 px-4">Classe</th>
                                        <th class="border-b py-2 px-4">Formateur</th>
                                        <th class="border-b py-2 px-4">Type d'intervention</th>
                                        <th class="border-b py-2 px-4 text-right">Montant (FCFA)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sessions as $session)
                                        <tr class="hover:bg-gray-50">
                                            <td class="border-b py-3 px-4">{{ \Carbon\Carbon::parse($session->start_time)->format('d/m/Y') }}</td>
                                            <td class="border-b py-3 px-4">
                                                {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                                            </td>
                                            <td class="border-b py-3 px-4">{{ $session->courseClass->name ?? 'N/A' }}</td>
                                            <td class="border-b py-3 px-4">{{ $session->coach->name ?? 'N/A' }}</td>
                                            <td class="border-b py-3 px-4">{{ $session->intervention_type === 'online' ? 'En ligne' : 'Présentiel' }}</td>
                                            <td class="border-b py-3 px-4 text-right font-medium">{{ number_format($session->amount, 0, ',', ' ') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 italic">Aucune session trouvée pour cette période (peut-être ont-elles été supprimées depuis la génération).</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
