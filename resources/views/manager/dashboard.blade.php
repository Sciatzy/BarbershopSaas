<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Manager Dashboard</h2>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $canManageBilling ? 'Owner scope: monitoring, governance, and billing controls.' : 'Branch scope: day-to-day branch operations and service execution.' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($canManageServices ?? false)
                    <a href="{{ route('manager.services.index') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Manage Services & Pricing
                    </a>
                @endif
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

            @if (session('user_mgmt_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('user_mgmt_status') }}
                </div>
            @endif

            @if (session('user_mgmt_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('user_mgmt_error') }}
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
                            @if ($canManageBilling)
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
                            @else
                                <p class="text-xs text-slate-500">Domain updates are restricted to the barbershop owner.</p>
                            @endif
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

            @if ($hasActivePlan)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">System Update Center</h3>
                        <p class="text-sm text-slate-500 mt-1">Apply new central releases or hold specific updates when needed.</p>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Currently Applied Version</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $tenant?->applied_system_version ?: 'Not applied yet' }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Applied At: {{ optional($tenant?->applied_system_version_at)->toDateTimeString() ?: 'n/a' }}
                            </p>
                        </div>

                        @forelse ($pendingSystemReleases as $tenantRelease)
                            @php
                                $release = $tenantRelease->systemRelease;
                                $displayVersion = $release?->display_version ?: $release?->version ?: 'Unknown release';
                            @endphp
                            <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                                <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $displayVersion }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            State: {{ ucfirst($tenantRelease->state) }}
                                            @if ($release?->published_at)
                                                · Published: {{ $release->published_at->toDateTimeString() }}
                                            @endif
                                            @if ($release?->short_commit || $release?->commit_hash)
                                                · Commit: {{ $release->short_commit ?: $release->commit_hash }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenantRelease->state === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ strtoupper($tenantRelease->state) }}
                                    </span>
                                </div>

                                @if (filled($release?->release_notes))
                                    <div class="rounded-md border border-slate-100 bg-slate-50 p-3 text-sm text-slate-700 whitespace-pre-line">{{ $release->release_notes }}</div>
                                @endif

                                @if ($tenantRelease->state === 'held' && filled($tenantRelease->hold_note))
                                    <p class="text-xs text-indigo-700">Hold note: {{ $tenantRelease->hold_note }}</p>
                                @endif

                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                    <form method="POST" action="{{ route('manager.system-updates.apply', ['tenantRelease' => $tenantRelease->id]) }}">
                                        @csrf
                                        <button type="submit" class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                            Apply Update
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('manager.system-updates.hold', ['tenantRelease' => $tenantRelease->id]) }}" class="space-y-2">
                                        @csrf
                                        <input type="text" name="hold_note" maxlength="500" placeholder="Optional hold note (e.g. waiting for off-peak window)" class="w-full rounded-md border-slate-200 bg-slate-50 text-sm">
                                        <button type="submit" class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                                            Hold Update
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No pending or held updates at the moment.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">Support Tickets</h3>
                        <p class="text-sm text-slate-500 mt-1">Open a support ticket with the central team and track the full reply thread.</p>
                    </div>

                    <div class="p-6 space-y-4">
                        @if ($canManageBilling)
                            <form method="POST" action="{{ route('manager.support-tickets.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-2">
                                @csrf

                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                                    <input type="text" name="subject" required maxlength="160" class="mt-1 w-full rounded-md border-slate-200 bg-white text-sm" placeholder="Short summary of your issue">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                                    <select name="category" class="mt-1 w-full rounded-md border-slate-200 bg-white text-sm">
                                        @foreach (['general', 'bug', 'billing', 'performance', 'security', 'feature_request', 'other'] as $category)
                                            <option value="{{ $category }}">{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Priority</label>
                                    <select name="priority" class="mt-1 w-full rounded-md border-slate-200 bg-white text-sm">
                                        @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                                            <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Details</label>
                                    <textarea name="description" rows="4" required class="mt-1 w-full rounded-md border-slate-200 bg-white text-sm" placeholder="Include what happened, expected behavior, and replication steps."></textarea>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Attachment (optional)</label>
                                    <input type="file" name="attachment" class="mt-1 w-full rounded-md border-slate-200 bg-white text-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx">
                                </div>

                                <div class="lg:col-span-2">
                                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Submit Ticket</button>
                                </div>
                            </form>
                        @else
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Support ticket creation is limited to the barbershop owner account.
                            </div>
                        @endif

                        @forelse ($supportTickets as $ticket)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $ticket->ticket_number }} · {{ $ticket->subject }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Status: {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        · Category: {{ ucfirst(str_replace('_', ' ', $ticket->category)) }}
                                        · Priority: {{ ucfirst($ticket->priority) }}
                                        · Last update: {{ optional($ticket->latest_reply_at)->diffForHumans() ?? 'n/a' }}
                                    </p>
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

                                @if ($canManageBilling)
                                    <form method="POST" action="{{ route('manager.support-tickets.reply', ['ticket' => $ticket->id]) }}" enctype="multipart/form-data" class="space-y-3">
                                        @csrf
                                        <textarea name="message" rows="3" required class="w-full rounded-md border-slate-200 bg-slate-50 text-sm" placeholder="Add a reply"></textarea>
                                        <input type="file" name="attachment" class="w-full rounded-md border-slate-200 bg-white text-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx">
                                        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Send Reply</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No support tickets yet.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Customer Shop Appearance</h3>
                    <p class="text-sm text-slate-500 mt-1">Customize how your customers experience your branded shop dashboard.</p>
                </div>

                <div class="p-6">
                    @if ($canManageBilling)
                        @php
                            $appearanceLogoUrl = ! empty($tenant?->logo_path) ? asset('storage/'.$tenant->logo_path) : '';
                            $appearanceHeroUrl = ! empty($tenant?->hero_image_path) ? asset('storage/'.$tenant->hero_image_path) : '';
                        @endphp
                        <form method="POST" action="{{ route('manager.appearance.update') }}" enctype="multipart/form-data" class="space-y-5">
                            @csrf
                            @method('PATCH')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="appearance_brand_color" class="block text-sm font-medium text-slate-600">Primary Accent Color</label>
                                    <input id="appearance_brand_color" name="brand_color" type="color" value="{{ old('brand_color', $tenant?->brand_color ?? '#C9A84C') }}" class="mt-1 h-11 w-full rounded border border-slate-200 bg-white p-1">
                                </div>

                                <div>
                                    <label for="appearance_brand_color_secondary" class="block text-sm font-medium text-slate-600">Secondary CTA Color</label>
                                    <input id="appearance_brand_color_secondary" name="brand_color_secondary" type="color" value="{{ old('brand_color_secondary', $tenant?->brand_color_secondary ?? '#B54B2A') }}" class="mt-1 h-11 w-full rounded border border-slate-200 bg-white p-1">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="appearance_theme" class="block text-sm font-medium text-slate-600">Theme</label>
                                    <select id="appearance_theme" name="customer_theme" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="dark" @selected(old('customer_theme', $tenant?->customer_theme ?? 'dark') === 'dark')>Dark</option>
                                        <option value="light" @selected(old('customer_theme', $tenant?->customer_theme ?? 'dark') === 'light')>Light</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="appearance_font" class="block text-sm font-medium text-slate-600">Font Style</label>
                                    <select id="appearance_font" name="customer_font" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="dm_sans" @selected(old('customer_font', $tenant?->customer_font ?? 'dm_sans') === 'dm_sans')>DM Sans</option>
                                        <option value="poppins" @selected(old('customer_font', $tenant?->customer_font ?? 'dm_sans') === 'poppins')>Poppins</option>
                                        <option value="space_grotesk" @selected(old('customer_font', $tenant?->customer_font ?? 'dm_sans') === 'space_grotesk')>Space Grotesk</option>
                                        <option value="lora" @selected(old('customer_font', $tenant?->customer_font ?? 'dm_sans') === 'lora')>Lora</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="appearance_button_style" class="block text-sm font-medium text-slate-600">Button Style</label>
                                    <select id="appearance_button_style" name="customer_button_style" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="rounded" @selected(old('customer_button_style', $tenant?->customer_button_style ?? 'rounded') === 'rounded')>Rounded</option>
                                        <option value="pill" @selected(old('customer_button_style', $tenant?->customer_button_style ?? 'rounded') === 'pill')>Pill</option>
                                        <option value="sharp" @selected(old('customer_button_style', $tenant?->customer_button_style ?? 'rounded') === 'sharp')>Sharp</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="rounded-lg border border-slate-100 p-4">
                                    <label for="appearance_logo" class="block text-sm font-medium text-slate-600">Shop Logo</label>
                                    <input id="appearance_logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">

                                    @if (! empty($tenant?->logo_path))
                                        <img src="{{ asset('storage/'.$tenant->logo_path) }}" alt="Current logo" class="mt-3 h-14 w-auto rounded border border-slate-200 bg-white p-1">
                                        <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600">
                                            <input id="appearance_remove_logo" type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            Remove current logo
                                        </label>
                                    @endif
                                </div>

                                <div class="rounded-lg border border-slate-100 p-4">
                                    <label for="appearance_hero_image" class="block text-sm font-medium text-slate-600">Customer Hero Image</label>
                                    <input id="appearance_hero_image" name="hero_image" type="file" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">

                                    @if (! empty($tenant?->hero_image_path))
                                        <img src="{{ asset('storage/'.$tenant->hero_image_path) }}" alt="Current hero" class="mt-3 h-24 w-full max-w-sm rounded border border-slate-200 object-cover">
                                        <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600">
                                            <input id="appearance_remove_hero_image" type="checkbox" name="remove_hero_image" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            Remove current hero image
                                        </label>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-800">Live Customer View Preview</h4>
                                        <p class="text-xs text-slate-500">See how your customer dashboard style will look before you save.</p>
                                    </div>
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Preview</span>
                                </div>

                                <div id="appearance_preview_surface" class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-950 text-slate-100">
                                    <div id="appearance_preview_hero" data-initial-src="{{ $appearanceHeroUrl }}" class="h-28 w-full bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700"></div>

                                    <div class="p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img
                                                    id="appearance_preview_logo"
                                                    src="{{ $appearanceLogoUrl }}"
                                                    data-initial-src="{{ $appearanceLogoUrl }}"
                                                    alt="Preview logo"
                                                    class="h-10 w-10 rounded-md object-cover border border-white/20 {{ $appearanceLogoUrl === '' ? 'hidden' : '' }}"
                                                >
                                                <div
                                                    id="appearance_preview_logo_fallback"
                                                    class="h-10 w-10 rounded-md bg-white/15 text-white text-xs font-semibold flex items-center justify-center {{ $appearanceLogoUrl !== '' ? 'hidden' : '' }}"
                                                >
                                                    LOGO
                                                </div>
                                                <div class="min-w-0">
                                                    <p id="appearance_preview_shop_name" class="truncate text-sm font-semibold text-current">{{ $tenant?->name ?? 'Your Shop Name' }}</p>
                                                    <p class="text-xs text-current/70">Customer dashboard sample</p>
                                                </div>
                                            </div>

                                            <button
                                                id="appearance_preview_button"
                                                type="button"
                                                class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white"
                                                style="background: {{ old('brand_color_secondary', $tenant?->brand_color_secondary ?? '#B54B2A') }}; border-radius: 10px;"
                                            >
                                                Book Now
                                            </button>
                                        </div>

                                        <div class="mt-4 grid grid-cols-3 gap-2">
                                            <div class="rounded-md bg-white/10 px-2 py-2 text-[11px]">Points</div>
                                            <div class="rounded-md bg-white/10 px-2 py-2 text-[11px]">Bookings</div>
                                            <div class="rounded-md bg-white/10 px-2 py-2 text-[11px]">Rewards</div>
                                        </div>

                                        <div class="mt-3 h-2 rounded-full bg-white/15 overflow-hidden">
                                            <div id="appearance_preview_accent_bar" class="h-full w-2/3" style="background: {{ old('brand_color', $tenant?->brand_color ?? '#C9A84C') }};"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                                    Save Customer Appearance
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">Appearance updates are restricted to the barbershop owner account.</p>
                    @endif
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const form = document.querySelector('form[action="{{ route('manager.appearance.update') }}"]');

                    if (!form) {
                        return;
                    }

                    const primaryInput = document.getElementById('appearance_brand_color');
                    const secondaryInput = document.getElementById('appearance_brand_color_secondary');
                    const themeInput = document.getElementById('appearance_theme');
                    const fontInput = document.getElementById('appearance_font');
                    const buttonStyleInput = document.getElementById('appearance_button_style');
                    const logoInput = document.getElementById('appearance_logo');
                    const heroInput = document.getElementById('appearance_hero_image');
                    const removeLogoInput = document.getElementById('appearance_remove_logo');
                    const removeHeroInput = document.getElementById('appearance_remove_hero_image');

                    const previewSurface = document.getElementById('appearance_preview_surface');
                    const previewHero = document.getElementById('appearance_preview_hero');
                    const previewLogo = document.getElementById('appearance_preview_logo');
                    const previewLogoFallback = document.getElementById('appearance_preview_logo_fallback');
                    const previewButton = document.getElementById('appearance_preview_button');
                    const previewAccentBar = document.getElementById('appearance_preview_accent_bar');

                    if (!primaryInput || !secondaryInput || !themeInput || !fontInput || !buttonStyleInput || !previewSurface || !previewHero || !previewLogo || !previewLogoFallback || !previewButton || !previewAccentBar) {
                        return;
                    }

                    const initialLogoSrc = previewLogo.dataset.initialSrc || '';
                    const initialHeroSrc = previewHero.dataset.initialSrc || '';

                    const fontMap = {
                        dm_sans: "'DM Sans', sans-serif",
                        poppins: "'Poppins', sans-serif",
                        space_grotesk: "'Space Grotesk', sans-serif",
                        lora: "'Lora', serif",
                    };

                    const buttonRadiusMap = {
                        rounded: '10px',
                        pill: '999px',
                        sharp: '4px',
                    };

                    let uploadedLogoSrc = null;
                    let uploadedHeroSrc = null;

                    function readFileAsDataUrl(input, callback) {
                        if (!input || !input.files || !input.files[0]) {
                            callback(null);
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function (event) {
                            callback(typeof event.target?.result === 'string' ? event.target.result : null);
                        };
                        reader.readAsDataURL(input.files[0]);
                    }

                    function applyPreview() {
                        const primary = primaryInput.value || '#C9A84C';
                        const secondary = secondaryInput.value || '#B54B2A';
                        const theme = themeInput.value || 'dark';
                        const fontFamily = fontMap[fontInput.value] || fontMap.dm_sans;
                        const buttonRadius = buttonRadiusMap[buttonStyleInput.value] || buttonRadiusMap.rounded;

                        const darkMode = theme === 'dark';
                        previewSurface.style.background = darkMode ? '#0f172a' : '#f8fafc';
                        previewSurface.style.color = darkMode ? '#f8fafc' : '#0f172a';
                        previewSurface.style.borderColor = darkMode ? 'rgba(255,255,255,0.12)' : 'rgba(15,23,42,0.12)';
                        previewSurface.style.fontFamily = fontFamily;

                        previewButton.style.background = secondary;
                        previewButton.style.borderRadius = buttonRadius;
                        previewAccentBar.style.background = primary;

                        const effectiveHeroSrc = (removeHeroInput && removeHeroInput.checked)
                            ? null
                            : (uploadedHeroSrc || initialHeroSrc || null);

                        if (effectiveHeroSrc) {
                            previewHero.style.backgroundImage = "linear-gradient(rgba(15,23,42,0.45), rgba(15,23,42,0.45)), url('" + effectiveHeroSrc + "')";
                            previewHero.style.backgroundSize = 'cover';
                            previewHero.style.backgroundPosition = 'center';
                        } else {
                            previewHero.style.backgroundImage = 'linear-gradient(135deg, ' + primary + ', ' + secondary + ')';
                            previewHero.style.backgroundSize = 'cover';
                            previewHero.style.backgroundPosition = 'center';
                        }

                        const effectiveLogoSrc = (removeLogoInput && removeLogoInput.checked)
                            ? null
                            : (uploadedLogoSrc || initialLogoSrc || null);

                        if (effectiveLogoSrc) {
                            previewLogo.src = effectiveLogoSrc;
                            previewLogo.classList.remove('hidden');
                            previewLogoFallback.classList.add('hidden');
                        } else {
                            previewLogo.classList.add('hidden');
                            previewLogoFallback.classList.remove('hidden');
                        }
                    }

                    const inputsToWatch = [primaryInput, secondaryInput, themeInput, fontInput, buttonStyleInput, removeLogoInput, removeHeroInput].filter(Boolean);
                    inputsToWatch.forEach(function (input) {
                        input.addEventListener('input', applyPreview);
                        input.addEventListener('change', applyPreview);
                    });

                    if (logoInput) {
                        logoInput.addEventListener('change', function () {
                            readFileAsDataUrl(logoInput, function (src) {
                                uploadedLogoSrc = src;
                                applyPreview();
                            });
                        });
                    }

                    if (heroInput) {
                        heroInput.addEventListener('change', function () {
                            readFileAsDataUrl(heroInput, function (src) {
                                uploadedHeroSrc = src;
                                applyPreview();
                            });
                        });
                    }

                    applyPreview();
                });
            </script>

            @if ($canManageBilling)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">Dashboard RBAC Settings</h3>
                        <p class="text-sm text-slate-500 mt-1">Choose which dashboard features are available to Branch Managers and Barbers.</p>
                    </div>

                    <div class="p-6">
                        <form method="POST" action="{{ route('manager.dashboard-access.update') }}" class="space-y-5">
                            @csrf
                            @method('PATCH')

                            @foreach ($dashboardAccessDefinitions as $roleKey => $roleDefinition)
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <h4 class="text-sm font-semibold text-slate-900">{{ $roleDefinition['label'] }}</h4>
                                    <p class="text-xs text-slate-500 mt-1">{{ $roleDefinition['description'] }}</p>

                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach ($roleDefinition['features'] as $featureKey => $featureDefinition)
                                            @php
                                                $accessInputName = "access.{$roleKey}.{$featureKey}";
                                                $isEnabled = (bool) old($accessInputName, data_get($dashboardAccessSettings, "{$roleKey}.{$featureKey}", true));
                                            @endphp
                                            <label class="rounded-lg border border-slate-200 bg-slate-50 p-3 flex items-start gap-3 cursor-pointer">
                                                <input type="hidden" name="access[{{ $roleKey }}][{{ $featureKey }}]" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="access[{{ $roleKey }}][{{ $featureKey }}]"
                                                    value="1"
                                                    class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                    @checked($isEnabled)
                                                >
                                                <span>
                                                    <span class="block text-sm font-semibold text-slate-800">{{ $featureDefinition['label'] }}</span>
                                                    <span class="block text-xs text-slate-500 mt-0.5">{{ $featureDefinition['description'] }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Save Dashboard Access Settings
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($hasActivePlan && ($canManageUsers ?? false))
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-slate-800">User Management</h3>
                        <p class="text-sm text-slate-500 mt-1">Create, delete, and reset passwords for tenant Branch Managers, Barbers, and Customers.</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <form method="POST" action="{{ route('manager.users.store') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 items-end">
                            @csrf

                            <div>
                                <label for="managed_role" class="block text-sm font-medium text-slate-600">User Type</label>
                                <select id="managed_role" name="role" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="Branch Manager" @selected(old('role') === 'Branch Manager')>Branch Manager</option>
                                    <option value="Barber" @selected(old('role') === 'Barber')>Barber</option>
                                    <option value="Customer" @selected(old('role') === 'Customer')>Customer</option>
                                </select>
                            </div>

                            <div>
                                <label for="managed_name" class="block text-sm font-medium text-slate-600">Name</label>
                                <input id="managed_name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Full name">
                            </div>

                            <div>
                                <label for="managed_email" class="block text-sm font-medium text-slate-600">Email</label>
                                <input id="managed_email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="user@example.com">
                            </div>

                            <div>
                                <label for="managed_password" class="block text-sm font-medium text-slate-600">Password</label>
                                <input id="managed_password" name="password" type="password" required minlength="8" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Minimum 8 characters">
                            </div>

                            <div>
                                <label for="managed_branch_id" class="block text-sm font-medium text-slate-600">Branch (required for Branch Manager, optional for Barber)</label>
                                <select id="managed_branch_id" name="branch_id" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">No branch</option>
                                    @foreach ($assignableBranches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="xl:col-span-5">
                                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                    Create User
                                </button>
                            </div>
                        </form>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <div class="rounded-xl border border-slate-100 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                                    <h4 class="text-sm font-semibold text-slate-800">Barbers</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Name</th>
                                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Email</th>
                                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @forelse ($manageableBarbers as $managedBarber)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-800">
                                                        <p class="font-medium">{{ $managedBarber->name }}</p>
                                                        <p class="text-xs text-slate-500">Branch ID: {{ $managedBarber->branch_id ?? 'N/A' }}</p>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600">{{ $managedBarber->email }}</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-col gap-2">
                                                            <form method="POST" action="{{ route('manager.users.password', ['userId' => $managedBarber->id]) }}" class="flex items-center gap-2">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input name="password" type="password" minlength="8" required class="w-full rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500" placeholder="New password">
                                                                <button type="submit" class="shrink-0 rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                                                    Update
                                                                </button>
                                                            </form>

                                                            <form method="POST" action="{{ route('manager.users.destroy', ['userId' => $managedBarber->id]) }}" onsubmit="return confirm('Delete this barber account?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 border border-red-200">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-4 py-6 text-center text-slate-400">No barbers found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-100 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                                    <h4 class="text-sm font-semibold text-slate-800">Customers</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-slate-500">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Name</th>
                                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Email</th>
                                                <th class="px-4 py-3 text-left text-xs uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @forelse ($manageableCustomers as $managedCustomer)
                                                <tr>
                                                    <td class="px-4 py-3 text-slate-800 font-medium">{{ $managedCustomer->name }}</td>
                                                    <td class="px-4 py-3 text-slate-600">{{ $managedCustomer->email }}</td>
                                                    <td class="px-4 py-3">
                                                        <div class="flex flex-col gap-2">
                                                            <form method="POST" action="{{ route('manager.users.password', ['userId' => $managedCustomer->id]) }}" class="flex items-center gap-2">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input name="password" type="password" minlength="8" required class="w-full rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500" placeholder="New password">
                                                                <button type="submit" class="shrink-0 rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                                                    Update
                                                                </button>
                                                            </form>

                                                            <form method="POST" action="{{ route('manager.users.destroy', ['userId' => $managedCustomer->id]) }}" onsubmit="return confirm('Delete this customer account?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 border border-red-200">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-4 py-6 text-center text-slate-400">No customers found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (($canRecordWalkIns ?? false) && $hasActivePlan)
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

                @if ($canManageServices ?? false)
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
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
