<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'BarberSaaS') }} - Manage Your Barbershop</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700,800&display=swap" rel="stylesheet" />
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50 selection:bg-barber-accent selection:text-white">
        <!-- Navigation -->
        <nav class="absolute top-0 w-full z-50 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-24">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="w-10 h-10 text-barber-accent" />
                        <span class="text-2xl font-bold text-white tracking-tight">Barber<span class="text-barber-accent">SaaS</span></span>
                    </div>
                    <div class="flex items-center gap-6">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-white hover:text-barber-accent transition-colors">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-semibold text-white hover:text-barber-accent transition-colors">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="px-6 py-2.5 text-sm font-bold text-white bg-barber-accent rounded-full hover:bg-amber-500 hover:-translate-y-0.5 transition-all shadow-lg hover:shadow-barber-accent/30">Get Started</a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative bg-barber-dark min-h-screen flex items-center justify-center overflow-hidden">
            <!-- Background Abstract Lines (Simulated Waves) -->
            <div class="absolute inset-0 opacity-20 pointer-events-none">
                <svg class="absolute w-[200%] h-[200%] top-[-50%] left-[-50%]" viewBox="0 0 100 100" preserveAspectRatio="none">
                    @for ($i = 0; $i < 40; $i++)
                        <path d="M0,{{ 10 + ($i * 3) }} Q25,{{ 0 + ($i * 3) }} 50,{{ 10 + ($i * 3) }} T100,{{ 10 + ($i * 3) }}" fill="none" stroke="white" stroke-width="0.5" />
                    @endfor
                </svg>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-16 text-center animate-slide-up">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white/90 text-sm font-medium mb-8 backdrop-blur-sm border border-white/20">
                    <span class="flex h-2 w-2 rounded-full bg-barber-accent"></span>
                    The ultimate platform for modern barbershops
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold text-white tracking-tight leading-tight mb-8">
                    Elevate Your Barbershop<br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-barber-accent to-yellow-300">Management Experience</span>
                </h1>
                <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-12 font-light">
                    Streamline appointments, manage multiple branches, handle staff schedules, and keep your clients coming back with our powerful points and rewards system.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 text-lg font-bold text-barber-dark bg-white rounded-full hover:bg-gray-100 hover:scale-105 transition-all shadow-[0_0_40px_rgba(255,255,255,0.2)]">
                        Start your free trial
                    </a>
                    <a href="#about" class="w-full sm:w-auto px-8 py-4 text-lg font-semibold text-white border-2 border-white/20 rounded-full hover:bg-white/10 transition-colors">
                        Learn more
                    </a>
                </div>
            </div>

            <!-- Bottom Fade -->
            <div class="absolute bottom-0 inset-x-0 h-32 bg-gradient-to-t from-gray-50 to-transparent"></div>
        </div>

        <!-- About Section -->
        <section id="about" class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-base text-barber-accent font-bold tracking-wide uppercase">About BarberSaaS</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">Everything you need to succeed</p>
                    <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">We built BarberSaaS from the ground up for modern barbershops. Say goodbye to messy notebooks and clunky spreadsheets.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                    <!-- Feature 1 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow border border-gray-100 text-center hover:-translate-y-1 duration-300">
                        <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mb-6 transform rotate-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Booking</h3>
                        <p class="text-gray-600">Give your clients the power to book, reschedule, or cancel their appointments online 24/7 without calling the shop.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow border border-gray-100 text-center hover:-translate-y-1 duration-300">
                        <div class="w-16 h-16 mx-auto bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mb-6 transform -rotate-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Staff Management</h3>
                        <p class="text-gray-600">Assign specific services to different barbers, manage their availability, and track their performance from one dashboard.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow border border-gray-100 text-center hover:-translate-y-1 duration-300">
                        <div class="w-16 h-16 mx-auto bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 transform rotate-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Rewards Program</h3>
                        <p class="text-gray-600">Retain clients through an integrated loyalty point system. Automatically reward points for punctuality and frequent visits.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Subscription / Pricing Section -->
        <section id="pricing" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Simple, transparent pricing for shops of all sizes</h2>
                    <p class="mt-4 text-xl text-gray-500">Pick the plan that fits your growth.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto items-center">

                    <!-- Basic Tier -->
                    <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm hover:shadow-xl transition-all duration-300">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
                        <p class="text-gray-500 mb-6 font-light">Perfect for solo barbers.</p>
                        <div class="mb-6 flex items-baseline">
                            <span class="text-5xl font-extrabold tracking-tight text-gray-900">₱499</span>
                            <span class="text-xl font-medium text-gray-500 ml-1">/mo</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> 1 Branch</li>
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Up to 2 Barbers</li>
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Online Booking Link</li>
                            <li class="flex items-center text-gray-400"><svg class="w-5 h-5 text-gray-300 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> <span class="line-through">Rewards System</span></li>
                        </ul>
                        <a href="{{ route('register', ['plan' => 'starter']) }}" class="block w-full py-4 text-center font-bold rounded-xl text-barber-dark bg-gray-100 hover:bg-gray-200 transition-colors">Start Starter</a>
                    </div>

                    <!-- Pro Tier (Highlighted) -->
                    <div class="bg-barber-dark rounded-[2rem] p-8 shadow-2xl relative transform md:-translate-y-4 shadow-barber-dark/30 ring-1 ring-white/10">
                        <div class="absolute top-0 right-8 transform -translate-y-1/2">
                            <span class="bg-gradient-to-r from-barber-accent to-yellow-400 text-white text-xs font-bold px-3 py-1 uppercase tracking-wide rounded-full shadow-lg">Most Popular</span>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Professional</h3>
                        <p class="text-gray-400 mb-6 font-light">Ideal for growing shops.</p>
                        <div class="mb-6 flex items-baseline">
                            <span class="text-5xl font-extrabold tracking-tight text-white">₱1,299</span>
                            <span class="text-xl font-medium text-gray-400 ml-1">/mo</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center text-gray-300"><svg class="w-5 h-5 text-barber-accent mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Up to 3 Branches</li>
                            <li class="flex items-center text-gray-300"><svg class="w-5 h-5 text-barber-accent mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Up to 10 Barbers</li>
                            <li class="flex items-center text-gray-300"><svg class="w-5 h-5 text-barber-accent mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Full Rewards Point System</li>
                            <li class="flex items-center text-gray-300"><svg class="w-5 h-5 text-barber-accent mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Custom Shop Subdomain</li>
                        </ul>
                        <a href="{{ route('register', ['plan' => 'professional']) }}" class="block w-full py-4 text-center font-bold rounded-xl text-barber-dark bg-white hover:bg-gray-100 transition-colors shadow-lg hover:shadow-xl">Start Professional</a>
                    </div>

                    <!-- Enterprise Tier -->
                    <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm hover:shadow-xl transition-all duration-300">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
                        <p class="text-gray-500 mb-6 font-light">For barbershop chains.</p>
                        <div class="mb-6 flex items-baseline">
                            <span class="text-5xl font-extrabold tracking-tight text-gray-900">₱4,999</span>
                            <span class="text-xl font-medium text-gray-500 ml-1">/mo</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-barber-dark mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Unlimited Branches</li>
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-barber-dark mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Unlimited Barbers</li>
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-barber-dark mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Advanced Analytics</li>
                            <li class="flex items-center text-gray-600"><svg class="w-5 h-5 text-barber-dark mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Dedicated Support</li>
                        </ul>
                        <a href="{{ route('register', ['plan' => 'enterprise']) }}" class="block w-full py-4 text-center font-bold rounded-xl text-barber-dark bg-gray-100 hover:bg-gray-200 transition-colors">Start Enterprise</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Small Footer -->
        <footer class="bg-gray-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="flex justify-center items-center gap-2 mb-6 text-white text-xl font-bold">
                    <!-- Barber Logo Fallback using generic scissors/pole SVG if component fails -->
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4h6m-3-3v3M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18H0m3 3v-3m15 3h-6m3 3v-3" /></svg>
                    Barber<span class="text-gray-400">SaaS</span>
                </div>
                <p class="text-gray-400 text-sm">© 2026 BarberSaaS Platform. All rights reserved.</p>
            </div>
        </footer>
    </body>
</html>
