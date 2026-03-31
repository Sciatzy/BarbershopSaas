<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Barber Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total Points</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalPoints }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Today Schedule Blocks</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $scheduleToday->count() }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Today Appointments</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $appointmentsToday->count() }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Daily Schedule</h3>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($scheduleToday as $slot)
                            <li class="px-6 py-4 text-sm text-gray-700">
                                {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }} -
                                {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('g:i A') }}
                            </li>
                        @empty
                            <li class="px-6 py-6 text-sm text-gray-500">No schedule configured for today.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Today Appointments</h3>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse ($appointmentsToday as $appointment)
                            <li class="px-6 py-4 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_datetime)->format('g:i A') }} - {{ $appointment->customer_name ?? 'Customer' }}</div>
                                <div class="text-gray-600">{{ $appointment->service_name ?? 'Service' }} | <span class="capitalize">{{ $appointment->status }}</span></div>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-sm text-gray-500">No appointments for today.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
