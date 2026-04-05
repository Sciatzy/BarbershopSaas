<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-800">Platform Admin Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">Platform management and analytics.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Tenant Signup (Central App)</h3>
                <p class="text-sm text-slate-500 mt-1">Create tenant + manager account. Password is auto-generated, database is auto-provisioned, and access details are emailed automatically.</p>

                <form method="POST" action="{{ route('admin.tenants.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-600" for="name">Tenant Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600" for="plan_tier">Plan Tier</label>
                        <select id="plan_tier" name="plan_tier" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                <option value="{{ $tier }}" @selected(old('plan_tier', 'starter') === $tier)>{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600" for="primary_domain">Primary Domain</label>
                        <input id="primary_domain" name="primary_domain" type="text" value="{{ old('primary_domain') }}" placeholder="tenant.example.com" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600" for="database_name">Database Name</label>
                        <input id="database_name" name="database_name" type="text" value="{{ old('database_name') }}" placeholder="bs_tenant_sample" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600" for="owner_name">Owner Name</label>
                        <input id="owner_name" name="owner_name" type="text" value="{{ old('owner_name') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600" for="owner_email">Owner Email</label>
                        <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2 rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800">
                        Password is generated securely by the system and sent to the manager email. Tenant is activated and provisioned automatically.
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit" class="rounded-md bg-blue-500 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600">
                            Create Tenant + Owner
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <p class="text-sm text-slate-400">Total Tenants</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ $tenants->count() }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <p class="text-sm text-slate-400">Total MRR</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">PHP {{ number_format($totalMrr, 2) }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <p class="text-sm text-slate-400">Plans Tracked</p>
                    <p class="text-2xl font-bold text-slate-800 mt-1">{{ count($planMrrPhp) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Tenant List</h3>
                    <p class="mt-1 text-sm text-slate-500">Tenants pay for their own plans and are activated automatically after successful payment.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Name</th>
     <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Owner Email</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Plan Tier</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Domain</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Database</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Subscription Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Plan Availed</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Plan Ends</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">MRR (PHP)</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Created</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Operate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse ($tenants as $tenant)
                                @php
                                    $subscription = $tenant->latestCashierSubscription;
                                    $planAvailedAt = $subscription?->created_at;
                                    $planEndsAt = $subscription?->ends_at;
                                    $subscriptionStatus = $subscription?->stripe_status;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $tenant->name }}</td>
     <td class="px-4 py-3 text-slate-600">{{ $tenant->owner->email ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">{{ $tenant->status ?? 'pending' }}</td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">{{ $tenant->plan_tier }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($tenant->primary_domain)
                                            @php
                                                $domainUrl = str_starts_with($tenant->primary_domain, 'http://') || str_starts_with($tenant->primary_domain, 'https://')
                                                    ? $tenant->primary_domain
                                                    : 'http://'.$tenant->primary_domain;
                                            @endphp
                                            <a href="{{ $domainUrl }}" target="_blank" rel="noopener" class="text-indigo-700 hover:text-indigo-600 hover:underline">
                                                {{ $tenant->primary_domain }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $tenant->database_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 capitalize">{{ $subscriptionStatus ?? 'Not subscribed' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $planAvailedAt ? $planAvailedAt->format('Y-m-d') : '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($planEndsAt)
                                            {{ $planEndsAt->format('Y-m-d') }}
                                        @elseif ($subscriptionStatus)
                                            Auto-renew
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ number_format($planMrrPhp[$tenant->plan_tier] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ optional($tenant->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <form method="POST" action="{{ route('admin.tenants.update', ['tenant' => $tenant->id]) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $tenant->name }}" class="rounded-md border-slate-200 bg-slate-50 text-xs">
                                            <select name="plan_tier" class="rounded-md border-slate-200 bg-slate-50 text-xs">
                                                @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                                    <option value="{{ $tier }}" @selected($tenant->plan_tier === $tier)>{{ ucfirst($tier) }}</option>
                                                @endforeach
                                            </select>
                                            <select name="status" class="rounded-md border-slate-200 bg-slate-50 text-xs">
                                                @foreach (['pending', 'active', 'inactive', 'suspended'] as $status)
                                                    <option value="{{ $status }}" @selected(($tenant->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="primary_domain" value="{{ $tenant->primary_domain }}" placeholder="domain" class="rounded-md border-slate-200 bg-slate-50 text-xs">
                                            <input type="text" name="database_name" value="{{ $tenant->database_name }}" placeholder="db" class="rounded-md border-slate-200 bg-slate-50 text-xs">
                                            <button type="submit" class="mt-2 w-full rounded-full border border-blue-500 text-blue-500 hover:bg-blue-50 px-4 py-1.5 text-xs font-semibold transition-colors">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-6 text-center text-slate-400">No tenants found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
