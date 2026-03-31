<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Account Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-3">
                    <p class="font-medium">You are logged in, but no role dashboard was matched for this account.</p>

                    <p class="text-sm text-gray-600">Assigned roles:</p>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($roles as $role)
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                {{ $role }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-600">No roles assigned.</span>
                        @endforelse
                    </div>

                    <p class="text-sm text-gray-600">Assign one of these roles to access a functional dashboard: Platform Admin, Barbershop Admin, Branch Manager, Barber, or Customer.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
