<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Platform Admin Dashboard
        </h2>
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

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Tenant Signup (Central App)</h3>
                <p class="text-sm text-gray-600 mt-1">Create tenant, owner account, domain, and optional DB provisioning from one form.</p>

                <form method="POST" action="{{ route('admin.tenants.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="name">Tenant Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="plan_tier">Plan Tier</label>
                        <select id="plan_tier" name="plan_tier" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                <option value="{{ $tier }}" @selected(old('plan_tier', 'starter') === $tier)>{{ ucfirst($tier) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="primary_domain">Primary Domain</label>
                        <input id="primary_domain" name="primary_domain" type="text" value="{{ old('primary_domain') }}" placeholder="tenant.example.com" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="database_name">Database Name</label>
                        <input id="database_name" name="database_name" type="text" value="{{ old('database_name') }}" placeholder="bs_tenant_sample" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="owner_name">Owner Name</label>
                        <input id="owner_name" name="owner_name" type="text" value="{{ old('owner_name') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="owner_email">Owner Email</label>
                        <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="owner_password">Owner Password</label>
                        <input id="owner_password" name="owner_password" type="password" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-2 flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="auto_activate" value="1" @checked(old('auto_activate')) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Auto-activate tenant
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="auto_provision_db" value="1" @checked(old('auto_provision_db')) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Auto-create tenant DB
                        </label>
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-600">
                            Create Tenant + Owner
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total Tenants</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $tenants->count() }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Total MRR</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">PHP {{ number_format($totalMrr, 2) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500">Plans Tracked</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($planMrrPhp) }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Tenant List</h3>
                    <p class="mt-1 text-sm text-gray-600">Tenants pay for their own plans and are activated automatically after successful payment.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Plan Tier</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Domain</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Database</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Subscription Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Plan Availed</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Plan Ends</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">MRR (PHP)</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Settings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($tenants as $tenant)
                                @php
                                    $subscription = $tenant->latestCashierSubscription;
                                    $planAvailedAt = $subscription?->created_at;
                                    $planEndsAt = $subscription?->ends_at;
                                    $subscriptionStatus = $subscription?->stripe_status;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $tenant->name }}</td>
                                    <td class="px-4 py-3 text-gray-700 capitalize">{{ $tenant->status ?? 'pending' }}</td>
                                    <td class="px-4 py-3 text-gray-700 capitalize">{{ $tenant->plan_tier }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $tenant->primary_domain ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $tenant->database_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700 capitalize">{{ $subscriptionStatus ?? 'Not subscribed' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $planAvailedAt ? $planAvailedAt->format('Y-m-d') : '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($planEndsAt)
                                            {{ $planEndsAt->format('Y-m-d') }}
                                        @elseif ($subscriptionStatus)
                                            Auto-renew
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ number_format($planMrrPhp[$tenant->plan_tier] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ optional($tenant->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        <form method="POST" action="{{ route('admin.tenants.update', ['tenant' => $tenant->id]) }}" class="grid grid-cols-1 gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $tenant->name }}" class="rounded-md border-gray-300 text-xs">
                                            <select name="plan_tier" class="rounded-md border-gray-300 text-xs">
                                                @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                                    <option value="{{ $tier }}" @selected($tenant->plan_tier === $tier)>{{ ucfirst($tier) }}</option>
                                                @endforeach
                                            </select>
                                            <select name="status" class="rounded-md border-gray-300 text-xs">
                                                @foreach (['pending', 'active', 'inactive', 'suspended'] as $status)
                                                    <option value="{{ $status }}" @selected(($tenant->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="primary_domain" value="{{ $tenant->primary_domain }}" placeholder="domain" class="rounded-md border-gray-300 text-xs">
                                            <input type="text" name="database_name" value="{{ $tenant->database_name }}" placeholder="db" class="rounded-md border-gray-300 text-xs">
                                            <button type="submit" class="rounded-md bg-indigo-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-600">Update Tenant</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-6 text-center text-gray-500">No tenants found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
