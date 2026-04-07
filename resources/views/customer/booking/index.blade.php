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
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
