<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 border-t-4 border-t-hpa-orange">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @role('admin')
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Tableau de bord') }}
                        </x-nav-link>

                        @php
                            $isAdminDropdownActive = request()->routeIs([
                                'admin.users.*',
                                'admin.programs.*',
                                'admin.levels.*',
                                'admin.classes.*'
                            ]);
                        @endphp
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out cursor-pointer h-16 focus:outline-none {{ $isAdminDropdownActive ? 'border-indigo-400 dark:border-indigo-600 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700' }}">
                                    <span>{{ __('Administration') }}</span>
                                    <svg class="ms-1 h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('admin.users.index')" class="{{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium' : '' }}">
                                    {{ __('Utilisateurs') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.programs.index')" class="{{ request()->routeIs('admin.programs.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium' : '' }}">
                                    {{ __('Programmes') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.levels.index')" class="{{ request()->routeIs('admin.levels.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium' : '' }}">
                                    {{ __('Niveaux') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.classes.index')" class="{{ request()->routeIs('admin.classes.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-medium' : '' }}">
                                    {{ __('Classes') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-nav-link :href="route('manager.payments.index')" :active="request()->routeIs('manager.payments.*')">
                            {{ __('Fiches de Paie') }}
                        </x-nav-link>

                        <x-nav-link :href="route('manager.sessions.index')" :active="request()->routeIs('manager.sessions.*')">
                            {{ __('Planning') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.submissions.index')" :active="request()->routeIs('admin.submissions.*')">
                            {{ __('Évaluations') }}
                        </x-nav-link>
                        <x-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">
                            {{ __('Messagerie') }}
                        </x-nav-link>
                    @endrole

                    @role('manager')
                        <x-nav-link :href="route('manager.dashboard')" :active="request()->routeIs('manager.dashboard')">
                            {{ __('Tableau de bord') }}
                        </x-nav-link>
                        <x-nav-link :href="route('manager.classes.index')" :active="request()->routeIs('manager.classes.*')">
                            {{ __('Affectations') }}
                        </x-nav-link>
                        <x-nav-link :href="route('manager.sessions.index')" :active="request()->routeIs('manager.sessions.*')">
                            {{ __('Planning') }}
                        </x-nav-link>
                        <x-nav-link :href="route('manager.payments.index')" :active="request()->routeIs('manager.payments.*')">
                            {{ __('Fiches de Paie') }}
                        </x-nav-link>
                        <x-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">
                            {{ __('Messagerie') }}
                        </x-nav-link>
                    @endrole

                    @role('coach')
                        {{-- Sept rubriques alignees seraient illisibles : elles sont
                             regroupees par nature. « Enseignement » rassemble ce qui
                             concerne les cours, « Suivi » ce qui concerne le travail
                             des apprenants. --}}
                        <x-nav-link :href="route('coach.dashboard')" :active="request()->routeIs('coach.dashboard')">
                            {{ __('Tableau de bord') }}
                        </x-nav-link>

                        <x-menu-onglet titre="Enseignement"
                            :actif="request()->routeIs('coach.programmes.*', 'coach.classes.*', 'coach.cours.*', 'coach.sessions.*')"
                            :liens="[
                                ['Mes programmes',    route('coach.programmes.index'), 'coach.programmes.*'],
                                ['Mes classes',       route('coach.classes.index'),    'coach.classes.*'],
                                ['Mes prochains cours', route('coach.cours.index'),    'coach.cours.*'],
                                ['Mon planning',      route('coach.sessions.index'),   'coach.sessions.*'],
                            ]" />

                        <x-menu-onglet titre="Suivi"
                            :actif="request()->routeIs('coach.assignments.*', 'coach.evaluations.*', 'coach.reports.*')"
                            :liens="[
                                ['Devoirs & évaluations', route('coach.assignments.index'), 'coach.assignments.*'],
                                ['Mes rapports',          route('coach.reports.index'),     'coach.reports.*'],
                            ]" />

                        <x-nav-link :href="route('coach.payments.index')" :active="request()->routeIs('coach.payments.*')">
                            {{ __('Rémunérations') }}
                        </x-nav-link>
                        <x-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">
                            {{ __('Messagerie') }}
                        </x-nav-link>
                    @endrole

                    @role('student')
                        <x-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                            {{ __('Tableau de bord') }}
                        </x-nav-link>

                        <x-menu-onglet titre="Ma formation"
                            :actif="request()->routeIs('student.programme.*', 'student.emploi.*', 'student.progression.*')"
                            :liens="[
                                ['Mon programme',      route('student.programme.index'),   'student.programme.*'],
                                ['Mon emploi du temps', route('student.emploi.index'),     'student.emploi.*'],
                                ['Ma progression',     route('student.progression.index'), 'student.progression.*'],
                            ]" />

                        <x-nav-link :href="route('student.assignments.index')" :active="request()->routeIs('student.assignments.*')">
                            {{ __('Mes devoirs') }}
                        </x-nav-link>
                        <x-nav-link :href="route('student.payments.index')" :active="request()->routeIs('student.payments.*')">
                            {{ __('Mes paiements') }}
                        </x-nav-link>
                        <x-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">
                            {{ __('Messagerie') }}
                        </x-nav-link>
                    @endrole
                </div>
            </div>

            <!-- Notifications Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="64">
                    <x-slot name="trigger">
                        <button class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full">{{ Auth::user()->unreadNotifications->count() }}</span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="max-h-96 overflow-y-auto">
                            @forelse(Auth::user()->notifications()->take(5)->get() as $notification)
                                <div class="px-4 py-3 border-b {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}">
                                    <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-sm text-gray-500">{{ $notification->data['message'] ?? '' }}</p>
                                    <div class="mt-2 text-xs text-gray-400 flex justify-between">
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                        @if(!$notification->read_at)
                                            <a href="{{ route('notifications.read', $notification->id) }}" class="text-indigo-600 hover:text-indigo-800">Marquer lu</a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-gray-500">
                                    Aucune notification.
                                </div>
                            @endforelse
                        </div>
                        @if(Auth::user()->notifications->count() > 0)
                            <div class="px-4 py-2 border-t text-center">
                                <a href="{{ route('notifications.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Voir toutes les notifications</a>
                            </div>
                        @endif
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-2">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            @if(Auth::user()->avatar)
                                <img class="h-8 w-8 rounded-full object-cover mr-2" src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" />
                            @else
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold mr-2">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                            @endif
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @role('admin')
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    {{ __('Tableau de bord Admin') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Utilisateurs') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.programs.index')" :active="request()->routeIs('admin.programs.*')">
                    {{ __('Programmes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.levels.index')" :active="request()->routeIs('admin.levels.*')">
                    {{ __('Niveaux') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.classes.index')" :active="request()->routeIs('admin.classes.*')">
                    {{ __('Classes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('manager.sessions.index')" :active="request()->routeIs('manager.sessions.*')">
                    {{ __('Planning') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('manager.payments.index')" :active="request()->routeIs('manager.payments.*')">
                    {{ __('Fiches de Paie') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.submissions.index')" :active="request()->routeIs('admin.submissions.*')">
                    {{ __('Évaluations') }}
                </x-responsive-nav-link>
            @endrole

            @role('manager')
                <x-responsive-nav-link :href="route('manager.dashboard')" :active="request()->routeIs('manager.dashboard')">
                    {{ __('Tableau de bord Manager') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('manager.classes.index')" :active="request()->routeIs('manager.classes.*')">
                    {{ __('Affectations') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('manager.sessions.index')" :active="request()->routeIs('manager.sessions.*')">
                    {{ __('Planning') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('manager.payments.index')" :active="request()->routeIs('manager.payments.*')">
                    {{ __('Fiches de Paie') }}
                </x-responsive-nav-link>
            @endrole

            @role('coach')
                <x-responsive-nav-link :href="route('coach.dashboard')" :active="request()->routeIs('coach.dashboard')">
                    {{ __('Tableau de bord') }}
                </x-responsive-nav-link>
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Enseignement</p>
                <x-responsive-nav-link :href="route('coach.programmes.index')" :active="request()->routeIs('coach.programmes.*')">
                    {{ __('Mes programmes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('coach.classes.index')" :active="request()->routeIs('coach.classes.*')">
                    {{ __('Mes classes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('coach.cours.index')" :active="request()->routeIs('coach.cours.*')">
                    {{ __('Mes prochains cours') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('coach.sessions.index')" :active="request()->routeIs('coach.sessions.*')">
                    {{ __('Mon planning') }}
                </x-responsive-nav-link>
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Suivi</p>
                <x-responsive-nav-link :href="route('coach.assignments.index')" :active="request()->routeIs('coach.assignments.*')">
                    {{ __('Devoirs & évaluations') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('coach.reports.index')" :active="request()->routeIs('coach.reports.*')">
                    {{ __('Mes rapports') }}
                </x-responsive-nav-link>
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Autres</p>
                <x-responsive-nav-link :href="route('coach.payments.index')" :active="request()->routeIs('coach.payments.*')">
                    {{ __('Rémunérations') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">
                    {{ __('Messagerie') }}
                </x-responsive-nav-link>
            @endrole

            @role('student')
                <x-responsive-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                    {{ __('Tableau de bord') }}
                </x-responsive-nav-link>
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Ma formation</p>
                <x-responsive-nav-link :href="route('student.programme.index')" :active="request()->routeIs('student.programme.*')">
                    {{ __('Mon programme') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.emploi.index')" :active="request()->routeIs('student.emploi.*')">
                    {{ __('Mon emploi du temps') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.progression.index')" :active="request()->routeIs('student.progression.*')">
                    {{ __('Ma progression') }}
                </x-responsive-nav-link>
                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Autres</p>
                <x-responsive-nav-link :href="route('student.assignments.index')" :active="request()->routeIs('student.assignments.*')">
                    {{ __('Mes devoirs') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.payments.index')" :active="request()->routeIs('student.payments.*')">
                    {{ __('Mes paiements') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">
                    {{ __('Messagerie') }}
                </x-responsive-nav-link>
            @endrole
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
