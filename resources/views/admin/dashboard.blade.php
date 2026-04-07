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
                        <input id="primary_domain" name="primary_domain" type="text" value="{{ old('primary_domain') }}" placeholder="myshop.localhost:8000" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
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
                                                $rawDomain = (string) $tenant->primary_domain;
                                                $appPort = parse_url((string) config('app.url', ''), PHP_URL_PORT);
                                                $resolvedPort = is_int($appPort) ? $appPort : (int) request()->getPort();
                                                $portSegment = in_array($resolvedPort, [80, 443], true) ? '' : ':'.$resolvedPort;

                                                $domainWithoutScheme = preg_replace('#^https?://#', '', $rawDomain) ?? $rawDomain;
                                                $hasExplicitPort = preg_match('/:\d+$/', $domainWithoutScheme) === 1;
                                                $displayDomain = $hasExplicitPort ? $domainWithoutScheme : $domainWithoutScheme.$portSegment;
                                                $domainUrl = 'http://'.$displayDomain;
                                            @endphp
                                            <a href="{{ $domainUrl }}" target="_blank" rel="noopener" class="text-indigo-700 hover:text-indigo-600 hover:underline">
                                                {{ $displayDomain }}
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
                                    <td class="px-4 py-3 text-slate-600" x-data="{ open: false }">
                                        <button @click="open = true" type="button" class="whitespace-nowrap px-4 py-1.5 rounded-full bg-[#E2D4FF] text-black text-xs font-bold shadow-sm transition-transform hover:scale-105">
                                            Manage
                                        </button>

                                        <!-- Edit Modal -->
                                        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" x-cloak style="display: none;" @keydown.escape.window="open = false">
                                            <div @click.away="open = false" class="bg-white p-8 rounded-[28px] shadow-2xl flex flex-col w-full max-w-md text-left transform transition-all relative animate-fade-in">
                                                <button @click="open = false" type="button" class="absolute top-6 right-6 text-gray-400 hover:text-black transition-colors rounded-full p-1 hover:bg-gray-100">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>

                                                <div class="mb-6">
                                                    <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Tenant</h3>
                                                    <p class="text-sm font-medium text-gray-400 mt-1">Update details for {{ $tenant->name }}</p>
                                                </div>

                                                <form id="tenant-update-form-{{ $tenant->id }}" method="POST" action="{{ route('admin.tenants.update', ['tenant' => $tenant->id]) }}" class="space-y-5">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div>
                                                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Tenant Name</label>
                                                        <input type="text" name="name" value="{{ $tenant->name }}" class="w-full rounded-xl border border-gray-200 bg-[#F3F4F6] text-gray-900 focus:ring-2 focus:ring-[#E2D4FF] focus:border-[#E2D4FF] font-medium py-2.5 px-4 shadow-sm" required>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Plan Tier</label>
                                                            <select name="plan_tier" class="w-full rounded-xl border border-gray-200 bg-[#F3F4F6] text-gray-900 focus:ring-2 focus:ring-[#E2D4FF] focus:border-[#E2D4FF] font-medium py-2.5 px-4 shadow-sm" required>
                                                                @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                                                    <option value="{{ $tier }}" @selected($tenant->plan_tier === $tier)>{{ ucfirst($tier) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Status</label>
                                                            <select name="status" class="w-full rounded-xl border border-gray-200 bg-[#F3F4F6] text-gray-900 focus:ring-2 focus:ring-[#E2D4FF] focus:border-[#E2D4FF] font-medium py-2.5 px-4 shadow-sm" required>
                                                                @foreach (['pending', 'active', 'inactive', 'suspended'] as $status)
                                                                    <option value="{{ $status }}" @selected(($tenant->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Primary Domain</label>
                                                        <input type="text" name="primary_domain" value="{{ $tenant->primary_domain }}" placeholder="e.g. myshop.localhost:8000" class="w-full rounded-xl border border-gray-200 bg-[#F3F4F6] text-gray-900 focus:ring-2 focus:ring-[#E2D4FF] focus:border-[#E2D4FF] font-medium py-2.5 px-4 shadow-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Database Name</label>
                                                        <input type="text" name="database_name" value="{{ $tenant->database_name }}" placeholder="tenant_db_1" class="w-full rounded-xl border border-gray-200 bg-[#F3F4F6] text-gray-900 focus:ring-2 focus:ring-[#E2D4FF] focus:border-[#E2D4FF] font-medium py-2.5 px-4 shadow-sm">
                                                    </div>

                                                </form>

                                                <div class="pt-4 flex justify-end gap-3">
                                                    <a href="{{ route('admin.customer.dashboard', ['tenant' => $tenant->id]) }}" class="px-5 py-2.5 rounded-full bg-blue-100 border border-blue-200 text-blue-800 font-bold hover:bg-blue-200 transition-colors shadow-sm" target="_blank" rel="noopener">
                                                        Open Customer View
                                                    </a>
                                                    <form method="POST" action="{{ route('admin.tenants.resend-credentials', ['tenant' => $tenant->id]) }}" onsubmit="return confirm('Regenerate and email temporary credentials to this tenant owner?');">
                                                        @csrf
                                                        <button type="submit" class="px-5 py-2.5 rounded-full bg-amber-100 border border-amber-200 text-amber-800 font-bold hover:bg-amber-200 transition-colors shadow-sm">Resend Credentials</button>
                                                    </form>
                                                    <button type="button" @click="open = false" class="px-5 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition-colors shadow-sm">Cancel</button>
                                                    <button type="submit" form="tenant-update-form-{{ $tenant->id }}" class="px-5 py-2.5 rounded-full bg-black text-white font-bold hover:bg-gray-800 transition-colors shadow-sm tracking-wide">Save Changes</button>
                                                </div>
                                            </div>
                                        </div>
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
