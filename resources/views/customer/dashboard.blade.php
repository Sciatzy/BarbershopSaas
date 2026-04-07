@extends('customer.layouts.app')

@section('content')
@php
    $isCustomerUser = auth()->user()->hasRole('Customer');
@endphp
<div class="welcome-strip">
    <h1 class="page-title">
        Good {{ now()->format('A') === 'AM' ? 'morning' : 'afternoon' }}, {{ Str::before(auth()->user()->name, ' ') }}!
    </h1>
    <p class="page-subtitle">Here's what's happening at {{ $tenant->name ?? 'the shop' }}.</p>
    @if (! $isCustomerUser)
        <p style="color:var(--gold); font-size:13px; margin-top:-20px; margin-bottom:24px;">Preview mode: customer actions are read-only for admin users.</p>
    @endif
</div>

<div class="kpi-cards" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px;">
    <div style="background:var(--surface); border:1px solid var(--border); border-left:3px solid var(--gold); border-radius:var(--radius-lg); padding:24px;">
        <div style="color:var(--muted); font-size:13px; margin-bottom:8px;">Points Balance</div>
        <div style="font-family:var(--font-display); font-size:36px; margin-bottom:4px;">★ <span data-count="{{ $pointsBalance }}">0</span> pts</div>
        @if ($isCustomerUser)
            <a href="{{ route('customer.points') }}" style="color:var(--gold); font-size:12px; font-weight:500;">earn more →</a>
        @else
            <span style="color:var(--muted); font-size:12px;">preview only</span>
        @endif
    </div>

    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px;">
        <div style="color:var(--muted); font-size:13px; margin-bottom:8px;">Total Visits</div>
        <div style="font-family:var(--font-display); font-size:36px; margin-bottom:4px;"><span data-count="{{ $totalVisits }}">0</span> visits</div>
        <div style="color:var(--muted); font-size:12px;">since joining</div>
    </div>

    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px;">
        <div style="color:var(--muted); font-size:13px; margin-bottom:8px;">Points Earned (all time)</div>
        <div style="font-family:var(--font-display); font-size:36px; margin-bottom:4px;"><span data-count="{{ $totalEarned }}">0</span> pts</div>
        <div style="color:var(--muted); font-size:12px;">redeemed {{ $totalRedeemed ?? 0 }}</div>
    </div>

    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px;">
        <div style="color:var(--muted); font-size:13px; margin-bottom:8px;">Leaderboard Rank</div>
        <div style="font-family:var(--font-display); font-size:36px; margin-bottom:4px;">#<span data-count="{{ $rank }}">0</span></div>
        <div style="color:var(--muted); font-size:12px;">in the shop</div>
    </div>
</div>

@if($activeBooking)
<div class="active-booking" style="background:var(--gold-dim); border:1px solid var(--gold); border-radius:var(--radius-lg); padding: 20px; display:flex; align-items:center; gap:16px; margin-bottom:32px;">
    <div style="font-size:24px;">⏳</div>
    <div style="flex:1;">
        <div style="color:var(--cream); font-weight:500; font-size:16px;">You have an active booking</div>
        <div style="color:var(--muted); font-size:14px; margin-top:4px;">
            Service: {{ $activeBooking->service->name ?? 'Cut' }} ·
            Barber: {{ $activeBooking->staff->name ?? 'Any' }} ·
            Status: <span style="color:var(--gold); font-weight:500 uppercase;">{{ strtoupper($activeBooking->status) }}</span>
        </div>
    </div>
    @if ($isCustomerUser)
        <a href="{{ route('customer.bookings') }}" class="btn" style="background:transparent; border:1px solid var(--gold); color:var(--gold); padding:8px 16px; font-size:13px;">View Details →</a>
    @endif
</div>
@endif

