<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Toutes les notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Vos notifications</h3>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                            <form action="{{ route('notifications.readAll') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    Tout marquer comme lu
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="divide-y divide-gray-200">
                        @forelse($notifications as $notification)
                            <div class="py-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-md font-semibold text-gray-900">
                                            {{ $notification->data['title'] ?? 'Notification' }}
                                        </h4>
                                        <p class="text-gray-600 mt-1">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>
                                        <div class="mt-2 text-sm text-gray-400">
                                            {{ $notification->created_at->format('d/m/Y H:i') }} 
                                            ({{ $notification->created_at->diffForHumans() }})
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        @if(isset($notification->data['action_url']))
                                            <a href="{{ $notification->data['action_url'] }}" class="text-sm bg-gray-100 px-3 py-1 rounded hover:bg-gray-200">Voir détails</a>
                                        @endif
                                        
                                        @if(!$notification->read_at)
                                            <a href="{{ route('notifications.read', $notification->id) }}" class="text-sm text-indigo-600 hover:underline">Marquer comme lu</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-gray-500">
                                Vous n'avez aucune notification.
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
