<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Bookings
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
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

            <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-5 py-4">
                <p class="text-sm text-indigo-700">You have {{ (int) (auth()->user()->points_balance ?? 0) }} pts</p>
                <p class="text-xs text-indigo-600 mt-1">Earn 1 point per ₱50 spent</p>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('booking.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                    Book a Service
                </a>
            </div>

            @if ($bookings->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    No bookings yet. Make your first booking!
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($bookings as $booking)
                        @php
                            $statusStyles = [
                                'queued' => 'bg-amber-100 text-amber-700',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'cancelled' => 'bg-rose-100 text-rose-700',
                            ];

                            $status = (string) ($booking->status ?? 'queued');
                            $statusClass = $statusStyles[$status] ?? 'bg-gray-100 text-gray-700';
                            $earnedPoints = (int) floor(((float) ($booking->total_price ?? 0)) / 50);
                        @endphp

                        <div class="bg-white shadow-sm sm:rounded-lg p-5">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Booking #{{ $booking->id }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ $booking->service?->name ?? 'Service unavailable' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Barber: {{ $booking->staff?->name ?? 'Any' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Schedule: {{ optional($booking->appointment_datetime)->format('M d, Y h:i A') ?? '-' }}</p>
                                </div>

                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                    {{ str_replace('_', ' ', ucfirst($status)) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4 text-sm text-gray-600">
                                <p>Date: {{ optional($booking->booked_at)->format('M d, Y h:i A') ?? optional($booking->created_at)->format('M d, Y h:i A') }}</p>
                                <p>Total Price: ₱{{ number_format((float) ($booking->total_price ?? 0), 2) }}</p>
                                <p>Points Earned: {{ $booking->status === 'completed' ? $earnedPoints : 0 }}</p>
                            </div>

                            @if (in_array($status, ['queued', 'confirmed'], true))
                                <div class="mt-4 border-t border-gray-100 pt-4 space-y-3">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('booking.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-md border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-50">
                                                Cancel Booking
                                            </button>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('booking.reschedule', $booking) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">New Date</label>
                                            <input type="date" name="appointment_date" required class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">New Time</label>
                                            <input type="time" name="appointment_time" required class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        </div>
                                        <div>
                                            <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">
                                                Reschedule
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif

                            @if ($status === 'completed')
                                <form method="POST" action="{{ route('booking.feedback', $booking) }}" class="mt-4 border-t border-gray-100 pt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Rating (1-5)</label>
                                        <select name="customer_rating" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                            <option value="">No rating</option>
                                            @for ($rating = 5; $rating >= 1; $rating--)
                                                <option value="{{ $rating }}" @selected((int) ($booking->customer_rating ?? 0) === $rating)>{{ $rating }} star{{ $rating > 1 ? 's' : '' }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs text-gray-500 mb-1">Feedback</label>
                                        <textarea name="customer_feedback" rows="2" maxlength="500" class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Share your experience...">{{ old('customer_feedback', $booking->customer_feedback) }}</textarea>
                                    </div>
                                    <div class="md:col-span-3">
                                        <button type="submit" class="inline-flex items-center px-3 py-2 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500">
                                            Submit Feedback
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
