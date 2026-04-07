@extends('customer.layouts.app')

@section('content')
<div style="margin-bottom:40px;">
    <h1 style="font-family:var(--font-display); font-size:clamp(32px, 4vw, 48px); margin:0;">MY BOOKINGS</h1>
    <p style="color:var(--muted); font-size:16px; margin:4px 0 0 0;">All your visits and upcoming reservations.</p>
</div>

<div style="display:flex; flex-direction:column; gap:16px;">
    @forelse($bookings ?? [] as $booking)
    <div class="booking-card" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid var(--border);">
            <div style="font-family:var(--font-mono); color:var(--muted); font-size:13px;">#{{ $booking->id }}</div>
            <div style="font-size:14px; font-weight:500; color:{{ $booking->status === 'queued' ? 'var(--gold)' : ($booking->status === 'completed' ? 'var(--green)' : 'var(--muted)') }}; text-transform:uppercase;">● {{ $booking->status }}</div>
            <div style="font-size:13px; color:var(--muted);">{{ \Carbon\Carbon::parse($booking->booked_at)->format('M d Y') }}</div>
        </div>
        
        <div style="display:flex; justify-content:space-between;">
            <div>
                <div style="font-size:16px; font-weight:500; margin-bottom:4px;">{{ $booking->service->name ?? 'Custom Service' }}</div>
                <div style="font-size:13px; color:var(--muted); margin-bottom:4px;">Barber: {{ $booking->staff->name ?? 'Any' }}</div>
                @if($booking->notes)
                <div style="font-size:13px; color:var(--muted); font-style:italic;">Notes: {{ $booking->notes }}</div>
                @endif
            </div>
            <div style="text-align:right;">
                <div style="font-family:var(--font-mono); font-size:16px; margin-bottom:4px;">₱{{ number_format($booking->total_price ?? 0, 2) }}</div>
                <div style="font-size:13px; color:var(--muted); margin-bottom:12px;">{{ $booking->service->duration_minutes ?? 30 }} min</div>
                
                @if($booking->status === 'queued')
                <form method="POST" action="{{ route('customer.bookings.cancel', $booking->id) }}" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="background:transparent; border:1px solid var(--border-strong); color:var(--muted); padding:6px 12px; border-radius:var(--radius); font-size:12px; cursor:pointer;" onmouseover="this.style.color='#ff8a6e'; this.style.borderColor='#ff8a6e';" onmouseout="this.style.color='var(--muted)'; this.style.borderColor='var(--border-strong)';">[Cancel]</button>
                </form>
                @elseif($booking->status === 'completed' && $booking->points_earned > 0)
                <div style="color:var(--green); font-size:12px; font-weight:500;">+{{ $booking->points_earned }} pts earned</div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div style="text-align:center; padding:60px 20px; background:var(--surface); border:1px dashed var(--border); border-radius:var(--radius-lg); color:var(--muted);">
        <p style="margin-bottom:16px;">No bookings yet.</p>
        <a href="{{ route('customer.services') }}" style="color:var(--gold); font-weight:500;">&rarr; Book your first service</a>
    </div>
    @endforelse
</div>
@endsection
