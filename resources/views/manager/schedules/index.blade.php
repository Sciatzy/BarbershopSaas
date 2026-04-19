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
                <form method="POST" action="{{ route('manager.schedules.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
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
                                        <form method="POST" action="{{ route('manager.schedules.destroy', $schedule->id) }}" onsubmit="return confirm('Delete this schedule entry?');">
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
