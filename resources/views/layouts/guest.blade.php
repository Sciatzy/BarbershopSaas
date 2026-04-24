<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Barbershop SaaS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Diagonal shapes for the left pane inspired by the image */
            .diagonal-shape {
                position: absolute;
                border-radius: 9999px;
                transform: rotate(45deg);
                opacity: 0.8;
            }
            .shape-1 { width: 40px; height: 300px; background: linear-gradient(to top, #ef4444, #3b82f6); bottom: -50px; left: 10%; }
            .shape-2 { width: 60px; height: 400px; background: linear-gradient(to top, #ef4444, #fca5a5); bottom: -100px; left: 25%; opacity: 0.9; }
            .shape-3 { width: 30px; height: 200px; background: rgba(255, 255, 255, 0.2); bottom: 50px; left: 40%; }
            .shape-4 { width: 50px; height: 250px; background: linear-gradient(to bottom, #3b82f6, #60a5fa); top: -50px; right: 20%; }
            .shape-5 { width: 80px; height: 180px; background: linear-gradient(to top right, #ef4444, #f87171); bottom: 10%; right: 10%; }
            .circle-shape {
                position: absolute;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(239,68,68,0.4) 0%, rgba(59,130,246,0.1) 70%, transparent 100%);
                width: 300px;
                height: 300px;
                top: 10%;
                right: -50px;
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">
        <div class="min-h-screen flex items-center justify-center p-4 sm:p-8">
            <!-- Main Container -->
            <div class="w-full max-w-[1000px] bg-white rounded-lg shadow-2xl flex flex-col lg:flex-row overflow-hidden lg:min-h-[600px]">

                <!-- Left Side: Barbershop Themed Graphic -->
                <div class="w-full lg:w-1/2 relative overflow-hidden flex flex-col justify-center p-12 text-white bg-gradient-to-br from-blue-700 via-blue-600 to-red-600">

                    <!-- Abstract Background Shapes -->
                    <div class="circle-shape"></div>
                    <div class="diagonal-shape shape-1"></div>
                    <div class="diagonal-shape shape-2"></div>
                    <div class="diagonal-shape shape-3"></div>
                    <div class="diagonal-shape shape-4"></div>
                    <div class="diagonal-shape shape-5"></div>

                    <!-- Content -->
                    <div class="relative z-10">
                        <h2 class="text-4xl font-bold mb-4 drop-shadow-md">
                            Welcome to Barbershop
                        </h2>
                        <p class="text-white/90 text-sm leading-relaxed drop-shadow max-w-sm">
                            Manage your appointments, staff, and services with ease. Log in to access your dashboard and take control of your barbershop operations.
                        </p>
                    </div>
                </div>

                <!-- Right Side: Form Container -->
                <div class="w-full lg:w-1/2 p-8 sm:p-12 lg:p-16 flex flex-col justify-center bg-white relative">
                    {{ $slot }}
                </div>

            </div>
        </div>
    </body>
</html>
