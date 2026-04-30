<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Branch Schedule Management</h2>
                <p class="text-sm text-slate-500 mt-1">Set barber availability for your assigned branch.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('billing_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('billing_status') }}
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

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Add or Update Schedule</h3>
                <form method="POST" action="{{ route('manager.schedules.store', [], false) }}" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                    @csrf

                    <select name="barber_id" class="rounded-md border-slate-300" required>
                        <option value="">Select barber</option>
                        @foreach ($barbers as $barber)
                            <option value="{{ $barber->id }}" @selected((string) old('barber_id') === (string) $barber->id)>{{ $barber->name }}</option>
                        @endforeach
                    </select>

                    <select name="day_of_week" class="rounded-md border-slate-300" required>
                        <option value="">Select day</option>
                        @foreach ($weekdayLabels as $dayValue => $dayLabel)
                            <option value="{{ $dayValue }}" @selected((string) old('day_of_week') === (string) $dayValue)>{{ $dayLabel }}</option>
                        @endforeach
                    </select>

                    <input type="time" name="start_time" value="{{ old('start_time') }}" class="rounded-md border-slate-300" required>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" class="rounded-md border-slate-300" required>

                    <label class="inline-flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700">
                        <input type="hidden" name="is_working" value="0">
                        <input type="checkbox" name="is_working" value="1" @checked(old('is_working', '1') === '1') class="rounded border-slate-300">
                        Working
                    </label>

                    <button type="submit" class="rounded-md bg-amber-600 hover:bg-amber-500 text-white font-semibold px-4 py-2">Save Schedule</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Add Date Override</h3>
                <p class="text-sm text-slate-500 mt-1">Override weekly schedules for specific future dates, like leave days or one-time custom shifts.</p>

                <form method="POST" action="{{ route('manager.schedules.overrides.store', [], false) }}" class="mt-4 grid grid-cols-1 md:grid-cols-8 gap-3">
                    @csrf

                    <select name="override_barber_id" class="rounded-md border-slate-300 md:col-span-2" required>
                        <option value="">Select barber</option>
                        @foreach ($barbers as $barber)
                            <option value="{{ $barber->id }}" @selected((string) old('override_barber_id') === (string) $barber->id)>{{ $barber->name }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="schedule_date" value="{{ old('schedule_date') }}" min="{{ now()->toDateString() }}" class="rounded-md border-slate-300 md:col-span-2" required>

                    <select name="override_type" class="rounded-md border-slate-300" required>
                        <option value="off" @selected(old('override_type', 'off') === 'off')>Off Day</option>
                        <option value="custom" @selected(old('override_type') === 'custom')>Custom Hours</option>
                    </select>

                    <input type="time" name="override_start_time" value="{{ old('override_start_time') }}" class="rounded-md border-slate-300" placeholder="Start">
                    <input type="time" name="override_end_time" value="{{ old('override_end_time') }}" class="rounded-md border-slate-300" placeholder="End">

                    <button type="submit" class="rounded-md bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-2">Save Override</button>

                    <input type="text" name="override_notes" value="{{ old('override_notes') }}" maxlength="255" class="rounded-md border-slate-300 md:col-span-8" placeholder="Notes (optional)">
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Emergency Absence Workflow</h3>
                <p class="text-sm text-slate-500 mt-1">Use this when a barber can no longer report for duty on a booked date. It sets an off-day and lets you reassign affected queued bookings.</p>

                <form method="POST" action="{{ route('manager.schedules.emergency.store', [], false) }}" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                    @csrf

                    <select name="emergency_barber_id" class="rounded-md border-slate-300" required>
                        <option value="">Select barber</option>
                        @foreach ($barbers as $barber)
                            <option value="{{ $barber->id }}" @selected((string) old('emergency_barber_id', (string) ($selectedEmergencyBarberId ?? 0)) === (string) $barber->id)>{{ $barber->name }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="emergency_date" value="{{ old('emergency_date', $selectedEmergencyDate ?? '') }}" min="{{ now()->toDateString() }}" class="rounded-md border-slate-300" required>

                    <input type="text" name="emergency_reason" value="{{ old('emergency_reason') }}" maxlength="255" class="rounded-md border-slate-300 md:col-span-2" placeholder="Reason (optional)">

                    <button type="submit" class="rounded-md bg-rose-600 hover:bg-rose-500 text-white font-semibold px-4 py-2">Set Emergency Off-Day</button>
                </form>

                @if (($selectedEmergencyBarberId ?? 0) > 0 && ($selectedEmergencyDate ?? '') !== '')
                    <div class="mt-5 border border-slate-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 text-sm font-semibold text-slate-800">
                            Impacted Queued Bookings for {{ \Illuminate\Support\Carbon::parse($selectedEmergencyDate)->format('Y-m-d') }}
                        </div>

                        @if (($impactedBookings ?? collect())->isNotEmpty())
                            <div class="px-4 py-3 border-b border-slate-200 bg-white">
                                <form method="POST" action="{{ route('manager.schedules.emergency.request-all', [], false) }}" class="flex flex-col md:flex-row md:items-center gap-2" onsubmit="return confirm('Send customer decision requests for all impacted queued bookings?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="emergency_barber_id" value="{{ $selectedEmergencyBarberId }}">
                                    <input type="hidden" name="emergency_date" value="{{ $selectedEmergencyDate }}">
                                    <input type="text" name="emergency_reason" maxlength="255" class="rounded-md border-slate-300 text-xs" placeholder="Reason (optional)">
                                    <select name="proposed_replacement_barber_id" class="rounded-md border-slate-300 text-xs">
                                        <option value="">No proposed replacement</option>
                                        @foreach (($replacementBarbers ?? collect()) as $replacement)
                                            <option value="{{ $replacement->id }}">{{ $replacement->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-md bg-indigo-700 hover:bg-indigo-600 text-white px-3 py-1.5 text-xs font-semibold">Request Customer Decisions (All)</button>
                                </form>
                            </div>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Booking #</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Customer</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Service</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Decision Request</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse (($impactedBookings ?? collect()) as $booking)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-800">{{ $booking->id }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ optional($booking->appointment_datetime)->format('g:i A') }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $booking->customer?->name ?? 'Customer' }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $booking->service?->name ?? 'Service' }}</td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <form method="POST" action="{{ route('manager.schedules.emergency.request', $booking->id, false) }}" class="flex gap-2 items-center">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="emergency_barber_id" value="{{ $selectedEmergencyBarberId }}">
                                                    <input type="hidden" name="emergency_date" value="{{ $selectedEmergencyDate }}">
                                                    <select name="proposed_replacement_barber_id" class="rounded-md border-slate-300 text-xs">
                                                        <option value="">No proposed replacement</option>
                                                        @foreach (($replacementBarbers ?? collect()) as $replacement)
                                                            <option value="{{ $replacement->id }}">{{ $replacement->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" name="emergency_reason" maxlength="255" class="rounded-md border-slate-300 text-xs" placeholder="Reason (optional)">
                                                    <button type="submit" class="rounded-md bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 text-xs font-semibold">Request Decision</button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-xs">
                                                @if ($booking->requires_customer_decision)
                                                    Waiting for customer response
                                                @else
                                                    Queued only
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No queued bookings are impacted for this barber/date.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800">Upcoming Date Overrides</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Barber</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Hours</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse (($overrides ?? collect()) as $override)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $override->barber_name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($override->schedule_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $override->is_working ? 'Custom Hours' : 'Off Day' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($override->is_working && $override->start_time && $override->end_time)
                                            {{ \Illuminate\Support\Carbon::parse($override->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($override->end_time)->format('g:i A') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $override->notes ?: '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <form method="POST" action="{{ route('manager.schedules.overrides.destroy', $override->id, false) }}" onsubmit="return confirm('Delete this date override?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md bg-rose-600 hover:bg-rose-500 text-white px-3 py-1.5 text-xs font-semibold">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No upcoming date overrides.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800">Current Branch Schedules</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Barber</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Day</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Start</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">End</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Working</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($schedules as $schedule)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $schedule->barber_name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $weekdayLabels[(int) $schedule->day_of_week] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('g:i A') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('g:i A') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $schedule->is_working ? 'Yes' : 'No' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <form method="POST" action="{{ route('manager.schedules.destroy', $schedule->id, false) }}" onsubmit="return confirm('Delete this schedule entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md bg-rose-600 hover:bg-rose-500 text-white px-3 py-1.5 text-xs font-semibold">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">No schedules configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
