<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mes Sessions de Cours') }}
            </h2>
            <a href="{{ route('coach.sessions.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Déclarer une session
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-100 p-4 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($sessions->isEmpty())
                        <p class="text-gray-500 text-center py-4">Vous n'avez pas encore déclaré de session.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 whitespace-nowrap">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progression</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($sessions as $session)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $session->courseClass->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $session->start_time->format('d/m/Y H:i') }} - {{ $session->end_time->format('H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                {{ number_format($session->amount, 0, ',', ' ') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if($session->status === 'scheduled')
                                                    <span class="px-2 py-1 text-xs text-blue-700 bg-blue-100 rounded-full">Prévue</span>
                                                @elseif($session->status === 'completed')
                                                    <span class="px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full">Effectuée</span>
                                                @elseif($session->status === 'validated')
                                                    <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Validée</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs text-gray-700 bg-gray-100 rounded-full">Annulée</span>
                                                @endif
                                            </td>
                                            {{-- "truncate" coupait le resume a une seule ligne. On en montre
                                                 quelques-unes ici, le texte complet est sur la page de detail. --}}
                                            <td class="px-6 py-4 text-sm text-gray-500 max-w-md">
                                                <div class="line-clamp-3 whitespace-pre-wrap break-words">{{ $session->sessionReport ? $session->sessionReport->progress : 'Non renseignée' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex items-center gap-3">
                                                    <a href="{{ route('coach.sessions.show', $session) }}" class="text-indigo-600 hover:underline">Détails</a>
                                                    @if(in_array($session->status, ['scheduled', 'completed']))
                                                        <form action="{{ route('coach.sessions.cancel', $session) }}" method="POST" onsubmit="return confirm('Confirmer l\'annulation de cette session ?');">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="text-red-600 hover:underline">Annuler</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
