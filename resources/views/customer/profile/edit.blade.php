@extends('customer.layouts.app')

@section('content')
<div style="margin-bottom:40px;">
    <h1 style="font-family:var(--font-display); font-size:clamp(32px, 4vw, 48px); margin:0;">MY PROFILE</h1>
    <p style="color:var(--muted); font-size:16px; margin:4px 0 0 0;">Update your account settings.</p>
</div>

<div style="display:flex; justify-content:center; align-items:center; flex-direction:column; margin-bottom:32px;">
    <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg, var(--gold), var(--rust)); display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-size:36px; color:var(--ink); margin-bottom:16px; box-shadow:0 8px 16px rgba(0,0,0,0.2);">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
    <div style="font-size:20px; font-weight:500;">{{ $user->name }}</div>
    <div style="color:var(--muted); font-size:14px; margin-top:4px;">{{ $user->email }}</div>
</div>

<form method="POST" action="{{ route('customer.profile.update') }}" style="max-width:560px; margin:0 auto; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:32px;">
    @csrf
    @method('PUT')

    <div style="margin-bottom:20px;">
        <label for="name" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Full Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 16px; border-radius:8px; font-size:15px; box-sizing:border-box;">
        @error('name')<div style="color:#ff8a6e; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:20px;">
        <label for="email" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Email Address</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 16px; border-radius:8px; font-size:15px; box-sizing:border-box;">
        @error('email')<div style="color:#ff8a6e; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
    </div>

    <div style="margin-bottom:32px;">
        <label for="phone" style="display:block; margin-bottom:8px; font-size:13px; color:var(--muted);">Phone Number (optional)</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" style="width:100%; background:var(--surface-2); border:1px solid var(--border-strong); color:var(--cream); padding:12px 16px; border-radius:8px; font-size:15px; box-sizing:border-box;">
        @error('phone')<div style="color:#ff8a6e; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
    </div>

    <button type="submit" style="width:100%; background:var(--rust); color:var(--cream); border:none; padding:14px 24px; border-radius:8px; font-weight:500; font-size:16px; cursor:pointer; font-family:var(--font-body); transition:background 0.2s;">
        Save Changes
    </button>
</form>
@endsection
