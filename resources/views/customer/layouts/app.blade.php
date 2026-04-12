<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Dashboard - {{ $tenant->name ?? config('app.name', 'Barbershop SaaS') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink:          #0D1117;
            --surface:      #161B22;
            --surface-2:    #1C2330;
            --surface-3:    #222B3A;
            --border:       rgba(255,255,255,0.07);
            --border-strong:rgba(255,255,255,0.13);

            /* The tenant's custom primary / secondary branding colors logic injected here */
            --gold:         {{ $tenant->brand_color ?? '#C9A84C' }};
            --rust:         {{ $tenant->brand_color_secondary ?? '#B54B2A' }};

            --cream:        #F5EFE0;
            --muted:        #8B9AAD;

            --green:        #28C76F;
            --green-dim:    rgba(40,199,111,0.12);
            --radius:       10px;
            --radius-lg:    16px;
            --radius-xl:    22px;
            --font-display: 'Bebas Neue', sans-serif;
            --font-body:    'DM Sans', sans-serif;
            --font-mono:    'DM Mono', monospace;
        }

        /* Inject CSS hex to RGB conversion variables to support opacity if needed,
           but CSS custom properties are mainly being used explicitly or with rgba()
           Wait, there's `var(--gold-dim)` and `var(--rust-dim)`.
           CSS calc or color-mix is optimal, but let's use color-mix for modern browsers. */
        :root {
            --gold-dim: color-mix(in srgb, var(--gold) 15%, transparent);
            --rust-dim: color-mix(in srgb, var(--rust) 15%, transparent);
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--ink);
            color: var(--cream);
            font-family: var(--font-body);
            display: flex;
            min-height: 100vh;
        }

        a { text-decoration: none; color: inherit; }

        .layout-sidebar {
            width: 260px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 0 24px 24px;
            font-family: var(--font-display);
            font-size: 28px;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .sidebar-user {
            padding: 0 24px 24px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .user-name { font-weight: 500; font-size: 15px; }

        .points-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: flex-start;
            padding: 4px 10px;
            background: var(--gold-dim);
            color: var(--gold);
            border-radius: 999px;
            font-family: var(--font-mono);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .nav-list {
            flex: 1;
            padding: 0 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
        }

        .nav-item {
            padding: 12px 16px;
            border-radius: var(--radius);
            font-weight: 500;
            color: var(--muted);
            transition: color 0.2s;
            position: relative;
            z-index: 1;
        }

        .nav-item:hover, .nav-item.active { color: var(--cream); }

        .nav-indicator {
            position: absolute;
            left: 12px; right: 12px;
            height: 44px;
            background: var(--surface-3);
            border-radius: var(--radius);
            transition: top 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 0;
        }

        .sidebar-queue-widget {
            margin: auto 24px 0;
            padding: 16px;
            background: var(--surface-2);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            font-size: 13px;
            color: var(--muted);
        }

        .sqw-header { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; color: var(--cream); font-weight: 500;}
        .sqw-dot { width: 8px; height: 8px; background: var(--green); border-radius: 50%; box-shadow: 0 0 8px var(--green); }
        .sqw-count { color: var(--cream); font-weight: 500; }

        .layout-main {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .topbar {
            padding: 24px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hamburger { display: none; background: transparent; border: none; color: var(--cream); font-size: 24px; cursor: pointer; }

        .main-content {
            padding: 0 40px 60px;
            flex: 1;
        }

        .bottom-nav { display: none; }

        @media (max-width: 1024px) {
            .layout-sidebar { width: 200px; }
            .layout-main { margin-left: 200px; }
            .main-content { padding: 0 24px 60px; }
        }

        @media (max-width: 768px) {
            .layout-sidebar { transform: translateX(-100%); width: 260px; }
            .layout-sidebar.open { transform: translateX(0); }
            .layout-main { margin-left: 0; }
            .topbar { padding: 20px; }
            .hamburger { display: block; padding: 0; }
            .main-content { padding: 0 20px 80px; }

            .bottom-nav {
                display: flex;
                position: fixed; bottom: 0; left: 0; right: 0;
                background: var(--surface);
                border-top: 1px solid var(--border);
                z-index: 100;
                padding-bottom: env(safe-area-inset-bottom);
            }
            .bottom-nav a {
                flex: 1; display: flex; flex-direction: column; align-items: center;
                padding: 12px 0; gap: 4px; color: var(--muted); font-size: 11px;
            }
            .bottom-nav a.active { color: var(--cream); }
            .bn-icon { font-size: 20px; }
        }

        /* Typography */
        h1, h2, h3, .font-display { font-family: var(--font-display); font-weight: normal; margin: 0; }
        .page-title { font-size: clamp(28px, 4vw, 48px); margin-bottom: 8px; }
        .page-subtitle { color: var(--muted); font-size: 16px; margin-bottom: 32px; }

        /* Animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 12px 24px; border-radius: var(--radius); font-weight: 500; font-size: 15px;
            cursor: pointer; border: none; font-family: var(--font-body);
        }

        .btn-rust { background: var(--rust); color: var(--cream); }

        /* Flash */
        .flash {
            position: fixed; top: 24px; right: 24px; z-index: 500;
            padding: 14px 20px; border-radius: var(--radius);
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; font-family: var(--font-body);
            animation: slideInRight 0.35s ease forwards;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .flash-success { background: var(--green-dim); border: 1px solid var(--green); color: var(--green); }
        .flash-error   { background: var(--rust-dim); border: 1px solid var(--rust); color: #ff8a6e; }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="layout-sidebar" id="sidebar">
        <div class="sidebar-brand">
            {{ $tenant->name ?? 'Barber SaaS' }}
        </div>

        <div class="sidebar-user">
            <span class="user-name">{{ auth()->user()->name }}</span>
            <span class="points-pill">★ {{ auth()->user()->points_balance ?? 0 }} pts</span>
        </div>

        @php($isCustomerUser = auth()->user()->hasRole('Customer'))

        <nav class="nav-list" id="nav-list">
            <div class="nav-indicator" id="nav-indicator"></div>
            <a href="{{ route('customer.dashboard') }}" class="nav-item {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">Dashboard</a>
            @if ($isCustomerUser)
                <a href="{{ route('customer.services') }}" class="nav-item {{ request()->routeIs('customer.services') ? 'active' : '' }}">Book a Service</a>
                <a href="{{ route('customer.bookings') }}" class="nav-item {{ request()->routeIs('customer.bookings') ? 'active' : '' }}">My Bookings</a>
                <a href="{{ route('customer.points') }}" class="nav-item {{ request()->routeIs('customer.points') ? 'active' : '' }}">Points & Rewards</a>
                <a href="{{ route('customer.profile') }}" class="nav-item {{ request()->routeIs('customer.profile') ? 'active' : '' }}">Profile</a>
            @else
                <a href="{{ route('admin.dashboard') }}" class="nav-item">Back to Admin</a>
            @endif

            <form method="POST" action="{{ route('logout') }}" style="margin-top:20px;">
                @csrf
                <button type="submit" class="nav-item" style="width:100%; text-align:left; background:transparent; border:none; font-family:var(--font-body); font-size:15px; cursor:pointer;">
                    Sign Out
                </button>
            </form>
        </nav>

        <div class="sidebar-queue-widget">
            <div class="sqw-header">
                <div class="sqw-dot"></div>
                <span>Live Queue</span>
            </div>
            <div>
                <span class="sqw-count" id="sidebar-queue-count">—</span>
                <span class="sqw-sub">ahead · ~<span id="sidebar-wait">?</span> min</span>
            </div>
        </div>
    </aside>

    <div class="layout-main">
        <header class="topbar">
            <button class="hamburger" id="hamburger">☰</button>
            <div style="flex:1"></div>
            <!-- Notification bell or similar can go here -->
        </header>

        <main class="main-content">
            @include('customer.partials.flash')
            @yield('content')
        </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="bottom-nav">
        <a href="{{ route('customer.dashboard') }}" class="{{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
            <span class="bn-icon">⌂</span><span class="bn-label">Home</span>
        </a>
        @if ($isCustomerUser)
            <a href="{{ route('customer.services') }}" class="{{ request()->routeIs('customer.services') ? 'active' : '' }}">
                <span class="bn-icon">✂</span><span class="bn-label">Book</span>
            </a>
            <a href="{{ route('customer.bookings') }}" class="{{ request()->routeIs('customer.bookings') ? 'active' : '' }}">
                <span class="bn-icon">☰</span><span class="bn-label">History</span>
            </a>
            <a href="{{ route('customer.points') }}" class="{{ request()->routeIs('customer.points') ? 'active' : '' }}">
                <span class="bn-icon">★</span><span class="bn-label">Points</span>
            </a>
            <a href="{{ route('customer.profile') }}" class="{{ request()->routeIs('customer.profile') ? 'active' : '' }}">
                <span class="bn-icon">◯</span><span class="bn-label">Profile</span>
            </a>
        @else
            <a href="{{ route('admin.dashboard') }}">
                <span class="bn-icon">↩</span><span class="bn-label">Admin</span>
            </a>
        @endif
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Flash messages auto-dismiss
            const flash = document.getElementById('flash-msg');
            if (flash) setTimeout(() => {
                flash.style.opacity = '0';
                flash.style.transition = 'opacity 0.3s';
                setTimeout(() => flash.remove(), 300);
            }, 4000);

            // Active nav indicator movement
            const activeItem = document.querySelector('.nav-item.active');
            const indicator = document.getElementById('nav-indicator');
            if (activeItem && indicator && activeItem.offsetTop) {
                indicator.style.top = activeItem.offsetTop + 'px';
            } else if (indicator) {
                indicator.style.opacity = '0'; // Hide if no active
            }

            // Mobile drawer toggle
            const btn = document.getElementById('hamburger');
            const sidebar = document.getElementById('sidebar');
            btn.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });

            // Number counter animation
            function animateCount(el, target, isDecimal = false, prefix = '', suffix = '') {
                const dur = 1200, start = performance.now();
                const ease = t => 1 - Math.pow(1 - t, 4); // quartic ease out
                (function tick(now) {
                    const p = Math.min((now - start) / dur, 1);
                    const val = isDecimal
                        ? (ease(p) * target).toFixed(1)
                        : Math.floor(ease(p) * target);
                    el.textContent = prefix + (val >= 1000 ? Number(val).toLocaleString() : val) + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                })(start);
            }

            document.querySelectorAll('[data-count]').forEach(el => {
                const target = parseFloat(el.dataset.count);
                if(!isNaN(target)) {
                    animateCount(el, target, false, el.dataset.prefix || '', el.dataset.suffix || '');
                }
            });

            setTimeout(() => {
                const bar = document.getElementById('points-fill');
                if (bar) bar.style.width = bar.dataset.pct + '%';
            }, 300);
        });
    </script>
</body>
</html>
