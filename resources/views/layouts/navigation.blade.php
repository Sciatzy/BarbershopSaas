<!-- Sidebar replacing old top-nav -->
<aside class="w-[260px] flex-shrink-0 bg-[#FAFAFB] h-full flex flex-col overflow-y-auto overflow-x-hidden z-30 py-8 px-6 hidden lg:flex border-r border-gray-100">
    @php
        $homeRoute = 'dashboard';

        if (Auth::user()->hasRole('Platform Admin')) {
            $homeRoute = 'admin.dashboard';
        } elseif (Auth::user()->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            $homeRoute = 'manager.dashboard';
        } elseif (Auth::user()->hasRole('Barber')) {
            $homeRoute = 'barber.dashboard';
        } elseif (Auth::user()->hasRole('Customer')) {
            $homeRoute = 'booking.index';
        }

        $isHomeActive = request()->routeIs($homeRoute)
            || ($homeRoute === 'manager.dashboard' && request()->routeIs('manager.*'))
            || ($homeRoute === 'booking.index' && request()->routeIs('booking.*'));
    @endphp

    <!-- Logo -->
    <a href="{{ route($homeRoute) }}" class="flex items-center gap-3 mb-10 group">
        <div class="text-[26px] font-bold text-gray-900 tracking-tight">
            FiiNeo.io
        </div>
    </a>

    <!-- Nav Menu -->
    <nav class="flex flex-col gap-2 relative mb-auto">
        @php
            $navItemClass = "flex items-center gap-4 px-4 py-3.5 rounded-xl text-gray-500 font-semibold transition-all duration-300 hover:text-gray-900 group text-[15px]";
            $activeClass = "bg-[#E6DBFF] text-gray-900 font-bold active-nav";
            $iconClass = "w-5 h-5 text-gray-400 group-hover:text-gray-900 transition-colors";
            $activeIconClass = "w-5 h-5 text-gray-900";
        @endphp

        <!-- Dashboard -->
        <a href="{{ route($homeRoute) }}" class="{{ $navItemClass }} {{ $isHomeActive ? $activeClass : '' }}">
            <svg class="{{ $isHomeActive ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>

        @if (Auth::user()->hasAnyRole(['Barbershop Admin', 'Branch Manager']))
            <a href="{{ route('manager.queue.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.queue.*') ? $activeClass : '' }}">
                <svg class="{{ request()->routeIs('manager.queue.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Queue
            </a>

            <a href="{{ route('manager.barbers.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.barbers.*') ? $activeClass : '' }}">
                <svg class="{{ request()->routeIs('manager.barbers.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Barbers
            </a>
            @if (Auth::user()->hasRole('Barbershop Admin'))
                <a href="{{ route('customer.dashboard') }}" target="_blank" class="{{ $navItemClass }}">
                    <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Customer View
                </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="{{ $navItemClass }}">
                <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Profile
            </a>
        @endif

        @if (Auth::user()->hasRole('Barbershop Admin'))
            <a href="{{ route('billing.plans') }}" class="{{ $navItemClass }} {{ request()->routeIs('billing.plans') ? $activeClass : '' }}">
                <svg class="{{ request()->routeIs('billing.plans') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Billing
            </a>
        @endif

        @if (Auth::user()->hasRole('Platform Admin'))
            <a href="{{ route('admin.dashboard') }}" class="{{ $navItemClass }} {{ request()->routeIs('admin.dashboard') ? $activeClass : '' }}">
                 <svg class="{{ request()->routeIs('admin.dashboard') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Admin
            </a>
        @endif
        
        @if (Auth::user()->hasRole('Customer'))
            <a href="{{ route('booking.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('booking.*') ? $activeClass : '' }}">
                <svg class="{{ request()->routeIs('booking.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Book Now
            </a>
        @endif

    </nav>

    <!-- Bottom Actions -->
    <div class="mt-8 flex flex-col gap-1">
        <a href="{{ route('profile.edit') }}" class="{{ $navItemClass }} {{ request()->routeIs('profile.*') ? $activeClass : '' }}">
            <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Settings
        </a>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="w-full text-left {{ $navItemClass }}">
                <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Log out
            </button>
        </form>
    </div>
</aside>
