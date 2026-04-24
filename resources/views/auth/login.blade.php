<x-guest-layout>
    <div class="w-full max-w-sm mx-auto">
        <!-- Header -->
        <div class="mb-10 text-center">
            <h1 class="text-xl font-bold text-blue-600 tracking-wide uppercase">
                User Login
            </h1>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4 text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-200" :status="session('status')" />

        @php
            $currentTenant = request()->attributes->get('currentTenant');
            $isTenantLogin = $currentTenant !== null;
        @endphp

        <form method="POST" action="{{ route('login', [], false) }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <x-text-input id="email" class="block w-full pl-12 pr-5 py-3 text-sm rounded-full border-transparent bg-blue-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Email Address" />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-[10px] text-red-500 pl-4 hidden" />
            </div>

            <!-- Password -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <x-text-input id="password" class="block w-full pl-12 pr-5 py-3 text-sm rounded-full border-transparent bg-blue-50 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                              type="password"
                              name="password"
                              required autocomplete="current-password" placeholder="Password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-[10px] text-red-500 pl-4 hidden" />
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between px-1">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 w-4 h-4 cursor-pointer" name="remember">
                    <span class="ml-2 text-xs text-gray-500 cursor-pointer hover:text-blue-600 transition-colors">Remember</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-xs text-gray-500 hover:text-blue-600 transition-colors" href="{{ route('password.request', [], false) }}">
                        Forgot password?
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex justify-center">
                <button type="submit" class="w-32 py-2.5 px-4 border border-transparent rounded-full shadow-md text-sm font-bold text-white bg-gradient-to-r from-red-500 to-blue-600 hover:from-red-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 hover:-translate-y-0.5">
                    LOGIN
                </button>
            </div>
        </form>

        <div class="mt-8 text-center">
            @if ($isTenantLogin)
                <a href="{{ route('customer.register', [], false) }}" class="text-xs text-gray-500 hover:text-blue-600 transition-colors">
                    Are you a new Customer? Sign up here.
                </a>
            @else
                <a href="{{ route('register', [], false) }}" class="text-xs text-gray-500 hover:text-blue-600 transition-colors">
                    New barbershop owner? Register here.
                </a>
            @endif
        </div>
    </div>
</x-guest-layout>
