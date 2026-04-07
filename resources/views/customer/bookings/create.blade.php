@extends('customer.layouts.app')

@section('content')
<div style="margin-bottom:40px; max-width:560px; margin-left:auto; margin-right:auto;">
    <a href="{{ route('customer.services') }}" style="color:var(--muted); font-size:14px; text-decoration:none; display:inline-block; margin-bottom:24px;">&larr; Back to services</a>
</div>

<form method="POST" action="{{ route('customer.book.store') }}" style="max-width:560px; margin:0 auto; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:32px;">
    @csrf
    
    <div style="margin-bottom:32px; text-align:center; padding-bottom:24px; border-bottom:1px solid var(--border);">
        <div style="font-family:var(--font-display); font-size:32px; color:var(--cream); line-height:1; margin-bottom:8px;">{{ $service->name }}</div>
        <div style="font-family:var(--font-mono); font-size:16px; color:var(--gold);">
            ₱{{ number_format($service->base_price ?? $service->price ?? 0, 2) }} <span style="color:var(--muted)">•</span> {{ $service->duration_minutes ?? 30 }} min
        </div>
    </div>
    
    <input type="hidden" name="service_id" value="{{ $service->id }}">

    <div style="margin-bottom:24px;">
        <label for="staff_id" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Preferred Barber</label>
        <select id="staff_id" name="staff_id" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 16px; border-radius:8px; font-size:15px; box-sizing:border-box;">
            <option value="">Any available barber</option>
            @foreach($barbers ?? [] as $barber)
            <option value="{{ $barber->id }}" {{ old('staff_id') == $barber->id ? 'selected' : '' }}>{{ $barber->name }}</option>
            @endforeach
        </select>
        @error('staff_id')<div style="color:#ff8a6e; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:32px;">
        <label for="notes" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Notes (optional)</label>
        <textarea id="notes" name="notes" rows="3" placeholder="e.g. Low fade, keep top long" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 16px; border-radius:8px; font-size:15px; box-sizing:border-box; resize:vertical; font-family:var(--font-body);">{{ old('notes') }}</textarea>
        @error('notes')<div style="color:#ff8a6e; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="btn-submit" style="width:100%; background:var(--rust); color:var(--cream); border:none; padding:14px 24px; border-radius:8px; font-weight:500; font-size:16px; cursor:pointer; font-family:var(--font-body); transition:background 0.2s;">
        Confirm Reservation
    </button>
    <div style="text-align:center; color:var(--muted); font-size:12px; margin-top:16px;">
        No account changes · Free to cancel
    </div>
</form>
@endsection
