<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-slate-800 leading-tight">
                {{ __('Barber Dashboard') }}
            </h2>
            <div class="text-sm text-slate-500 font-medium">
                {{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Points Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center space-x-4 transition hover:shadow-md">
                    <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Points</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $totalPoints }}</p>
                    </div>
                </div>

                <!-- Schedule Blocks Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center space-x-4 transition hover:shadow-md">
                    <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Today's Schedule</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $scheduleToday->count() }} <span class="text-base font-normal text-slate-500">blocks</span></p>
                    </div>
                </div>

                <!-- Appointments Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center space-x-4 transition hover:shadow-md">
                    <div class="p-3 rounded-lg bg-sky-50 text-sky-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Today's Appointments</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $appointmentsToday->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Daily Schedule Panel -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col h-full">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Daily Schedule</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                            {{ $scheduleToday->count() }} Blocks
                        </span>
                    </div>
                    <div class="p-0 flex-1">
                        <ul class="divide-y divide-slate-100">
                            @forelse ($scheduleToday as $slot)
                                <li class="px-6 py-4 flex items-center space-x-4 hover:bg-slate-50 transition">
                                    <div class="flex-shrink-0 bg-blue-50 text-blue-600 rounded-full p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('g:i A') }}
                                        </p>
                                        <p class="text-sm text-slate-500">Available Block</p>
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-slate-900">No schedule blocks</h3>
                                    <p class="mt-1 text-sm text-slate-500">You do not have any schedule configured for today.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Today Appointments Panel -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col h-full">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Today's Appointments</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $appointmentsToday->count() }} Total
                        </span>
                    </div>
                    <div class="p-0 flex-1">
                        <ul class="divide-y divide-slate-100">
                            @forelse ($appointmentsToday as $appointment)
                                <li class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50 transition">
                                    <div class="flex items-center space-x-4 mb-3 sm:mb-0">
                                        <div class="flex-shrink-0 bg-slate-100 text-slate-600 rounded-full p-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ $appointment->customer_name ?? 'Customer' }}
                                            </p>
                                            <div class="flex items-center text-sm text-slate-500 mt-1">
                                                <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ \Illuminate\Support\Carbon::parse($appointment->appointment_datetime)->format('g:i A') }} - {{ $appointment->service_name ?? 'Service' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        @php
                                            $statusColor = match(strtolower($appointment->status)) {
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'confirmed' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-amber-100 text-amber-800'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusColor }}">
                                            {{ $appointment->status }}
                                        </span>

                                        @if (($canUpdateAppointmentStatus ?? true) && in_array(strtolower((string) $appointment->status), ['queued', 'in_progress'], true))
                                            <div class="mt-2 flex gap-2">
                                                @if (strtolower((string) $appointment->status) === 'queued')
                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-500">
                                                            Start
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (strtolower((string) $appointment->status) === 'in_progress')
                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}" onsubmit="return confirm('Mark this appointment as completed?');">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-600 text-white hover:bg-green-500">
                                                            Complete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @elseif (! ($canUpdateAppointmentStatus ?? true) && in_array(strtolower((string) $appointment->status), ['queued', 'in_progress'], true))
                                            <p class="mt-2 text-xs text-slate-400">Status updates are disabled by the shop owner.</p>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-slate-900">No appointments</h3>
                                    <p class="mt-1 text-sm text-slate-500">You don't have any appointments scheduled for today.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
