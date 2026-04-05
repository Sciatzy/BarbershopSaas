<x-app-layout>
    <x-slot name="header">
            <h2 class="text-2xl font-bold text-slate-800">Billing Plans</h2>
            <p class="text-sm text-slate-500 mt-1">Manage and upgrade your tenant subscription.</p>
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

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Choose a Subscription</h3>
                <p class="text-sm text-slate-500 mt-1">Payments are tenant-owned. After each successful payment, access remains active for 30 days.</p>

                @if ($tenant)
                    @php
                        $subscription = $tenant->latestCashierSubscription;
                        $status = (string) ($subscription?->stripe_status ?? 'not_subscribed');
                        $expiresAt = $subscription?->ends_at;
                    @endphp
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-slate-400">Current Tier</p>
                            <p class="mt-1 font-semibold text-slate-800 capitalize">{{ $tenant->plan_tier }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-slate-400">Subscription Status</p>
                            <p class="mt-1 font-semibold text-slate-800 capitalize">{{ str_replace('_', ' ', $status) }}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 p-3">
                            <p class="text-slate-400">Access Until</p>
                            <p class="mt-1 font-semibold text-slate-800">{{ $expiresAt ? $expiresAt->format('Y-m-d g:i A') : '-' }}</p>
                        </div>
                    </div>
                @endif

                @if (! $tenant)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        No tenant found for this account. Assign a tenant first, then retry billing checkout.
                    </div>
                @elseif ($mustContactAdminForReactivation ?? false)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Your tenant currently has an existing subscription but account access is {{ $tenant->status }}.
                        Please contact platform admin for reactivation. New checkout is disabled.
                    </div>
                @else
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        @foreach ($planOptions as $plan)
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm flex flex-col">
                                <h4 class="text-base font-semibold text-slate-800">{{ $plan['label'] }}</h4>
                                <p class="text-sm text-slate-600 mt-1">PHP {{ number_format($plan['amount_php'], 2) }} / month</p>
                                <p class="text-xs text-slate-500 mt-3">{{ $plan['description'] }}</p>
                                <p class="text-xs text-slate-400 mt-2">{{ $plan['limits'] }}</p>

                                <form method="POST" action="{{ route($plan['checkout_route'], ['tenant' => $tenant->id]) }}" class="mt-6 mt-auto">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-full rounded-md px-3 py-2 text-sm font-semibold text-white transition"
                                        
                                        title="Proceed to PayMongo checkout"
                                    >
                                        Pay with PayMongo - {{ $plan['label'] }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
