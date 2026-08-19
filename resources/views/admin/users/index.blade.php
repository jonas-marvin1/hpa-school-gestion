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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('status'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-wrap gap-4 items-end">
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

                        <div>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium">Filtrer</button>
                            @if(request()->hasAny(['search', 'role', 'status']))
                                <a href="{{ route('admin.users.index') }}" class="ml-2 text-gray-600 hover:underline text-sm">Réinitialiser</a>
                            @endif
                        </div>
                    </form>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr>
                                <th class="border-b py-2 px-4">Nom</th>
                                <th class="border-b py-2 px-4">Email</th>
                                <th class="border-b py-2 px-4">Rôle</th>
                                <th class="border-b py-2 px-4">Statut</th>
                                <th class="border-b py-2 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
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
                            @endforeach
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
</x-app-layout>
