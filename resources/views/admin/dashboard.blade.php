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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <p class="text-sm text-slate-400">System Version</p>
                    <p id="system-version-value" class="text-2xl font-bold text-slate-800 mt-1">Loading...</p>
                    <p id="system-version-meta" class="mt-1 text-xs text-slate-500 uppercase tracking-wide">Checking repository status</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Automated Version Control Sync</h3>
                        <p class="mt-1 text-sm text-slate-500">Fetch the latest central version and publish updates to all active tenants or selected cohorts.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.releases.fetch-latest') }}">
                        @csrf
                        <input type="hidden" name="fetch_remote" value="1">
                        <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            Fetch Latest Release Metadata
                        </button>
                    </form>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($systemReleases as $release)
                        <div class="p-6 space-y-4">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $release->display_version ?: $release->version }}
                                        <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $release->publication_status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ strtoupper($release->publication_status) }}
                                        </span>
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Source: {{ $release->source ?? 'n/a' }}
                                        @if ($release->branch)
                                            · Branch: {{ $release->branch }}
                                        @endif
                                        @if ($release->short_commit || $release->commit_hash)
                                            · Commit: {{ $release->short_commit ?: $release->commit_hash }}
                                        @endif
                                        @if ($release->published_at)
                                            · Published: {{ $release->published_at->toDateTimeString() }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-700">Pending: {{ $release->pending_states_count ?? 0 }}</span>
                                    <span class="rounded-full bg-indigo-50 px-3 py-1 font-semibold text-indigo-700">Held: {{ $release->held_states_count ?? 0 }}</span>
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700">Applied: {{ $release->applied_states_count ?? 0 }}</span>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.releases.publish', ['release' => $release->id]) }}" class="grid grid-cols-1 gap-3 lg:grid-cols-2" data-rollout-form>
                                @csrf

                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Release Notes</label>
                                    <textarea name="release_notes" rows="3" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm" placeholder="Optional release notes shown to tenant owners">{{ old('release_notes', $release->release_notes) }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cohort</label>
                                    <select name="cohort_mode" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm" data-cohort-mode>
                                        <option value="all_active">All Active Tenants</option>
                                        <option value="plan_tier">Plan Tier</option>
                                        <option value="tenant_ids">Specific Tenant IDs</option>
                                    </select>
                                </div>

                                <div data-cohort-plan-wrapper class="hidden">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Plan Tier</label>
                                    <select name="cohort_plan_tier" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm">
                                        @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                            <option value="{{ $tier }}">{{ ucfirst($tier) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div data-cohort-tenant-wrapper class="hidden lg:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant IDs</label>
                                    <textarea name="cohort_tenant_ids" rows="2" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm" placeholder="Comma-separated tenant IDs"></textarea>
                                </div>

                                <div class="lg:col-span-2">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        Publish / Refresh Rollout
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-slate-500">No synced releases yet. Use "Fetch Latest Release Metadata" first.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Tenant Support Ticket Queue</h3>
                    <p class="mt-1 text-sm text-slate-500">Filter, search, and respond to tenant owner support tickets from one queue.</p>
                </div>

                @php
                    $ticketStatusOptions = ['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
                    $ticketPriorityOptions = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
                    $ticketCategoryOptions = ['general' => 'General', 'bug' => 'Bug', 'billing' => 'Billing', 'performance' => 'Performance', 'security' => 'Security', 'feature_request' => 'Feature Request', 'other' => 'Other'];
                @endphp

                <div class="p-6 border-b border-slate-100 bg-slate-50">
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-5">
                        <input type="text" name="ticket_search" value="{{ $supportTicketFilters['search'] ?? '' }}" placeholder="Search ticket #, subject, tenant, owner" class="w-full rounded-md border-slate-200 bg-white text-sm lg:col-span-2">

                        <select name="ticket_status" class="w-full rounded-md border-slate-200 bg-white text-sm">
                            <option value="">All Statuses</option>
                            @foreach ($ticketStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($supportTicketFilters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="ticket_priority" class="w-full rounded-md border-slate-200 bg-white text-sm">
                            <option value="">All Priorities</option>
                            @foreach ($ticketPriorityOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($supportTicketFilters['priority'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="ticket_category" class="w-full rounded-md border-slate-200 bg-white text-sm">
                            <option value="">All Categories</option>
                            @foreach ($ticketCategoryOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($supportTicketFilters['category'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <div class="lg:col-span-5 flex gap-2">
                            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Apply Filters</button>
                            <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="p-6 space-y-4">
                    @forelse ($supportTickets as $ticket)
                        <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $ticket->ticket_number }} · {{ $ticket->subject }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Tenant: {{ $ticket->tenant?->name ?? $ticket->tenant_id }}
                                        · Owner: {{ $ticket->owner?->name ?? 'Unknown' }}
                                        · Category: {{ ucfirst($ticket->category) }}
                                        · Priority: {{ ucfirst($ticket->priority) }}
                                        · Updated: {{ optional($ticket->latest_reply_at)->diffForHumans() ?? 'n/a' }}
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('admin.support-tickets.status', ['ticket' => $ticket->id]) }}" class="flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-md border-slate-200 bg-slate-50 text-sm">
                                        @foreach ($ticketStatusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Update</button>
                                </form>
                            </div>

                            <div class="max-h-72 space-y-3 overflow-y-auto rounded-lg border border-slate-100 bg-slate-50 p-3">
                                @forelse ($ticket->messages as $message)
                                    <div class="rounded-md border border-slate-200 bg-white p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            {{ str_replace('_', ' ', $message->sender_role) }}
                                            @if ($message->sender?->name)
                                                · {{ $message->sender->name }}
                                            @endif
                                            · {{ $message->created_at?->toDateTimeString() }}
                                        </p>
                                        <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $message->message }}</p>

                                        @if ($message->hasAttachment())
                                            <a href="{{ $message->attachmentUrl() }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                                Attachment: {{ $message->attachment_original_name ?: 'Download file' }}
                                            </a>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">No messages yet.</p>
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('admin.support-tickets.reply', ['ticket' => $ticket->id]) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <textarea name="message" rows="3" required class="w-full rounded-md border-slate-200 bg-slate-50 text-sm" placeholder="Reply to tenant owner"></textarea>

                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                    <select name="status" class="rounded-md border-slate-200 bg-slate-50 text-sm">
                                        @foreach ($ticketStatusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>

                                    <input type="file" name="attachment" class="w-full rounded-md border-slate-200 bg-white text-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx">
                                </div>

                                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Send Reply</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No support tickets found for the current filter.</p>
                    @endforelse

                    @if (method_exists($supportTickets, 'links'))
                        <div>
                            {{ $supportTickets->links() }}
                        </div>
                    @endif
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
                                    $manualPlanDurationDays = 30;
                                    $hasBillingSubscription = $subscription !== null;
                                    $planAvailedAt = $subscription?->created_at ?? $tenant->activated_at ?? $tenant->created_at;
                                    $planEndsAt = $subscription?->ends_at;
                                    $subscriptionStatus = $subscription?->stripe_status;

                                    if (! $hasBillingSubscription && $planAvailedAt) {
                                        $planEndsAt = $planAvailedAt->copy()->addDays($manualPlanDurationDays);
                                    }
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $tenant->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ optional($tenant->owner)->email ?? '-' }}</td>
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
                                        <button
                                            onclick="(function(){ const target=document.getElementById('tenant-detail-{{ $tenant->id }}'); if(!target) return; const willOpen=target.classList.contains('hidden'); document.querySelectorAll('[id^=tenant-detail-]').forEach(function(row){ row.classList.add('hidden'); }); if(willOpen){ target.classList.remove('hidden'); } })()"
                                            type="button"
                                            class="whitespace-nowrap px-4 py-1.5 rounded-full bg-[#E2D4FF] text-black text-xs font-bold shadow-sm transition-transform hover:scale-105"
                                        >
                                            Manage
                                        </button>
                                    </td>
                                </tr>
                                <tr id="tenant-detail-{{ $tenant->id }}" class="hidden bg-slate-50/70">
                                    <td colspan="12" class="px-4 py-4">
                                        <div class="rounded-2xl border border-slate-200 bg-white p-5 space-y-5">
                                            <div>
                                                <h4 class="text-base font-semibold text-slate-900">Edit Tenant</h4>
                                                <p class="text-sm text-slate-500 mt-1">Update details for {{ $tenant->name }}</p>
                                            </div>

                                            <form id="tenant-update-form-{{ $tenant->id }}" method="POST" action="{{ route('admin.tenants.update', ['tenant' => $tenant->id]) }}" class="space-y-4">
                                                @csrf
                                                @method('PATCH')

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tenant Name</label>
                                                        <input type="text" name="name" value="{{ $tenant->name }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 font-medium py-2.5 px-4 shadow-sm" required>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Plan Tier</label>
                                                        <select name="plan_tier" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 font-medium py-2.5 px-4 shadow-sm" required>
                                                            @foreach (['starter', 'professional', 'business', 'enterprise'] as $tier)
                                                                <option value="{{ $tier }}" @selected($tenant->plan_tier === $tier)>{{ ucfirst($tier) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Status</label>
                                                        <select name="status" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 font-medium py-2.5 px-4 shadow-sm" required>
                                                            @foreach (['pending', 'active', 'inactive', 'suspended'] as $status)
                                                                <option value="{{ $status }}" @selected(($tenant->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Primary Domain</label>
                                                        <input type="text" name="primary_domain" value="{{ $tenant->primary_domain }}" placeholder="e.g. myshop.localhost:8000" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 font-medium py-2.5 px-4 shadow-sm">
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Database Name</label>
                                                        <input type="text" name="database_name" value="{{ $tenant->database_name }}" placeholder="tenant_db_1" class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:ring-2 focus:ring-blue-300 focus:border-blue-300 font-medium py-2.5 px-4 shadow-sm">
                                                    </div>
                                                </div>
                                            </form>

                                            <div class="flex flex-wrap justify-end gap-3">
                                                <a href="{{ route('admin.customer.dashboard', ['tenant' => $tenant->id]) }}" class="px-5 py-2.5 rounded-full bg-blue-100 border border-blue-200 text-blue-800 font-bold hover:bg-blue-200 transition-colors shadow-sm" target="_blank" rel="noopener">
                                                    Open Customer View
                                                </a>
                                                <form method="POST" action="{{ route('admin.tenants.resend-credentials', ['tenant' => $tenant->id]) }}" onsubmit="return confirm('Regenerate and email temporary credentials to this tenant owner?');">
                                                    @csrf
                                                    <button type="submit" class="px-5 py-2.5 rounded-full bg-amber-100 border border-amber-200 text-amber-800 font-bold hover:bg-amber-200 transition-colors shadow-sm">Resend Credentials</button>
                                                </form>
                                                <button type="button" onclick="document.getElementById('tenant-detail-{{ $tenant->id }}')?.classList.add('hidden')" class="px-5 py-2.5 rounded-full bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors shadow-sm">Cancel</button>
                                                <button type="submit" form="tenant-update-form-{{ $tenant->id }}" class="px-5 py-2.5 rounded-full bg-black text-white font-bold hover:bg-slate-800 transition-colors shadow-sm tracking-wide">Save Changes</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-6 text-center text-slate-400">No tenants found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rollout-form]').forEach(function (form) {
                const modeInput = form.querySelector('[data-cohort-mode]');
                const planWrapper = form.querySelector('[data-cohort-plan-wrapper]');
                const tenantWrapper = form.querySelector('[data-cohort-tenant-wrapper]');

                if (!modeInput || !planWrapper || !tenantWrapper) {
                    return;
                }

                const syncVisibility = function () {
                    const mode = modeInput.value || 'all_active';

                    planWrapper.classList.toggle('hidden', mode !== 'plan_tier');
                    tenantWrapper.classList.toggle('hidden', mode !== 'tenant_ids');
                };

                modeInput.addEventListener('change', syncVisibility);
                syncVisibility();
            });

            const versionValue = document.getElementById('system-version-value');
            const versionMeta = document.getElementById('system-version-meta');

            if (!versionValue || !versionMeta) {
                return;
            }

            fetch('{{ route('system.version', ['full' => 1], false) }}', {
                headers: {
                    'Accept': 'application/json'
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    return response.json();
                })
                .then(function (payload) {
                    const version = payload.display_version || payload.version || 'vdev';
                    const status = (payload.status || 'unknown').toUpperCase();
                    const branch = payload.branch || null;
                    const commit = payload.short_commit || payload.commit || null;

                    versionValue.textContent = version;

                    const metaParts = [status, branch, commit].filter(Boolean);
                    versionMeta.textContent = metaParts.length > 0
                        ? metaParts.join(' · ')
                        : 'Version metadata unavailable';
                })
                .catch(function () {
                    versionValue.textContent = 'Unavailable';
                    versionMeta.textContent = 'Endpoint unreachable';
                });
        });
    </script>
</x-app-layout>
