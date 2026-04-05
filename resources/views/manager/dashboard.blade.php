<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-800">Manager Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">Tenant subscription and operations overview.</p>
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
                    <form method="POST" action="{{ route($autoPlanRoute, ['tenant' => $tenant->id]) }}" id="autoCheckoutForm" class="hidden">
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
            @endphp

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Subscription Overview</h3>
                    <p class="text-sm text-slate-400 mt-1">This panel shows the current plan for your tenant and when it was availed.</p>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-slate-400">Current Plan</p>
                        <p class="text-slate-800 font-semibold mt-1 capitalize">{{ $tenant?->plan_tier ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-slate-400">Tenant Access</p>
                        <p class="text-slate-800 font-semibold mt-1 capitalize">{{ $tenant?->status ?? 'pending' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-slate-400">Subscription</p>
                        <p class="text-slate-800 font-semibold mt-1 capitalize">{{ $subscriptionStatus ?? 'Not subscribed' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-slate-400">Plan Availed</p>
                        <p class="text-slate-800 font-semibold mt-1">{{ $planAvailedAt ? $planAvailedAt->format('Y-m-d') : '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <p class="text-slate-400">Plan Ends</p>
                        <p class="text-slate-800 font-semibold mt-1">
                            @if ($planEndsAt)
                                {{ $planEndsAt->format('Y-m-d') }}
                            @elseif ($subscriptionStatus)
                                Auto-renew
                            @else
                                -
                            @endif
                        </p>
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
</x-app-layout>
