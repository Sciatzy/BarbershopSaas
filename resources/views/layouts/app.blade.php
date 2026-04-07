<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BarberSaaS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 bg-[#FAFAFC] h-screen overflow-hidden selection:bg-[#E2D4FF] selection:text-black">
        <div class="flex h-screen w-full">

            <!-- Sidebar -->
            @include('layouts.navigation')

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

                <!-- Main Header (Search, Notifications, Profile) -->
                <header class="flex justify-between items-center px-10 py-6 shrink-0 z-20 fade-in-up stagger-1">
                    <div class="flex items-center">
                        @if (isset($header))
                            <div class="text-3xl font-bold text-gray-900 tracking-tight">
                                {{ $header }}
                            </div>
                        @else
                            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Hello, {{ explode(' ', Auth::user()->name)[0] }} <span class="inline-block animate-wave origin-bottom-right">👋</span></h1>
                        @endif
                    </div>

                    <div class="flex items-center gap-6">
                        <!-- Search -->
                        <div class="relative hidden md:block group">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-black transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" placeholder="Search" class="pl-12 pr-4 py-2.5 bg-[#F3F4F6] border-none rounded-full w-64 focus:ring-2 focus:ring-[#E2D4FF] focus:bg-white shadow-sm transition-all focus:w-72 outline-none text-sm font-medium placeholder:text-gray-400">
                        </div>

                        <!-- Notifications -->
                        <button class="w-11 h-11 flex items-center justify-center rounded-full border border-gray-200 bg-white hover:bg-gray-50 flex-shrink-0 transition-transform hover:scale-105 shadow-sm relative">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>

                        <!-- Profile Dropdown -->
                        <div class="relative shrink-0 z-50 inline-block" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="w-11 h-11 rounded-full overflow-hidden border-2 border-white hover:border-[#E2D4FF] shadow-sm transition-all focus:outline-none block">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=E2D4FF&color=000&bold=true" class="w-full h-full object-cover">
                            </button>
                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-2" style="display: none;">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium">Profile Settings</a>
                                <div class="h-px bg-gray-100 my-1"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">Sign Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Scrollable Dashboard Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto px-10 pb-12 fade-in-up stagger-2">
                    {{ $slot }}
                </main>
            </div>

        </div>
    </body>
</html>
