<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $tenant->name ?? 'Our Barbershop' }} - Reserve Your Spot</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50 selection:bg-barber-accent selection:text-white">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <h1 class="text-xl font-bold text-gray-900">{{ $tenant->name ?? 'Our Barbershop' }}</h1>
                    <div class="flex items-center gap-4 text-sm">
                        <a href="#services" class="text-gray-600 hover:text-gray-900">Services</a>
                        <a href="#barbers" class="text-gray-600 hover:text-gray-900">Barbers</a>
                        <a href="{{ auth()->check() ? route('booking.create') : route('login') }}" class="px-4 py-2 rounded-md bg-gray-900 text-white hover:bg-gray-800">Reserve My Spot</a>
                    </div>
                </div>
            </div>
        </nav>

        <section class="py-16 bg-gradient-to-br from-barber-dark to-slate-900 text-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-sm uppercase tracking-[0.2em] text-white/70">Welcome to</p>
                <h2 class="text-4xl md:text-6xl font-extrabold mt-3">{{ $tenant->name ?? 'Our Barbershop' }}</h2>
                <p class="mt-5 text-white/80 text-lg">Book your next cut in minutes and keep your style sharp.</p>
                <div class="mt-8">
                    <a href="#reserve" class="inline-flex items-center px-6 py-3 rounded-full bg-white text-gray-900 font-semibold hover:bg-gray-100">
                        Reserve My Spot
                    </a>
                </div>
            </div>
        </section>

        <section id="services" class="py-14">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h3 class="text-2xl font-bold text-gray-900">Services</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                    @forelse($services as $service)
                        <div class="service-card bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <p class="text-lg font-semibold text-gray-900">{{ $service->name }}</p>
                            <p class="text-sm text-gray-600 mt-1">₱{{ number_format((float) ($service->base_price ?? $service->price ?? 0), 2) }} - {{ (int) ($service->duration_min ?? $service->duration_minutes ?? 0) }} min</p>
                            @if (!empty($service->description))
                                <p class="text-sm text-gray-500 mt-3">{{ $service->description }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="col-span-full bg-white rounded-xl border border-gray-200 p-5 text-gray-600">
                            Services will appear here soon.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="barbers" class="py-14 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <h3 class="text-2xl font-bold text-gray-900">Our Barbers</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-6">
                    @forelse($barbers as $barber)
                        <div class="rounded-xl border border-gray-200 p-5 bg-gray-50">
                            <p class="font-semibold text-gray-900">{{ $barber->name }}</p>
                            <p class="text-sm text-gray-600 mt-1">Professional Barber</p>
                        </div>
                    @empty
                        <div class="md:col-span-3 rounded-xl border border-gray-200 p-5 text-gray-600 bg-gray-50">
                            Barber roster is being prepared.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="reserve" class="py-14">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-gray-900">Reserve My Spot</h3>
                    <p class="text-sm text-gray-600 mt-1">Pick your service and confirm your visit.</p>

                    <form id="reserve-form" method="POST" action="{{ route('booking.store') }}" class="space-y-4 mt-5">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="service_id">Service</label>
                            <select id="service_id" name="service_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }} - ₱{{ number_format((float) ($service->base_price ?? $service->price ?? 0), 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="staff_id">Preferred Barber</label>
                            <select id="staff_id" name="staff_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Any available barber</option>
                                @foreach($barbers as $barber)
                                    <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" maxlength="300" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Optional request"></textarea>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">Reserve My Spot</button>
                    </form>
                </div>
            </div>
        </section>

        <script>
            window.__isLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
            window.__loginRedirect = '{{ route('booking.login-required') }}';
            window.__loginMessage = 'Please log in to reserve your spot.';
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const reserveForm = document.getElementById('reserve-form');

                if (!reserveForm) {
                    return;
                }

                reserveForm.addEventListener('submit', function (event) {
                    if (window.__isLoggedIn) {
                        return;
                    }

                    event.preventDefault();
                    window.location.href = window.__loginRedirect;
                });
            });
        </script>
    </body>
</html>
