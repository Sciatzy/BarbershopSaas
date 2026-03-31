<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PayMongoCheckoutService;
use App\Services\TenantLifecycleNotifier;
use App\Services\TenantLimitValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(
        private PayMongoCheckoutService $payMongoCheckoutService,
        private TenantLimitValidator $tenantLimitValidator,
        private TenantLifecycleNotifier $notifier,
    ) {}

    /**
     * Handle checkout for the starter plan (PHP 499).
     */
    public function checkoutStarter(Request $request, Tenant $tenant)
    {
        return $this->checkoutForTier($request, $tenant, 'starter');
    }

    /**
     * Handle checkout for the professional plan (PHP 1299).
     */
    public function checkoutProfessional(Request $request, Tenant $tenant)
    {
        return $this->checkoutForTier($request, $tenant, 'professional');
    }

    /**
     * Handle checkout for the business plan (PHP 2499).
     */
    public function checkoutBusiness(Request $request, Tenant $tenant)
    {
        return $this->checkoutForTier($request, $tenant, 'business');
    }

    /**
     * Handle checkout for the enterprise plan (PHP 4999).
     */
    public function checkoutEnterprise(Request $request, Tenant $tenant)
    {
        return $this->checkoutForTier($request, $tenant, 'enterprise');
    }

    public function success(Request $request): JsonResponse|RedirectResponse
    {
        $activationMessage = 'Checkout completed. Your plan will activate once PayMongo webhook processing finishes.';

        if ($request->user() && $request->user()->hasRole('Barbershop Admin')) {
            $tenantId = (string) $request->query('tenant', '');
            $tier = (string) $request->query('tier', '');
            $checkoutId = (string) $request->session()->get('paymongo_checkout.checkout_id', '');
            $sessionTenantId = (string) $request->session()->get('paymongo_checkout.tenant_id', '');
            $sessionTier = (string) $request->session()->get('paymongo_checkout.tier', '');

            if ($checkoutId !== '' && $tenantId !== '' && $tier !== '' && $tenantId === $sessionTenantId && $tier === $sessionTier) {
                $tenant = Tenant::query()->find($tenantId);

                if ($tenant) {
                    try {
                        $status = $this->payMongoCheckoutService->getCheckoutSessionStatus($checkoutId);

                        if ($status['is_paid'] === true) {
                            $this->activateTenantPlan($tenant, $tier, $status['checkout_id']);
                            $request->session()->forget('paymongo_checkout');
                            $activationMessage = 'Payment confirmed. Your subscription has been activated.';
                        }
                    } catch (Throwable) {
                        // Keep webhook as source of truth if direct status check is unavailable.
                    }
                }
            }
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route($this->postCheckoutRoute($request))
                ->with('billing_status', $activationMessage);
        }

        return response()->json([
            'message' => 'Checkout session completed. Subscription activation is finalized by PayMongo webhook.',
            'tenant_id' => $request->query('tenant'),
            'tier' => $request->query('tier'),
        ]);
    }

    public function cancel(Request $request): JsonResponse|RedirectResponse
    {
        $tenantId = (string) $request->query('tenant', '');
        $tenant = $tenantId !== '' ? Tenant::query()->find($tenantId) : null;

        if ($tenant && $this->canNotifyForTenant($request, $tenant)) {
            $this->notifier->notifyOwner(
                $tenant,
                'Checkout Cancelled',
                "Your checkout for tenant {$tenant->name} was cancelled. No payment was processed."
            );
        }

        if (! $request->expectsJson()) {
            return redirect()
                ->route($this->postCheckoutRoute($request))
                ->with('billing_error', 'Checkout cancelled. No payment was processed.');
        }

        return response()->json([
            'message' => 'Checkout cancelled.',
            'tenant_id' => $request->query('tenant'),
            'tier' => $request->query('tier'),
        ], 422);
    }

    private function checkoutForTier(Request $request, Tenant $tenant, string $tier)
    {
        $tenantAccessResponse = $this->authorizeTenantAccess($request, $tenant);

        if ($tenantAccessResponse !== null) {
            return $tenantAccessResponse;
        }

        if ($this->mustContactAdminForReactivation($tenant)) {
            $message = 'Your tenant already has an existing subscription but access is inactive/suspended. Please contact platform admin for reactivation.';

            if (! $request->expectsJson()) {
                return back()->with('billing_error', $message);
            }

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $plans = $this->plans();

        if (! isset($plans[$tier])) {
            if (! $request->expectsJson()) {
                return back()->with('billing_error', 'Invalid plan tier selected.');
            }

            return response()->json(['message' => 'Invalid plan tier.'], 422);
        }

        $plan = $plans[$tier];

        if (empty(config('services.paymongo.secret_key'))) {
            if (! $request->expectsJson()) {
                return back()->with('billing_error', 'PayMongo is not configured. Set PAYMONGO_SECRET_KEY in .env.');
            }

            return response()->json([
                'message' => 'PayMongo is not configured.',
                'expected_env' => ['PAYMONGO_SECRET_KEY'],
            ], 422);
        }

        $customerEmail = (string) ($request->input('customer_email') ?: ($request->user()?->email ?? ''));
        $customerEmail = $customerEmail !== '' ? $customerEmail : null;

        try {
            $successUrl = (string) $request->input('success_url', route('billing.success', [
                'tenant' => (string) $tenant->id,
                'tier' => $tier,
            ]));
            $cancelUrl = (string) $request->input('cancel_url', route('billing.cancel', [
                'tenant' => (string) $tenant->id,
                'tier' => $tier,
            ]));

            $checkout = $this->payMongoCheckoutService->createCheckoutSession(
                tenant: $tenant,
                planTier: $tier,
                amountPhp: $plan['amount_php'],
                successUrl: $successUrl,
                cancelUrl: $cancelUrl,
                customerEmail: $customerEmail,
            );

            $request->session()->put('paymongo_checkout', [
                'checkout_id' => (string) ($checkout['checkout_id'] ?? ''),
                'tenant_id' => (string) $tenant->id,
                'tier' => $tier,
            ]);

            if (! $request->expectsJson()) {
                return redirect()->away($checkout['checkout_url']);
            }

            return response()->json([
                'checkout_url' => $checkout['checkout_url'],
                'checkout_id' => $checkout['checkout_id'],
                'tenant_id' => (string) $tenant->id,
                'tier' => $tier,
            ]);
        } catch (Throwable $exception) {
            if (! $request->expectsJson()) {
                return back()->with('billing_error', 'Unable to start PayMongo checkout: '.$exception->getMessage());
            }

            return response()->json([
                'message' => 'Unable to start PayMongo checkout.',
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    private function activateTenantPlan(Tenant $tenant, string $planTier, string $externalId): void
    {
        if (! in_array($planTier, ['starter', 'professional', 'business', 'enterprise'], true)) {
            return;
        }

        if ($externalId === '') {
            return;
        }

        DB::transaction(function () use ($tenant, $planTier, $externalId): void {
            $wasActive = $tenant->status === 'active';
            $previousTier = (string) $tenant->plan_tier;

            $tenant->subscriptions()
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
                })
                ->where('stripe_id', '!=', $externalId)
                ->update([
                    'stripe_status' => 'canceled',
                    'ends_at' => now(),
                    'updated_at' => now(),
                ]);

            $tenant->subscriptions()->updateOrCreate(
                ['stripe_id' => $externalId],
                [
                    'type' => 'default',
                    'stripe_status' => 'active',
                    'stripe_price' => "paymongo_{$planTier}",
                    'quantity' => 1,
                    'trial_ends_at' => null,
                    'ends_at' => now()->addDays(30),
                ]
            );

            $tenant->forceFill([
                'plan_tier' => $planTier,
                'status' => 'active',
                'activated_at' => $tenant->activated_at ?? now(),
                'deactivated_at' => null,
            ])->save();

            if (! $wasActive) {
                $this->notifier->notifyOwner(
                    $tenant,
                    'Tenant Activated',
                    "Your tenant {$tenant->name} is now active after successful plan payment."
                );
            } elseif ($previousTier !== $planTier) {
                $this->notifier->notifyOwner(
                    $tenant,
                    'Plan Updated',
                    "Your tenant {$tenant->name} plan was updated to {$planTier}."
                );
            }

            $this->tenantLimitValidator->forgetTenantCounts((string) $tenant->id);
        });
    }

    /**
     * @return array<string, array{label:string, amount_php:int}>
     */
    private function plans(): array
    {
        return [
            'starter' => [
                'label' => 'Starter',
                'amount_php' => 499,
            ],
            'professional' => [
                'label' => 'Professional',
                'amount_php' => 1299,
            ],
            'business' => [
                'label' => 'Business',
                'amount_php' => 2499,
            ],
            'enterprise' => [
                'label' => 'Enterprise',
                'amount_php' => 4999,
            ],
        ];
    }

    private function postCheckoutRoute(Request $request): string
    {
        $user = $request->user();

        if ($user && $user->hasRole('Barbershop Admin')) {
            return 'billing.plans';
        }

        if ($user && $user->hasRole('Platform Admin')) {
            return 'admin.dashboard';
        }

        return 'manager.dashboard';
    }

    private function authorizeTenantAccess(Request $request, Tenant $tenant): JsonResponse|RedirectResponse|null
    {
        $user = $request->user();

        if ($user === null) {
            if (! $request->expectsJson()) {
                return redirect()->route('login');
            }

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->hasRole('Platform Admin')) {
            return null;
        }

        if ((string) ($user->tenant_id ?? '') === (string) $tenant->id) {
            return null;
        }

        if (! $request->expectsJson()) {
            return back()->with('billing_error', 'You are not allowed to manage billing for this tenant.');
        }

        return response()->json([
            'message' => 'You are not allowed to manage billing for this tenant.',
        ], 403);
    }

    private function canNotifyForTenant(Request $request, Tenant $tenant): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasRole('Platform Admin')) {
            return true;
        }

        return (string) ($user->tenant_id ?? '') === (string) $tenant->id;
    }

    private function mustContactAdminForReactivation(Tenant $tenant): bool
    {
        if (! in_array((string) ($tenant->status ?? 'pending'), ['inactive', 'suspended'], true)) {
            return false;
        }

        $subscription = $tenant->latestCashierSubscription;

        if (! $subscription) {
            return false;
        }

        return $subscription->ends_at === null || $subscription->ends_at->isFuture();
    }
}
