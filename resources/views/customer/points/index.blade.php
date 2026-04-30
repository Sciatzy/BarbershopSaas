@extends('customer.layouts.app')

@section('content')
<div style="margin-bottom:40px; display:flex; align-items:baseline; gap:20px;">
    <h1 style="font-family:var(--font-display); font-size:clamp(32px, 4vw, 48px); margin:0;">YOUR POINTS</h1>
    <div style="font-family:var(--font-display); font-size:72px; color:var(--gold); line-height:1;">★ <span data-count="{{ $balance ?? 0 }}">0</span> <span style="font-size:32px;">pts</span></div>
</div>

@if (session('points_status'))
    <div style="background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.4); color:var(--cream); border-radius:var(--radius-lg); padding:14px 16px; margin-bottom:20px;">
        {{ session('points_status') }}
    </div>
@endif

@if (session('points_error'))
    <div style="background:rgba(244,63,94,0.12); border:1px solid rgba(244,63,94,0.4); color:var(--cream); border-radius:var(--radius-lg); padding:14px 16px; margin-bottom:20px;">
        {{ session('points_error') }}
    </div>
@endif

<div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:32px; margin-bottom:32px; position:relative; overflow:hidden;">
    <div style="display:flex; justify-content:space-between; align-items:center; position:relative; margin-bottom:24px;">
        <div style="position:absolute; top:20px; left:40px; right:40px; height:8px; background:var(--surface-3); border-radius:999px; z-index:0;"></div>
        <div id="points-fill" data-pct="{{ min(100, (($balance ?? 0) / 1200) * 100) }}" style="position:absolute; top:20px; left:40px; width:0%; height:8px; background:var(--gold); border-radius:999px; z-index:1; transition:width 1s cubic-bezier(0.16, 1, 0.3, 1);"></div>
        
        @foreach($milestones ?? [] as $m)
        <div style="position:relative; z-index:2; display:flex; flex-direction:column; align-items:center; width:80px; text-align:center;">
            <div style="width:40px; height:40px; border-radius:50%; background:{{ ($balance ?? 0) >= $m['points'] ? 'var(--gold)' : 'var(--surface-3)' }}; border:2px solid {!! ($balance ?? 0) >= $m['points'] ? 'var(--gold)' : 'var(--border)' !!}; display:flex; align-items:center; justify-content:center; margin-bottom:12px; color:var(--ink);">
                @if(($balance ?? 0) >= $m['points']) ✓ @else 🔒 @endif
            </div>
            <div style="font-family:var(--font-mono); font-size:12px; color:var(--muted); margin-bottom:4px;">[{{ $m['points'] }}]</div>
            <div style="font-size:12px; font-weight:500; color:{{ ($balance ?? 0) >= $m['points'] ? 'var(--cream)' : 'var(--muted)' }}; line-height:1.3;">{{ $m['reward'] }}</div>
        </div>
        @endforeach
    </div>
</div>

<div style="margin-bottom:32px;">
    <h2 style="font-family:var(--font-display); font-size:24px; margin-bottom:16px;">READY TO REDEEM?</h2>
    <div style="background:var(--gold-dim); border:1px solid var(--gold); border-radius:var(--radius-lg); padding:24px;">
        <p style="margin:0 0 12px 0; color:var(--cream); font-weight:500;">Tell your barber your points balance when you book to apply a discount!</p>
        <p style="margin:0; color:var(--gold); font-size:14px; font-family:var(--font-mono);">Your balance: ★ {{ $balance ?? 0 }} pts</p>

        <div style="margin-top:16px; display:flex; flex-wrap:wrap; gap:10px;">
            @foreach($milestones ?? [] as $m)
                @php
                    $canRedeem = ($balance ?? 0) >= ($m['points'] ?? 0);
                @endphp
                <form method="POST" action="{{ route('customer.points.redeem') }}" style="margin:0;">
                    @csrf
                    <input type="hidden" name="points" value="{{ $m['points'] }}">
                    <input type="hidden" name="reward" value="{{ $m['reward'] }}">
                    <button type="submit" {{ $canRedeem ? '' : 'disabled' }}
                        style="border-radius:999px; padding:10px 14px; border:1px solid var(--gold); font-family:var(--font-mono); font-size:12px; cursor:{{ $canRedeem ? 'pointer' : 'not-allowed' }}; opacity:{{ $canRedeem ? '1' : '0.45' }}; background:{{ $canRedeem ? 'var(--gold)' : 'transparent' }}; color:{{ $canRedeem ? 'var(--ink)' : 'var(--gold)' }};">
                        Redeem {{ $m['points'] }} pts
                    </button>
                </form>
            @endforeach
        </div>
    </div>
</div>

<div>
    <h2 style="font-family:var(--font-display); font-size:24px; margin-bottom:16px;">POINTS HISTORY</h2>
    @if(isset($ledger) && $ledger->count() > 0)
    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden;">
        <table style="width:100%; border-collapse:collapse; text-align:left;">
            <tbody>
                @foreach($ledger as $item)
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:16px; color:var(--muted); font-size:13px;">{{ \Carbon\Carbon::parse($item->created_at)->format('M d, Y') }}</td>
                    <td style="padding:16px; font-size:14px;">{{ $item->notes ?? 'Adjustment' }}</td>
                    <td style="padding:16px; text-align:right; font-family:var(--font-mono); font-size:13px; color:{{ $item->type === 'earn' ? 'var(--green)' : ($item->type === 'redeem' ? 'var(--rust)' : 'var(--muted)') }};">
                        @php
                            $pointsValue = (int) ($item->points ?? 0);
                            $absPoints = abs($pointsValue);
                            $prefix = '';

                            if ($item->type === 'earn') {
                                $prefix = '+';
                            } elseif ($item->type === 'redeem') {
                                $prefix = '-';
                            } elseif ($pointsValue > 0) {
                                $prefix = '+';
                            } elseif ($pointsValue < 0) {
                                $prefix = '-';
                            }
                        @endphp
                        {{ $prefix }}{{ $absPoints }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p style="color:var(--muted); text-align:center; padding:40px; border:1px dashed var(--border); border-radius:var(--radius-lg);">No points history yet.</p>
    @endif
</div>
@endsection
