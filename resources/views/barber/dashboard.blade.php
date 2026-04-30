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

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center space-x-4 transition hover:shadow-md md:col-span-3">
                    <div class="p-3 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Upcoming Appointments</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ ($upcomingAppointments ?? collect())->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800">Cash Bonus</h3>
                    <p class="text-sm text-slate-500 mt-1">Request a cash bonus using your points (tier-based).</p>
                </div>
                <div class="p-6 space-y-4">
                    @if (session('cashout_status'))
                        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                            {{ session('cashout_status') }}
                        </div>
                    @endif

                    @if (session('cashout_error'))
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                            {{ session('cashout_error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('barber.cashouts.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        @csrf
                        <div>
                            <label for="cashout_points" class="block text-sm font-medium text-slate-600">Select tier</label>
                            <select id="cashout_points" name="points" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Choose a tier</option>
                                @foreach (($cashoutTiers ?? []) as $tier)
                                    <option value="{{ $tier['points'] }}" @selected((string) old('points') === (string) $tier['points'])>
                                        {{ $tier['points'] }} pts = PHP {{ number_format((float) $tier['amount_php'], 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="cashout_notes" class="block text-sm font-medium text-slate-600">Notes (optional)</label>
                            <input id="cashout_notes" name="notes" type="text" maxlength="255" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Cash bonus request" value="{{ old('notes') }}">
                        </div>
                        <div class="md:col-span-3">
                            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Request Cash Bonus
                            </button>
                        </div>
                    </form>

                    <div class="rounded-xl border border-slate-100 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                            <h4 class="text-sm font-semibold text-slate-800">Recent Requests</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Requested</th>
                                        <th class="px-4 py-3 text-right text-xs uppercase tracking-wider">Points</th>
                                        <th class="px-4 py-3 text-right text-xs uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse (($cashoutHistory ?? collect()) as $cashout)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($cashout->created_at)->format('M d, Y') }}</td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-800">{{ (int) $cashout->points }}</td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-800">PHP {{ number_format((float) $cashout->amount_php, 2) }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold capitalize
                                                    {{ $cashout->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($cashout->status === 'approved' ? 'bg-indigo-50 text-indigo-700' : ($cashout->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700')) }}">
                                                    {{ $cashout->status }}
                                                </span>
                                                @if ($cashout->status === 'rejected' && ! empty($cashout->rejection_reason))
                                                    <p class="text-xs text-slate-500 mt-1">Reason: {{ $cashout->rejection_reason }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-slate-400">No cash bonus requests yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Weekly Schedule Panel -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col h-full">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Weekly Schedule</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                            Next 7 Days
                        </span>
                    </div>
                    <div class="p-0 flex-1">
                        <ul class="divide-y divide-slate-100">
                            @forelse (($weekSchedule ?? collect()) as $day)
                                <li class="px-6 py-4 hover:bg-slate-50 transition">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ $day['label'] }}
                                                @if ($day['is_today'])
                                                    <span class="ml-2 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700">Today</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-500">{{ $day['date']->format('M j, Y') }}</p>
                                        </div>
                                        <span class="text-xs font-medium text-slate-500">{{ $day['slots']->count() }} {{ $day['slots']->count() === 1 ? 'block' : 'blocks' }}</span>
                                    </div>

                                    @if ($day['slots']->isNotEmpty())
                                        <ul class="mt-2 space-y-1.5">
                                            @foreach ($day['slots'] as $slot)
                                                <li class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                                                    {{ \Illuminate\Support\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($slot->end_time)->format('g:i A') }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="mt-2 text-xs text-slate-400">No duty schedule.</p>
                                    @endif
                                </li>
                            @empty
                                <li class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-slate-900">No schedule configured</h3>
                                    <p class="mt-1 text-sm text-slate-500">Your branch manager has not assigned your weekly duty schedule yet.</p>
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
                                                'no_show' => 'bg-rose-100 text-rose-800',
                                                'arrived' => 'bg-emerald-100 text-emerald-800',
                                                'late' => 'bg-orange-100 text-orange-800',
                                                'in_progress' => 'bg-indigo-100 text-indigo-800',
                                                'confirmed' => 'bg-blue-100 text-blue-800',
                                                default => 'bg-amber-100 text-amber-800'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusColor }}">
                                            {{ $appointment->status }}
                                        </span>

                                        @if (($canUpdateAppointmentStatus ?? true) && in_array(strtolower((string) $appointment->status), ['queued', 'arrived', 'late', 'in_progress'], true))
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @if (strtolower((string) $appointment->status) === 'queued')
                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="arrived">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-500">
                                                            Arrived
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="late">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-orange-600 text-white hover:bg-orange-500">
                                                            Late
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-500">
                                                            Start
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}" onsubmit="return confirm('Mark this appointment as finished?');">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-600 text-white hover:bg-green-500">
                                                            Finish
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}" onsubmit="return confirm('Mark this appointment as no-show?');">
                                                        @csrf
                                                        <input type="hidden" name="status" value="no_show">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-600 text-white hover:bg-rose-500">
                                                            No-show
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (in_array(strtolower((string) $appointment->status), ['arrived', 'late'], true))
                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-600 text-white hover:bg-blue-500">
                                                            Start
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}" onsubmit="return confirm('Mark this appointment as finished?');">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-600 text-white hover:bg-green-500">
                                                            Finish
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}" onsubmit="return confirm('Mark this appointment as no-show?');">
                                                        @csrf
                                                        <input type="hidden" name="status" value="no_show">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-600 text-white hover:bg-rose-500">
                                                            No-show
                                                        </button>
                                                    </form>
                                                @endif

                                                @if (strtolower((string) $appointment->status) === 'in_progress')
                                                    <form method="POST" action="{{ route('barber.appointments.status', $appointment->id) }}" onsubmit="return confirm('Mark this appointment as finished?');">
                                                        @csrf
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-600 text-white hover:bg-green-500">
                                                            Finish
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @elseif (! ($canUpdateAppointmentStatus ?? true) && in_array(strtolower((string) $appointment->status), ['queued', 'in_progress'], true))
                                            <p class="mt-2 text-xs text-slate-400">Status updates are disabled by the shop owner.</p>
                                        @endif

                                        <div class="mt-2 space-y-1">
                                            <p class="text-xs text-slate-500">
                                                Booked: {{ \Illuminate\Support\Carbon::parse($appointment->booked_at ?? $appointment->created_at)->format('M d, Y g:i A') }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                Duration: {{ (int) ($appointment->service_duration_minutes ?? 30) }} mins
                                                @if (! is_null($appointment->total_price))
                                                    • Price: PHP {{ number_format((float) $appointment->total_price, 2) }}
                                                @endif
                                            </p>
                                            @if (! empty($appointment->arrived_at))
                                                <p class="text-xs text-slate-500">Arrived: {{ \Illuminate\Support\Carbon::parse($appointment->arrived_at)->format('M d, Y g:i A') }}</p>
                                            @endif
                                            @if (! empty($appointment->late_marked_at))
                                                <p class="text-xs text-orange-700">Late marked: {{ \Illuminate\Support\Carbon::parse($appointment->late_marked_at)->format('M d, Y g:i A') }}</p>
                                            @endif
                                            @if (! empty($appointment->no_show_marked_at))
                                                <p class="text-xs text-rose-700">No-show marked: {{ \Illuminate\Support\Carbon::parse($appointment->no_show_marked_at)->format('M d, Y g:i A') }}</p>
                                            @endif
                                            @if (! empty($appointment->notes))
                                                <p class="text-xs text-slate-500 italic">Notes: {{ $appointment->notes }}</p>
                                            @endif
                                        </div>
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

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Upcoming Appointments</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        {{ ($upcomingAppointments ?? collect())->count() }} Upcoming
                    </span>
                </div>
                <div class="p-0 flex-1">
                    <ul class="divide-y divide-slate-100">
                        @forelse (($upcomingAppointments ?? collect()) as $appointment)
                            <li class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50 transition">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $appointment->customer_name ?? 'Customer' }}</p>
                                    <p class="text-sm text-slate-500 mt-1">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_datetime)->format('D, M j \a\t g:i A') }} - {{ $appointment->service_name ?? 'Service' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Booked: {{ \Illuminate\Support\Carbon::parse($appointment->booked_at ?? $appointment->created_at)->format('M d, Y g:i A') }}</p>
                                    <p class="text-xs text-slate-500">Duration: {{ (int) ($appointment->service_duration_minutes ?? 30) }} mins @if (! is_null($appointment->total_price)) • Price: PHP {{ number_format((float) $appointment->total_price, 2) }} @endif</p>
                                    @if (! empty($appointment->arrived_at))
                                        <p class="text-xs text-slate-500">Arrived: {{ \Illuminate\Support\Carbon::parse($appointment->arrived_at)->format('M d, Y g:i A') }}</p>
                                    @endif
                                    @if (! empty($appointment->late_marked_at))
                                        <p class="text-xs text-orange-700">Late marked: {{ \Illuminate\Support\Carbon::parse($appointment->late_marked_at)->format('M d, Y g:i A') }}</p>
                                    @endif
                                    @if (! empty($appointment->notes))
                                        <p class="text-xs text-slate-500 italic">Notes: {{ $appointment->notes }}</p>
                                    @endif
                                </div>
                                <span class="mt-2 sm:mt-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize bg-amber-100 text-amber-800">
                                    {{ $appointment->status }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-10 text-center text-sm text-slate-500">No upcoming appointments assigned yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800">Previous Appointments</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                        {{ ($previousAppointments ?? collect())->count() }} Recent
                    </span>
                </div>
                <div class="p-0 flex-1">
                    <ul class="divide-y divide-slate-100">
                        @forelse (($previousAppointments ?? collect()) as $appointment)
                            <li class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50 transition">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $appointment->customer_name ?? 'Customer' }}</p>
                                    <p class="text-sm text-slate-500 mt-1">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_datetime)->format('D, M j \a\t g:i A') }} - {{ $appointment->service_name ?? 'Service' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Booked: {{ \Illuminate\Support\Carbon::parse($appointment->booked_at ?? $appointment->created_at)->format('M d, Y g:i A') }}</p>
                                    <p class="text-xs text-slate-500">Duration: {{ (int) ($appointment->service_duration_minutes ?? 30) }} mins @if (! is_null($appointment->total_price)) • Price: PHP {{ number_format((float) $appointment->total_price, 2) }} @endif</p>

                                    @if (! empty($appointment->completed_at))
                                        <p class="text-xs text-slate-500">Completed: {{ \Illuminate\Support\Carbon::parse($appointment->completed_at)->format('M d, Y g:i A') }}</p>
                                    @endif
                                    @if (! empty($appointment->no_show_marked_at))
                                        <p class="text-xs text-rose-700">No-show marked: {{ \Illuminate\Support\Carbon::parse($appointment->no_show_marked_at)->format('M d, Y g:i A') }}</p>
                                    @endif
                                    @if (! empty($appointment->notes))
                                        <p class="text-xs text-slate-500 italic">Notes: {{ $appointment->notes }}</p>
                                    @endif
                                </div>

                                @php
                                    $statusColor = match(strtolower((string) ($appointment->status ?? ''))) {
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'no_show' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-slate-100 text-slate-800'
                                    };
                                @endphp
                                <span class="mt-2 sm:mt-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusColor }}">
                                    {{ $appointment->status }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-10 text-center text-sm text-slate-500">No previous appointments yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
