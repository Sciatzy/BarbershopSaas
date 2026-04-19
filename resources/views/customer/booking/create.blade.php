<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Book a Service
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('booking.store') }}" id="booking-form" class="space-y-5">
                    @csrf

                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                        <select id="branch_id" name="branch_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select a branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                                    {{ $branch->name }}{{ $branch->address ? ' - '.$branch->address : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="service_id" class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                        <select id="service_id" name="service_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select a service</option>
                            @foreach ($services as $service)
                                @php
                                    $duration = (int) ($service->duration_min ?? $service->duration_minutes ?? 0);
                                    $price = (float) ($service->base_price ?? $service->price ?? 0);
                                @endphp
                                <option value="{{ $service->id }}" @selected((string) old('service_id', (string) ($selectedServiceId ?? '')) === (string) $service->id)>
                                    {{ $service->name }} - {{ $duration }} min - ₱{{ number_format($price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('service_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="staff_id" class="block text-sm font-medium text-gray-700 mb-1">Preferred Barber</label>
                        <select id="staff_id" name="staff_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Any available barber</option>
                            @foreach ($barbers as $barber)
                                <option value="{{ $barber->id }}" data-branch-id="{{ $barber->branch_id ?? '' }}" @selected((string) old('staff_id') === (string) $barber->id)>
                                    {{ $barber->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Barbers are filtered by selected branch.</p>
                        @error('staff_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="300" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Tell us your preferred style...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" id="reserve-button" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md text-sm hover:bg-gray-800">
                            Reserve My Spot
                        </button>
                        <a href="{{ route('booking.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Back to bookings</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('booking-form');
            const button = document.getElementById('reserve-button');
            const branchSelect = document.getElementById('branch_id');
            const barberSelect = document.getElementById('staff_id');

            if (!form || !button) {
                return;
            }

            function filterBarbersByBranch() {
                if (!branchSelect || !barberSelect) {
                    return;
                }

                const selectedBranchId = branchSelect.value;

                Array.from(barberSelect.options).forEach(function (option, index) {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    const optionBranchId = option.getAttribute('data-branch-id');
                    const matches = selectedBranchId !== '' && optionBranchId === selectedBranchId;
                    option.hidden = !matches;

                    if (!matches && option.selected) {
                        barberSelect.value = '';
                    }
                });
            }

            if (branchSelect) {
                branchSelect.addEventListener('change', filterBarbersByBranch);
                filterBarbersByBranch();
            }

            form.addEventListener('submit', function () {
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
                button.textContent = 'Reserving...';
            });
        });
    </script>
</x-app-layout>
