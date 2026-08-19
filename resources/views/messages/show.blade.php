<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $message->subject }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ url()->previous() }}" class="text-indigo-600 hover:underline">&larr; Retour à la messagerie</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
                        <div>
                            <p class="font-medium text-gray-900">De : {{ $message->sender->name }}</p>
                            <p class="text-sm text-gray-500">À : {{ $message->receiver->name }}</p>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $message->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    <div class="prose max-w-none text-gray-800">
                        {!! nl2br(e($message->body)) !!}
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-200">
                        <a href="{{ route('messages.create') }}?receiver_id={{ $message->sender_id !== Auth::id() ? $message->sender_id : $message->receiver_id }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Répondre
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
