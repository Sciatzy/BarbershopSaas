<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Manager Dashboard</h2>
                <p class="text-sm text-slate-500 mt-1">Tenant subscription and operations overview.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('manager.services.index') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Manage Services & Pricing
                </a>
                <a href="{{ route('customer.dashboard') }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    View Customer Dashboard &rarr;
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
    @if(session('auto_checkout_plan') && $tenant)
        @php
            $autoPlan = session('auto_checkout_plan');
            $autoPlanRoute = collect($planOptions)->firstWhere('tier', $autoPlan)['checkout_route'] ?? null;
        @endphp
        @if($autoPlanRoute)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="bg-white p-8 rounded-2xl shadow-xl flex flex-col items-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-amber-500 border-t-transparent mb-4"></div>
                    <p class="text-slate-800 font-semibold text-lg">Redirecting to Checkout...</p>
                    <p class="text-slate-500 text-sm mt-2">Please wait while we prepare your {{ ucfirst($autoPlan) }} plan payment.</p>
                    <form method="POST" action="{{ route($autoPlanRoute, ['tenant' => $tenant->id], false) }}" id="autoCheckoutForm" class="hidden">
                        @csrf
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            setTimeout(function() {
                                document.getElementById('autoCheckoutForm').submit();
                            }, 1500);
                        });
                    </script>
                </div>
            </div>
            @php session()->forget('auto_checkout_plan'); @endphp
        @endif
    @endif

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('plan_required'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('plan_required') }}
                </div>
            @endif

            @if (session('billing_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('billing_status') }}
                </div>
            @endif

            @if (session('billing_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('billing_error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $planAvailedAt = $subscription?->created_at;
                $planEndsAt = $subscription?->ends_at;
                $subscriptionStatus = $subscription?->stripe_status;
                $shopDomain = $tenant?->primary_domain;
                $resolvedDomainSuffix = $domainSuffix ?? 'localhost:8000';
                $resolvedPreferredDomain = $preferredDomain ?? '';
                $shopDomainUrl = $shopDomain
                    ? ($domainPreviewUrl ?? ((str_starts_with($shopDomain, 'http://') || str_starts_with($shopDomain, 'https://')) ? $shopDomain : 'http://'.$shopDomain))
                    : null;
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Subscription & Details</h3>
                        <p class="text-sm text-slate-500 mt-1">Manage your shop, domain, and billing status.</p>
                    </div>
                    @if ($shopDomainUrl)
                        <a href="{{ $shopDomainUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition-colors border border-indigo-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            Visit Domain
                        </a>
                    @endif
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Profile/Account -->
                        <div class="space-y-3">
                            <h4 class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-2">Shop Profile</h4>
                            <div>
                                <p class="text-xs text-slate-500">Shop Name</p>
                                <p class="text-sm font-medium text-slate-900">{{ $tenant?->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Owner</p>
                                <p class="text-sm font-medium text-slate-900">{{ $tenant?->owner?->name ?? auth()->user()->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Email</p>
                                <p class="text-sm font-medium text-slate-900 truncate" title="{{ $tenant?->owner?->email ?? auth()->user()->email }}">{{ $tenant?->owner?->email ?? auth()->user()->email }}</p>
                            </div>
                        </div>

                        <!-- Domain Settings -->
                        <div class="space-y-3">
                            <h4 class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-2">Custom Domain</h4>
                            <div>
                                <p class="text-xs text-slate-500">Current Domain</p>
                                @if ($shopDomain)
                                    <p class="text-sm font-medium text-indigo-600 truncate">{{ $shopDomain }}</p>
                                @else
                                    <p class="text-sm font-medium text-slate-400">Not configured</p>
                                @endif
                            </div>
                            @php
                                $initialPreferredDomain = old('preferred_domain', $resolvedPreferredDomain);
                                $initialDomainPreview = 'http://'.($initialPreferredDomain !== '' ? $initialPreferredDomain : 'myshop').'.'.$resolvedDomainSuffix;
                            @endphp
                            <form method="POST" action="{{ route('manager.domain.update') }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <label for="preferred_domain" class="block text-xs text-slate-500">Preferred Domain Name</label>
                                <div class="flex items-stretch rounded-md border border-slate-200 overflow-hidden">
                                    <input
                                        id="preferred_domain"
                                        name="preferred_domain"
                                        type="text"
                                        value="{{ $initialPreferredDomain }}"
                                        placeholder="myshop"
                                        class="w-full border-0 text-sm text-slate-800 focus:ring-0"
                                        oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9-]/g,'');const preview='http://' + (this.value || 'myshop') + '.{{ $resolvedDomainSuffix }}';document.getElementById('domain-preview-value').textContent=preview;document.getElementById('domain-preview-link').setAttribute('href', preview);"
                                        required
                                    >
                                    <span class="px-3 py-2 bg-slate-50 text-xs text-slate-500 border-l border-slate-200">.{{ $resolvedDomainSuffix }}</span>
                                </div>
                                <p class="text-xs text-slate-500">
                                    Full URL preview:
                                    <a id="domain-preview-link" href="{{ $initialDomainPreview }}" target="_blank" rel="noopener" class="font-medium text-indigo-600 hover:text-indigo-700 hover:underline">
                                        <span id="domain-preview-value">{{ $initialDomainPreview }}</span>
                                    </a>
                                </p>
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-md bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                                    Save Domain
                                </button>
                            </form>
                            <div>
                                <p class="text-xs text-slate-500">Tenant Access</p>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium {{ $tenant?->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $tenant?->status === 'active' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        {{ ucfirst($tenant?->status ?? 'Pending') }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Member Since</p>
                                <p class="text-sm font-medium text-slate-900">{{ optional($tenant?->created_at)->format('M d, Y') ?? '-' }}</p>
                            </div>
                        </div>

                        <!-- Billing Status -->
                        <div class="space-y-3">
                            <h4 class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-2">Billing Information</h4>
                            <div>
                                <p class="text-xs text-slate-500">Current Plan</p>
                                <p class="text-sm font-medium text-slate-900 capitalize">{{ $tenant?->plan_tier ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Status</p>
                                <p class="text-sm font-medium capitalize {{ ($subscriptionStatus === 'active') ? 'text-emerald-600' : (($subscriptionStatus === 'canceled') ? 'text-red-500' : 'text-amber-600') }}">
                                    {{ $subscriptionStatus ?? 'Not subscribed' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Plan Duration</p>
                                <p class="text-sm font-medium text-slate-900">
                                    {{ $planAvailedAt ? $planAvailedAt->format('M d, Y') : '-' }} &rarr;
                                    @if ($planEndsAt)
                                        {{ $planEndsAt->format('M d, Y') }}
                                    @elseif ($subscriptionStatus)
                                        Auto-renew
                                    @else
                                        -
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @if (! $hasActivePlan)
                    <div class="px-6 pb-6">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-800">No active subscription</p>
                            @if ($mustContactAdminForReactivation ?? false)
                                <p class="text-sm text-amber-700 mt-1">Your tenant currently has a subscription but access is {{ $tenant?->status }}. Please contact platform admin to reactivate your account.</p>
                            @else
                                <p class="text-sm text-amber-700 mt-1">Select and activate a plan to continue using barber and customer booking features.</p>
                            @endif

                            @if (! ($mustContactAdminForReactivation ?? false) && $canManageBilling && $tenant)
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                                    @foreach ($planOptions as $plan)
                                        <div class="rounded-lg border border-amber-300 bg-white px-4 py-3">
                                            <span class="block text-sm font-semibold text-slate-800">{{ $plan['label'] }}</span>
                                            <span class="block text-xs text-slate-500 mt-1">PHP {{ number_format($plan['amount_php'], 2) }} / month</span>
                                            <p class="text-xs text-slate-600 mt-2">{{ $plan['description'] }}</p>
                                            <p class="text-xs text-slate-400 mt-2">{{ $plan['limits'] }}</p>

                                            <form method="POST" action="{{ route($plan['checkout_route'], ['tenant' => $tenant->id]) }}" class="mt-3">
                                                @csrf
                                                <button type="submit" class="w-full rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-500 transition">
                                                    Choose {{ $plan['label'] }}
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-amber-800 mt-3">
                                    {{ ($mustContactAdminForReactivation ?? false) ? 'Contact platform admin for account reactivation.' : 'Please contact your Barbershop Admin to activate a subscription plan.' }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if ($canRecordWalkIns && $hasActivePlan)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">Record Walk-in Work</h3>
                        <p class="text-sm text-slate-400 mt-1">Encode walk-in service completion. Points are automatically computed for service type, punctuality, and 5-star rating.</p>
                    </div>

                    <div class="p-6">
                        <form method="POST" action="{{ route('manager.walkins.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf

                            <div>
                                <label for="branch_id" class="block text-sm font-medium text-slate-600">Branch</label>
                                <select id="branch_id" name="branch_id" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select branch</option>
                                    @foreach ($branchesForWalkIns as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="barber_id" class="block text-sm font-medium text-slate-600">Barber</label>
                                <select id="barber_id" name="barber_id" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select barber</option>
                                    @foreach ($barbersForWalkIns as $barber)
                                        <option value="{{ $barber->id }}" @selected((string) old('barber_id') === (string) $barber->id)>{{ $barber->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="service_id" class="block text-sm font-medium text-slate-600">Service/Task</label>
                                <select id="service_id" name="service_id" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select service</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>
                                            {{ $service->name }} ({{ ucfirst($service->type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="work_datetime" class="block text-sm font-medium text-slate-600">Work Date/Time</label>
                                <input id="work_datetime" name="work_datetime" type="datetime-local" value="{{ old('work_datetime') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="customer_rating" class="block text-sm font-medium text-slate-600">Customer Rating (optional)</label>
                                <select id="customer_rating" name="customer_rating" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">No rating</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected((string) old('customer_rating') === (string) $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" name="is_on_time" value="1" @checked(old('is_on_time')) class="rounded border-slate-200 bg-slate-50 text-indigo-600 focus:ring-blue-500">
                                    Barber was on time
                                </label>
                            </div>

                            <div class="md:col-span-2">
                                <label for="work_notes" class="block text-sm font-medium text-slate-600">Work Notes (optional)</label>
                                <textarea id="work_notes" name="work_notes" rows="3" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g., Haircut + beard trim; requested styling details">{{ old('work_notes') }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <button type="submit" class="rounded-md bg-blue-500 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600" >
                                    Record Completed Work
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Customer Availed Services</h3>
                    <p class="text-sm text-slate-400 mt-1">Recent services booked by customers are listed here.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Booked At</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Customer</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Service Availed</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Price</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse ($availedServices as $availed)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $availed->booked_at ? \Illuminate\Support\Carbon::parse($availed->booked_at)->format('Y-m-d g:i A') : '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $availed->customer_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $availed->service_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600">PHP {{ number_format((float) ($availed->total_price ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">{{ $availed->status ?? 'queued' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">No customer bookings yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Branch Appointments</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Date/Time</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Branch</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Customer</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Barber</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Service</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse ($appointments as $appointment)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_datetime)->format('Y-m-d g:i A') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $appointment->branch_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $appointment->customer_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $appointment->barber_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $appointment->service_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">{{ $appointment->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-400">No appointments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">Barber Points</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                            <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Barber</th>
                                    <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Total Points</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                @forelse ($barberPoints as $point)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $point->barber_name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $point->total_points }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-6 text-center text-slate-400">No barber points yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">Services Management</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                            <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Name</th>
                                    <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Type</th>
                                    <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Price</th>
                                    <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                @forelse ($services as $service)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $service->name }}</td>
                                        <td class="px-4 py-3 text-slate-600 capitalize">{{ $service->type }}</td>
                                        <td class="px-4 py-3 text-slate-600">PHP {{ number_format($service->price, 2) }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $service->duration_minutes }} mins</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-400">No services found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>%
