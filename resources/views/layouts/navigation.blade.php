<!-- Sidebar replacing old top-nav -->
<aside class="w-[260px] flex-shrink-0 bg-[#FAFAFB] h-full overflow-y-auto overflow-x-hidden z-30 py-8 px-6 hidden lg:flex lg:flex-col border-r border-gray-100">
    @php
        $homeRoute = 'dashboard';
        $viewer = Auth::user();
        $viewerTenant = $viewer?->tenant;
        $viewerTenant?->loadMissing('latestCashierSubscription');
        $brandLabel = $viewerTenant?->name ?? config('app.name', 'Barbershop SaaS');
        $tenantName = trim((string) ($viewerTenant?->name ?? ''));

        if ($viewer?->hasRole('Platform Admin')) {
            $brandLabel = 'ADMIN';
        } elseif ($viewer?->hasAnyRole(['Barbershop Admin', 'Branch Manager']) && $tenantName !== '') {
            $brandLabel = str_contains(strtolower($tenantName), 'barbershop')
                ? $tenantName
                : $tenantName.' Barbershop';
        }

        $viewerSubscription = $viewerTenant?->latestCashierSubscription;
        $viewerHasActivePlan = ($viewerTenant?->status === 'active')
            && in_array((string) ($viewerSubscription?->stripe_status ?? ''), ['active', 'trialing'], true)
            && (($viewerSubscription?->ends_at ?? null) === null || $viewerSubscription->ends_at->isFuture());

        $dashboardAccess = $viewerTenant?->resolvedDashboardAccessSettings() ?? \App\Models\Tenant::dashboardAccessDefaults();
        $branchManagerAccess = $dashboardAccess['branch_manager'] ?? [];
        $canAccessManagerQueue = ! $viewer->hasRole('Branch Manager') || (bool) ($branchManagerAccess['manage_queue'] ?? true);
        $canAccessManagerBarbers = ! $viewer->hasRole('Branch Manager') || (bool) ($branchManagerAccess['manage_barbers'] ?? true);
        $canAccessManagerServices = ! $viewer->hasRole('Branch Manager') || (bool) ($branchManagerAccess['manage_services'] ?? true);
        $canAccessManagerSchedules = ! $viewer->hasRole('Branch Manager') || (bool) ($branchManagerAccess['manage_schedules'] ?? true);

        if ($viewer->hasRole('Platform Admin')) {
            $homeRoute = 'admin.dashboard';
        } elseif ($viewer->hasAnyRole(['Barbershop Admin', 'Branch Manager'])) {
            $homeRoute = 'manager.dashboard';
        } elseif ($viewer->hasRole('Barber')) {
            $homeRoute = 'barber.dashboard';
        } elseif ($viewer->hasRole('Customer')) {
            $homeRoute = 'booking.index';
        }

        $isHomeActive = request()->routeIs($homeRoute)
            || ($homeRoute === 'manager.dashboard' && request()->routeIs('manager.*'))
            || ($homeRoute === 'booking.index' && request()->routeIs('booking.*'));
    @endphp

    <!-- Logo -->
    <a href="{{ route($homeRoute) }}" class="flex items-center gap-3 mb-10 group">
        <div class="max-w-[200px] truncate text-[26px] font-bold text-gray-900 tracking-tight" title="{{ $brandLabel }}">
            {{ $brandLabel }}
        </div>
    </a>

    @if ($viewer->hasRole('Barbershop Admin'))
        <div class="mb-6 inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-indigo-700">
            Owner Scope
        </div>
    @elseif ($viewer->hasRole('Branch Manager'))
        <div class="mb-6 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-700">
            Branch Scope
        </div>
    @endif

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

        @if ($viewer->hasAnyRole(['Barbershop Admin', 'Branch Manager']))
            @if ($canAccessManagerQueue)
                <a href="{{ route('manager.queue.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.queue.*') ? $activeClass : '' }}">
                    <svg class="{{ request()->routeIs('manager.queue.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Queue
                </a>
            @endif

            @if ($canAccessManagerBarbers)
                <a href="{{ route('manager.barbers.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.barbers.*') ? $activeClass : '' }}">
                    <svg class="{{ request()->routeIs('manager.barbers.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Barbers
                </a>
            @endif
            @if ($canAccessManagerServices)
                <a href="{{ route('manager.services.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.services.*') ? $activeClass : '' }}">
                    <svg class="{{ request()->routeIs('manager.services.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"></path></svg>
                    Services
                </a>
            @endif
            @if ($viewer->hasRole('Branch Manager') && $canAccessManagerSchedules)
                <a href="{{ route('manager.schedules.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.schedules.*') ? $activeClass : '' }}">
                    <svg class="{{ request()->routeIs('manager.schedules.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Schedules
                </a>
            @endif
            @if ($viewer->hasRole('Barbershop Admin') && $viewerHasActivePlan)
                <a href="{{ route('manager.branches.index') }}" class="{{ $navItemClass }} {{ request()->routeIs('manager.branches.*') ? $activeClass : '' }}">
                    <svg class="{{ request()->routeIs('manager.branches.*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V10l7-7 7 7v11M9 21v-6h6v6"></path></svg>
                    Branches
                </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="{{ $navItemClass }}">
                <svg class="{{ $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Profile
            </a>
        @endif

        @if ($viewer->hasRole('Barbershop Admin'))
            <a href="{{ route('billing.plans') }}" class="{{ $navItemClass }} {{ request()->routeIs('billing.plans') ? $activeClass : '' }}">
                <svg class="{{ request()->routeIs('billing.plans') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Billing
            </a>
        @endif

        @if ($viewer->hasRole('Platform Admin'))
            <a href="{{ route('admin.dashboard') }}" class="{{ $navItemClass }} {{ request()->routeIs('admin.dashboard') ? $activeClass : '' }}">
                 <svg class="{{ request()->routeIs('admin.dashboard') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Admin
            </a>
        @endif

        @if ($viewer->hasRole('Customer'))
            <a href="{{ route('customer.bookings') }}" class="{{ $navItemClass }} {{ request()->routeIs('customer.book*') ? $activeClass : '' }}">
                <svg class="{{ request()->routeIs('customer.book*') ? $activeIconClass : $iconClass }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
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

        @php
            $systemVersion = app(\App\Support\SystemVersion::class)->resolve();
            $versionMeta = collect([
                $systemVersion['status'] ?? null,
                $systemVersion['branch'] ?? null,
                $systemVersion['short_commit'] ?? null,
            ])->filter(fn ($value) => filled($value))->implode(' · ');
        @endphp

        <div class="mt-3 rounded-lg border border-gray-200 bg-white px-3 py-2" title="Fetched from repository metadata or APP_VERSION configuration.">
            <p class="text-[11px] font-semibold text-gray-600">System {{ $systemVersion['display_version'] ?? 'vdev' }}</p>
            @if ($versionMeta !== '')
                <p class="mt-0.5 text-[10px] text-gray-400 uppercase tracking-wide">{{ $versionMeta }}</p>
            @endif
        </div>
    </div>
</aside>
