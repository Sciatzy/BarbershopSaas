@extends('customer.layouts.guest')

@section('content')
<div class="auth-header">
    <h1>BECOME A MEMBER</h1>
    <p>Sign up to book your next cut, earn points, and skip the queue.</p>
</div>

<form method="POST" action="{{ route('customer.register.store') }}">
    @csrf

    <div class="form-group">
        <label for="tenant_id" class="form-label">Shop (Select Barbershop)</label>
        <select id="tenant_id" name="tenant_id" class="form-input" required autofocus>
            <option value="" disabled selected>-- Choose a Barbershop --</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>
        @error('tenant_id') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="name" class="form-label">Full Name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-input" required placeholder="e.g. Juan Dela Cruz" />
        @error('name') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" required placeholder="e.g. juan@example.com" />
        @error('email') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input id="password" type="password" name="password" class="form-input" required placeholder="Create a strong password" />
        @error('password') <span class="error-message">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" required placeholder="Repeat your password" />
    </div>

    <button type="submit" class="btn-submit">
        Create Account &rarr;
    </button>
</form>

<div class="footer-link">
    Already have an account? <a href="{{ route('login') }}">Sign In</a>
</div>
@endsection
