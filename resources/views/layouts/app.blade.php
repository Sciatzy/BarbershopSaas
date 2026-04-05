<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BarberSaaS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 bg-gray-50 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] selection:bg-barber-accent selection:text-white">
        <div class="min-h-screen flex flex-col">
            <!-- Navigation Header -->
            <div class="sticky top-0 z-40">
                @include('layouts.navigation')
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-gray-200 shadow-sm relative z-30">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-between animate-fade-in">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 max-w-7xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8 w-full animate-slide-up">
                {{ $slot }}
            </main>

            <!-- Simple Footer -->
            <footer class="mt-auto py-6 text-center text-sm text-gray-500 border-t border-gray-200 bg-white">
                &copy; {{ date('Y') }} {{ config('app.name', 'BarberSaaS') }}. All rights reserved.
            </footer>
        </div>
    </body>
</html>
