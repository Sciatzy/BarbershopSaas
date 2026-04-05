<x-guest-layout>
    <div class="w-full max-w-sm mx-auto animate-slide-up">
        <!-- Header -->
        <div class="mb-8 text-center lg:text-left">
            <h1 class="text-[32px] font-bold text-gray-900 flex items-center justify-center lg:justify-start gap-2 mb-1">
                Login <span class="origin-bottom-right animate-[wave_2.5s_infinite]">👋</span>
            </h1>
            <p class="text-xs text-gray-500">How do I get started lorem ipsum dolor at?</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4 text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-200" :status="session('status')" />

        <!-- Social Logins (Visual Presentation) -->
        <div class="flex flex-col gap-3 mb-8">
            <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-2.5 rounded-full bg-red-50/50 hover:bg-red-50 transition-colors shadow-sm text-sm font-medium text-gray-700">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.16v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.16C1.43 8.55 1 10.22 1 12s.43 3.45 1.16 4.93l3.68-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.16 7.07l3.68 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Sign in with Google
            </button>
            <button type="button" class="w-full flex items-center justify-center gap-3 px-4 py-2.5 rounded-full bg-blue-50/50 hover:bg-blue-50 transition-colors shadow-sm text-sm font-medium text-gray-700">
                <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Sign in with Facebook
            </button>
        </div>

        <!-- Divider -->
        <div class="relative flex items-center justify-center mb-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-100"></div>
            </div>
            <div class="relative bg-white px-3 text-[10px] tracking-wider text-gray-300 font-medium">
                or Login with Email
            </div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="text-xs font-semibold text-gray-700 pl-1 mb-1.5" />
                <x-text-input id="email" class="block w-full px-5 py-3 text-sm rounded-full border-gray-200 bg-gray-50 focus:bg-white focus:ring-[#5e5ce4] focus:border-[#5e5ce4] shadow-[inset_0_1px_2px_rgba(0,0,0,0.02)] transition-all duration-200" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="betuAos312@gmail.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-[10px] text-red-500 pl-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" class="text-xs font-semibold text-gray-700 pl-1 mb-1.5" />
                <x-text-input id="password" class="block w-full px-5 py-3 text-sm rounded-full border-gray-200 bg-gray-50 focus:bg-white focus:ring-[#5e5ce4] focus:border-[#5e5ce4] shadow-[inset_0_1px_2px_rgba(0,0,0,0.02)] transition-all duration-200"
                              type="password"
                              name="password"
                              required autocomplete="current-password" placeholder="Enter your Password" />

                <div class="flex justify-end mt-2 pr-1">
                    @if (Route::has('password.request'))
                        <a class="text-[11px] font-semibold text-[#5e5ce4] hover:text-[#4a48b5] hover:underline transition-colors" href="{{ route('password.request') }}">
                            Forgot Password?
                        </a>
                    @endif
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-[10px] text-red-500 pl-2" />
            </div>

            <!-- Remember Me hidden to perfectly visually match the form, but functional via a hidden input to retain intended Laravel breeze flow if we want it, or just omit -->
            <input type="hidden" name="remember" value="on">

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-full shadow-lg shadow-[#5e5ce4]/30 text-sm font-bold text-white bg-[#5e5ce4] hover:bg-[#4a48b5] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#5e5ce4] transition-all duration-300 hover:-translate-y-0.5">
                    Login
                </button>
            </div>
        </form>

        <div class="mt-12 text-center lg:text-left pl-1">
            <p class="text-[10px] text-gray-400 font-medium tracking-wide">© 2026 Fragance. All Rights Reserved.</p>
        </div>
    </div>

    <style>
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(20deg); }
            50% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }
    </style>
</x-guest-layout>