<div class="services-section" style="margin-bottom: 40px;">
    <h2 style="font-family:var(--font-display); font-size:32px; margin-bottom:4px;">BOOK A SERVICE</h2>
    <p style="color:var(--muted); font-size:14px; margin-bottom:24px;">Choose from {{ $tenant->name ?? 'the shop' }}'s menu</p>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        @forelse($services as $index => $service)
        @if ($isCustomerUser)
        <a href="{{ route('customer.book', $service->id) }}" class="service-card" style="display:block; position:relative; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px; transition:transform 0.25s, box-shadow 0.25s, border-color 0.25s; overflow:hidden;">
        @else
        <div class="service-card" style="display:block; position:relative; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px; overflow:hidden;">
        @endif
            <div style="position:absolute; top:-10px; right:10px; font-family:var(--font-display); font-size:80px; color:rgba(255,255,255,0.03); z-index:0;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
            <div style="position:relative; z-index:1;">
                <div style="font-family:var(--font-display); font-size:24px; margin-bottom:8px; color:var(--cream);">✂ {{ $service->name }}</div>
                <div style="color:var(--muted); font-size:13px; margin-bottom:16px;">{{ $service->description ?? 'Premium grooming service.' }}</div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-family:var(--font-mono); font-size:14px;">₱{{ number_format($service->base_price ?? $service->price ?? 0, 2) }} <span style="color:var(--muted)">• {{ $service->duration_minutes ?? 30 }}m</span></div>
                    <span style="color:var(--rust); font-weight:500; font-size:13px;">{{ $isCustomerUser ? 'Book Now →' : 'Preview' }}</span>
                </div>
            </div>
        @if ($isCustomerUser)
        </a>
        @else
        </div>
        @endif
        @empty
        <div style="padding: 40px; text-align:center; background:var(--surface-2); border-radius:var(--radius); border:1px dashed var(--border); grid-column: 1 / -1; color:var(--muted);">
            No services listed yet.
        </div>
        @endforelse
    </div>
</div>

<div class="bookings-section">
    <h2 style="font-family:var(--font-display); font-size:32px; margin-bottom:20px;">RECENT BOOKINGS</h2>
    @if($recentBookings->count() > 0)
    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden;">
        <table style="width:100%; text-align:left; border-collapse:collapse; font-size:14px;">
            <thead style="background:var(--surface-3); font-family:var(--font-mono); font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px;">
                <tr>
                    <th style="padding:16px 20px;">Date</th>
                    <th style="padding:16px 20px;">Service</th>
                    <th style="padding:16px 20px;">Barber</th>
                    <th style="padding:16px 20px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentBookings as $booking)
                    @php
                        $statusColor = match ($booking->status) {
                            'queued' => 'var(--gold)',
                            'in_progress' => '#a78bfa',
                            'completed' => 'var(--green)',
                            default => 'var(--muted)',
                        };
                    @endphp
                    <tr style="border-top:1px solid var(--border);">
                        <td style="padding:16px 20px;">{{ \Carbon\Carbon::parse($booking->booked_at)->format('M d, Y') }}</td>
                        <td style="padding:16px 20px;">{{ $booking->service->name ?? 'Custom' }}</td>
                        <td style="padding:16px 20px; color:var(--muted);">{{ $booking->staff->name ?? 'Any' }}</td>
                        <td style="padding:16px 20px;">
                            <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:99px; font-size:11px; font-weight:500; background:color-mix(in srgb, {{ $statusColor }} 15%, transparent); color:{{ $statusColor }}; text-transform:uppercase;">
                                {{ $booking->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr style="border-top:1px solid var(--border);">
                        <td colspan="4" style="padding:16px 20px; color:var(--muted);">No recent bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align:center; padding:60px 20px; color:var(--muted); background:var(--surface); border-radius:var(--radius-lg); border:1px dashed var(--border);">
        <p style="margin-bottom:16px;">{{ $isCustomerUser ? 'No bookings yet. Book your first cut above!' : 'No recent tenant bookings found yet.' }}</p>
    </div>
    @endif
</div>
@endsection
