<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Subscription Required
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @if (session('plan_required'))
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                        {{ session('plan_required') }}
                    </div>
                @endif

                <p class="text-sm text-gray-700">
                    This tenant currently has no active plan. Booking and barber features stay locked until a subscription is activated.
                </p>

                @php
                    $user = auth()->user();
                @endphp

                @if ($user && $user->hasRole('Barbershop Admin'))
                    <p class="text-sm text-gray-700">Open your manager dashboard and select a plan to continue.</p>
                    <div>
                        <a href="{{ route('manager.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Go to Manager Dashboard
                        </a>
                    </div>
                @elseif ($user && $user->hasRole('Branch Manager'))
                    <p class="text-sm text-gray-700">Please ask your Barbershop Admin to activate a subscription plan.</p>
                    <div>
                        <a href="{{ route('manager.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Go to Manager Dashboard
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-700">Please contact your barbershop admin to activate a plan.</p>
                    <div>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Back to Dashboard
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
