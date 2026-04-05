<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <x-application-logo class="w-8 h-8 rounded-full shadow-lg" />
            <h2 class="font-semibold text-2xl text-gray-900 tracking-tight leading-tight">
                {{ __('Secure Account Dashboard') }}
            </h2>
        </div>
    </x-slot>

    <!-- Welcome Section (Animated entry) -->
    <div class="mb-10 w-full bg-white rounded-2xl p-8 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="text-3xl font-bold text-gray-900 mb-2">Welcome back, {{ Auth::user()->name }}!</h3>
            <p class="text-gray-500 max-w-lg">
                Your role determines which features and data you can access. Below is a summary of your account's assigned administrative paths.
            </p>
        </div>
        <div class="hidden lg:block">
            <div class="p-4 bg-barber-accent/10 rounded-full animate-bounce">
                <svg class="w-10 h-10 text-barber-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-slide-up" style="animation-delay: 150ms;">
        <!-- Card 1 -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
            <div class="p-8">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="p-2 bg-indigo-50 text-indigo-500 rounded-lg group-hover:bg-indigo-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.954 11.954 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900">Your Current Roles</h4>
                </div>

                <p class="text-sm text-gray-600 mb-4">Roles shape your experience across BarberSaaS.</p>

                <div class="flex flex-wrap gap-2">
                    @forelse ($roles ?? [] as $role)
                        <span class="inline-flex items-center rounded-lg bg-barber-dark px-3 py-1 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:scale-105">
                            {{ $role }}
                        </span>
                    @empty
                        <span class="inline-flex items-center rounded-lg bg-yellow-50 px-3 py-1 text-xs font-medium text-yellow-800 border border-yellow-200">
                            Unassigned Role
                        </span>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-gradient-to-br from-barber-dark to-black overflow-hidden shadow-sm sm:rounded-2xl border border-gray-800 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="p-8 relative">
                <div class="absolute -right-10 -top-10 bg-white/5 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10">
                    <h4 class="text-xl font-bold text-white mb-2">Explore Your Path</h4>
                    <p class="text-sm text-gray-300 mb-6">
                        Depending on your roles (Platform Admin, Barbershop Admin, Branch Manager, Barber, Customer), specific dashboard paths will dynamically appear here.
                    </p>
                    <a href="#" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-barber-dark bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white transition-all duration-200">
                        View Analytics &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
