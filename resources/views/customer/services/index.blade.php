@extends('customer.layouts.app')

@section('content')
<div style="margin-bottom:40px;">
    <h1 style="font-family:var(--font-display); font-size:clamp(32px, 4vw, 48px); margin:0;">BOOK A SERVICE</h1>
    <p style="color:var(--muted); font-size:16px; margin:4px 0 0 0;">Pick your service and preferred barber.</p>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:24px;">
    @forelse($services as $index => $service)
    <div class="service-card" style="position:relative; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px; --i:{{ $index }}; animation: slideUp 0.5s calc(var(--i) * 60ms) both;">
        <div style="position:absolute; top:-10px; right:10px; font-family:var(--font-display); font-size:100px; color:rgba(255,255,255,0.03); z-index:0; line-height:1;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
        <div style="position:relative; z-index:1; display:flex; flex-direction:column; height:100%;">
            <div style="font-size:28px; margin-bottom:8px;">✂</div>
            <div style="font-family:var(--font-display); font-size:26px; margin-bottom:8px; color:var(--cream);">{{ $service->name }}</div>
            <div style="color:var(--muted); font-size:13px; margin-bottom:24px; flex:1;">{{ $service->description ?? 'Premium grooming service.' }}</div>
            
            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px solid var(--border); margin-bottom:20px;">
                <div style="font-family:var(--font-mono); font-size:16px; color:var(--cream);">₱{{ number_format($service->base_price ?? $service->price ?? 0, 2) }}</div>
                <div style="font-family:var(--font-mono); font-size:14px; color:var(--muted);">{{ $service->duration_minutes ?? 30 }} min</div>
            </div>

            <a href="{{ route('customer.book', $service->id) }}" style="display:block; text-align:center; background:var(--rust); color:var(--cream); width:100%; border-radius:var(--radius); padding:12px; font-weight:500; font-size:14px; font-family:var(--font-body); border:none; cursor:pointer;">
                [Book This Service]
            </a>
        </div>
    </div>
    @empty
    <div style="grid-column: 1 / -1; padding:60px 20px; text-align:center; background:var(--surface); border:1px dashed var(--border); border-radius:var(--radius-lg); color:var(--muted);">
        Services coming soon. Check back later!
    </div>
    @endforelse
</div>
@endsection
