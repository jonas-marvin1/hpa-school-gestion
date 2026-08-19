<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Messagerie') }}
            </h2>
            <a href="{{ route('messages.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium">
                Nouveau Message
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex">
                
                <!-- Sidebar -->
                <div class="w-1/4 border-r border-gray-200 bg-gray-50">
                    <nav class="flex flex-col p-4 space-y-1">
                        <a href="{{ route('messages.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('messages.index') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-900 hover:bg-gray-100' }}">
                            Boîte de réception
                        </a>
                        <a href="{{ route('messages.sent') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('messages.sent') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-900 hover:bg-gray-100' }}">
                            Messages envoyés
                        </a>
                    </nav>
                </div>

                <!-- Content -->
                <div class="w-3/4 p-0">
                    @if($messages->count() > 0)
                        <div class="divide-y divide-gray-200">
                            @foreach($messages as $message)
                                <a href="{{ route('messages.show', $message) }}" class="block hover:bg-gray-50 {{ is_null($message->read_at) ? 'bg-blue-50' : 'bg-white' }}">
                                    <div class="px-6 py-4 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                                    {{ substr($message->sender->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900 truncate {{ is_null($message->read_at) ? 'font-bold' : '' }}">
                                                    {{ $message->sender->name }}
                                                </p>
                                                <p class="text-sm text-gray-500 truncate {{ is_null($message->read_at) ? 'font-semibold text-gray-800' : '' }}">
                                                    {{ $message->subject }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 whitespace-nowrap text-sm text-gray-500">
                                            {{ $message->created_at->format('d M H:i') }}
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            {{ $messages->links() }}
                        </div>
                    @else
                        <div class="p-8 text-center text-gray-500">
                            Aucun message dans la boîte de réception.
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
