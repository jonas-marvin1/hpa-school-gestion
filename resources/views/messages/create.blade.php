<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nouveau Message') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>- {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('messages.store') }}" method="POST">
                        @csrf
                        
                        <div class="space-y-6">
                            
                            <!-- Receiver -->
                            @role('coach')
                                <div class="bg-indigo-50 border border-indigo-200 text-indigo-700 text-sm rounded-md px-4 py-3">
                                    Ce message sera envoyé à l'ensemble des administrateurs et gestionnaires. Ils pourront tous les deux le consulter et vous répondre.
                                </div>
                            @else
                                <div>
                                    <label for="receiver_id" class="block text-sm font-medium text-gray-700">Destinataire *</label>
                                    <select name="receiver_id" id="receiver_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Sélectionnez un destinataire</option>
                                        @foreach($recipients as $recipient)
                                            <option value="{{ $recipient->id }}" {{ old('receiver_id', request('receiver_id')) == $recipient->id ? 'selected' : '' }}>
                                                {{ $recipient->name }} ({{ implode(', ', $recipient->getRoleNames()->toArray()) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endrole

                            <!-- Subject -->
                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700">Sujet *</label>
                                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Body -->
                            <div>
                                <label for="body" class="block text-sm font-medium text-gray-700">Message *</label>
                                <textarea name="body" id="body" rows="6" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                            </div>

                        </div>

                        <div class="mt-8 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Envoyer
                            </button>
                            <a href="{{ route('messages.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
