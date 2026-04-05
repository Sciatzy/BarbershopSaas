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
    </head>
    <body class="font-sans text-gray-900 antialiased bg-[#e9f2fa]">
        <div class="min-h-screen flex items-center justify-center p-4 sm:p-8">
            <!-- Main Login Card -->
            <div class="w-full max-w-[1000px] bg-white rounded-[2rem] shadow-2xl flex overflow-hidden lg:h-[650px] animate-fade-in">

                <!-- Left Side: Form Container -->
                <div class="w-full lg:w-1/2 p-8 sm:p-12 lg:p-16 flex flex-col justify-center overflow-y-auto bg-white">
                    {{ $slot }}
                </div>

                <!-- Right Side: Graphic/Banner -->
                <div class="hidden lg:flex w-1/2 bg-[#5e5ce4] relative flex-col items-center justify-center p-12 text-white overflow-hidden rounded-r-[2rem] m-2 ml-0 shadow-inner">
                    <!-- Background Abstract Lines (Simulated Waves) -->
                    <div class="absolute inset-0 opacity-10 pointer-events-none">
                        <svg class="absolute w-[200%] h-[200%] top-[-50%] left-[-50%]" viewBox="0 0 100 100" preserveAspectRatio="none">
                            @for ($i = 0; $i < 20; $i++)
                                <path d="M0,{{ 20 + ($i * 5) }} Q25,{{ 10 + ($i * 5) }} 50,{{ 20 + ($i * 5) }} T100,{{ 20 + ($i * 5) }}" fill="none" stroke="white" stroke-width="1" />
                            @endfor
                        </svg>
                    </div>

                    <!-- Floating Emoji Icons Context Elements -->
                    <div class="absolute top-[25%] right-8 bg-white text-black p-3 w-12 h-12 flex items-center justify-center rounded-full shadow-lg transform rotate-12 z-20 hover:scale-110 transition-transform">
                        💯
                    </div>
                    <div class="absolute bottom-[25%] left-6 bg-white text-black p-3 w-12 h-12 flex items-center justify-center rounded-full shadow-lg transform -rotate-12 z-20 hover:scale-110 transition-transform">
                        🤝
                    </div>

                    <!-- Main Banner Content -->
                    <div class="relative z-10 w-full h-full flex flex-col justify-start mt-8">
                        <!-- Heading Text -->
                        <div class="text-left font-bold max-w-[280px]">
                            <h2 class="text-3xl leading-snug">
                                Very good works are<br>
                                waiting for you <span class="inline-block animate-bounce">🤞</span><br>
                                Login Now
                            </h2>
                        </div>

                        <!-- Inspiration Image Illustration -->
                        <div class="flex-1 flex items-end justify-center relative w-full h-full min-h-[250px] mt-8">
                            <img src="https://images.unsplash.com/photo-1599351431202-1e0f0137899a?auto=format&fit=crop&w=400&h=450&q=80"
                                 alt="Professional Barber at work"
                                 class="absolute bottom-[-1rem] drop-shadow-2xl rounded-t-[3rem] object-cover h-[120%] w-[85%] border-b-0 border-4 border-white/10 shadow-[0_0_40px_rgba(0,0,0,0.3)] transition-transform duration-700 hover:scale-105"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </body>
</html>
