<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Service Queue
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($bookings->isEmpty())
                    <div class="p-8 text-center text-gray-600">
                        Queue is empty. All caught up!
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Customer Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Service</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Booked At</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($bookings as $booking)
                                    @php
                                        $statusStyles = [
                                            'queued' => 'bg-amber-100 text-amber-700',
                                            'in_progress' => 'bg-blue-100 text-blue-700',
                                        ];

                                        $statusClass = $statusStyles[$booking->status] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $booking->id }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $booking->customer?->name ?? 'Unknown Customer' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $booking->service?->name ?? 'Service unavailable' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                                                {{ str_replace('_', ' ', ucfirst((string) $booking->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ optional($booking->booked_at)->format('M d, Y h:i A') ?? optional($booking->created_at)->format('M d, Y h:i A') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                @if ($booking->status === 'queued')
                                                    <form method="POST" action="{{ route('manager.queue.status', $booking) }}">
                                                        @csrf
                                                        <input type="hidden" name="_method" value="POST">
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded text-xs hover:bg-blue-500">Start</button>
                                                    </form>
                                                @endif

                                                @if ($booking->status === 'in_progress')
                                                    <form method="POST" action="{{ route('manager.queue.status', $booking) }}">
                                                        @csrf
                                                        <input type="hidden" name="_method" value="POST">
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white rounded text-xs hover:bg-emerald-500">Complete</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('manager.queue.status', $booking) }}">
                                                        @csrf
                                                        <input type="hidden" name="_method" value="POST">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="px-3 py-1.5 bg-rose-600 text-white rounded text-xs hover:bg-rose-500">Cancel</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
