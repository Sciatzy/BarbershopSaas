@extends('customer.layouts.app')

@section('content')
<div style="margin-bottom:40px;">
    <h1 style="font-family:var(--font-display); font-size:clamp(32px, 4vw, 48px); margin:0;">BOOK A SERVICE</h1>
    <p style="color:var(--muted); font-size:16px; margin:4px 0 0 0;">Choose branch, service, barber, date, and your preferred time slot.</p>
</div>

<form method="GET" action="{{ route('customer.bookings.create') }}" id="availability-form" style="max-width:760px; margin:0 auto 16px auto; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:16px; display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px; align-items:end;">
    <div>
        <label for="availability_branch_id" style="display:block; margin-bottom:6px; font-size:12px; color:var(--muted);">Branch</label>
        <select id="availability_branch_id" name="branch_id" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:10px 12px; border-radius:8px; font-size:14px; box-sizing:border-box;">
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) ($selectedBranchId ?? 0) === (string) $branch->id)>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="availability_service_id" style="display:block; margin-bottom:6px; font-size:12px; color:var(--muted);">Service</label>
        <select id="availability_service_id" name="service_id" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:10px 12px; border-radius:8px; font-size:14px; box-sizing:border-box;">
            <option value="">Select service</option>
            @foreach ($services as $service)
                <option value="{{ $service->id }}" @selected((string) ($selectedServiceId ?? 0) === (string) $service->id)>
                    {{ $service->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="availability_staff_id" style="display:block; margin-bottom:6px; font-size:12px; color:var(--muted);">Barber</label>
        <select id="availability_staff_id" name="staff_id" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:10px 12px; border-radius:8px; font-size:14px; box-sizing:border-box;">
            <option value="">Select barber</option>
            @foreach ($barbers as $barber)
                <option value="{{ $barber->id }}" @selected((string) ($selectedBarberId ?? 0) === (string) $barber->id)>
                    {{ $barber->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="availability_date" style="display:block; margin-bottom:6px; font-size:12px; color:var(--muted);">Date</label>
        <input id="availability_date" name="date" type="date" min="{{ now()->toDateString() }}" value="{{ old('appointment_date', $selectedDate ?? now()->toDateString()) }}" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:10px 12px; border-radius:8px; font-size:14px; box-sizing:border-box;">
    </div>

    <button type="submit" style="background:var(--gold); color:#111; border:none; padding:10px 14px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; font-family:var(--font-body);">
        Check Available Times
    </button>
</form>

<form method="POST" action="{{ route('customer.book.store') }}" id="booking-form" style="max-width:760px; margin:0 auto; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:28px; display:flex; flex-direction:column; gap:20px;">
    @csrf

    @if ($errors->any())
        <div style="background:color-mix(in srgb, var(--rust) 20%, transparent); border:1px solid var(--rust); border-radius:var(--radius); padding:12px 14px; color:#ffd7cf; font-size:13px;">
            <div style="font-weight:600; margin-bottom:6px;">Please fix the following:</div>
            <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:4px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid; grid-template-columns:1fr; gap:16px;">
        <div>
            <label for="branch_id" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Branch</label>
            <select id="branch_id" name="branch_id" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 14px; border-radius:8px; font-size:15px; box-sizing:border-box;">
                <option value="">Select a branch</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', (string) ($selectedBranchId ?? 0)) === (string) $branch->id)>
                        {{ $branch->name }}{{ $branch->address ? ' - '.$branch->address : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="service_id" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Service</label>
            <select id="service_id" name="service_id" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 14px; border-radius:8px; font-size:15px; box-sizing:border-box;">
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
        </div>

        <div>
            <label for="staff_id" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Preferred Barber</label>
            <select id="staff_id" name="staff_id" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 14px; border-radius:8px; font-size:15px; box-sizing:border-box;">
                <option value="">Select barber</option>
                @foreach ($barbers as $barber)
                    <option value="{{ $barber->id }}" data-branch-id="{{ $barber->branch_id ?? '' }}" @selected((string) old('staff_id', (string) ($selectedBarberId ?? 0)) === (string) $barber->id)>
                        {{ $barber->name }}
                    </option>
                @endforeach
            </select>
            <p style="color:var(--muted); font-size:12px; margin:8px 0 0 0;">Barbers are filtered by selected branch.</p>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div>
                <label for="appointment_date" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Appointment Date</label>
                <input id="appointment_date" name="appointment_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('appointment_date', $selectedDate ?? now()->toDateString()) }}" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 14px; border-radius:8px; font-size:15px; box-sizing:border-box;">
            </div>
            <div>
                <label for="appointment_time" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Available Time Slot</label>
                <select id="appointment_time" name="appointment_time" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 14px; border-radius:8px; font-size:15px; box-sizing:border-box;">
                    <option value="">Select available time</option>
                    @foreach (($availableSlots ?? collect()) as $slot)
                        <option value="{{ $slot['value'] }}" @selected((string) old('appointment_time') === (string) $slot['value'])>{{ $slot['label'] }}</option>
                    @endforeach
                </select>
                @if (($availableSlots ?? collect())->isEmpty())
                    <p style="color:var(--muted); font-size:12px; margin:8px 0 0 0;">No slots yet. Choose branch, service, barber, and date, then click Check Available Times.</p>
                @endif
            </div>
        </div>

        <div>
            <label for="notes" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="4" maxlength="300" placeholder="Tell us your preferred style..." style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 14px; border-radius:8px; font-size:15px; box-sizing:border-box; resize:vertical; font-family:var(--font-body);">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
        <a href="{{ route('customer.bookings') }}" style="color:var(--muted); font-size:14px;">&larr; Back to bookings</a>
        <button type="submit" id="reserve-button" class="tenant-btn" style="background:var(--rust); color:var(--cream); border:none; padding:12px 18px; border-radius:8px; font-weight:500; font-size:14px; cursor:pointer; font-family:var(--font-body);">
            Reserve My Spot
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('booking-form');
        const button = document.getElementById('reserve-button');
        const branchSelect = document.getElementById('branch_id');
        const barberSelect = document.getElementById('staff_id');
        const availabilityForm = document.getElementById('availability-form');
        const availabilityBranch = document.getElementById('availability_branch_id');
        const availabilityService = document.getElementById('availability_service_id');
        const availabilityBarber = document.getElementById('availability_staff_id');
        const availabilityDate = document.getElementById('availability_date');

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

        if (availabilityForm && availabilityBranch && availabilityService && availabilityBarber && availabilityDate) {
            const syncAndRefresh = function () {
                availabilityForm.submit();
            };

            availabilityBranch.addEventListener('change', syncAndRefresh);
            availabilityService.addEventListener('change', syncAndRefresh);
            availabilityBarber.addEventListener('change', syncAndRefresh);
            availabilityDate.addEventListener('change', syncAndRefresh);
        }

        if (form && button) {
            form.addEventListener('submit', function () {
                button.disabled = true;
                button.textContent = 'Reserving...';
                button.style.opacity = '0.65';
                button.style.cursor = 'not-allowed';
            });
        }
    });
</script>
@endsection
