<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Customer Booking Flow
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
                You only see branches, barbers, and services under your assigned barbershop tenant.
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">1) Choose Branch, Service, Barber, and Date</h3>
                <form method="GET" action="{{ route('booking.index') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($singleBranch)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                            <div class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-800">
                                {{ $singleBranch->name }}
                                @if ($singleBranch->address)
                                    <span class="block text-xs text-gray-500 mt-1">{{ $singleBranch->address }}</span>
                                @endif
                            </div>
                            <input type="hidden" name="branch_id" value="{{ $singleBranch->id }}" />
                        </div>
                    @else
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                            <select name="branch_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="0">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($selectedBranchId === $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                        <select name="service_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="0">Select service</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected($selectedServiceId === $service->id)>
                                    {{ $service->name }} ({{ ucfirst($service->type) }}) - PHP {{ number_format($service->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barber</label>
                        <select name="barber_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="0">Select barber</option>
                            @foreach ($barbers as $barber)
                                <option value="{{ $barber->id }}" @selected($selectedBarberId === $barber->id)>
                                    {{ $barber->name }}
                                </option>
                            @endforeach
                        </select>
                        @if (! $singleBranch && $selectedBranchId <= 0)
                            <p class="text-xs text-gray-500 mt-1">Choose a branch first to load available barbers.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" value="{{ $selectedDate }}" class="w-full border-gray-300 rounded-md shadow-sm" />
                    </div>

                    <div class="md:col-span-2">
                        <x-primary-button>Load Time Slots</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">2) Confirm Booking</h3>

                @if ($availableSlots->isEmpty())
                    <p class="text-sm text-gray-500">Select a barber and date to view available time slots.</p>
                @else
                    <form method="POST" action="{{ route('booking.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}" />
                        <input type="hidden" name="service_id" value="{{ $selectedServiceId }}" />
                        <input type="hidden" name="barber_id" value="{{ $selectedBarberId }}" />
                        <input type="hidden" name="appointment_date" value="{{ $selectedDate }}" />

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Slot</label>
                            <select name="appointment_time" class="w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ($availableSlots as $slot)
                                    <option value="{{ $slot['value'] }}">{{ $slot['label'] }}</option>
                                @endforeach
                            </select>
                            @error('appointment_time')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 flex items-center gap-3">
                            <x-primary-button>Book Appointment</x-primary-button>
                            <p class="text-xs text-gray-500">New bookings are created with pending status.</p>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
