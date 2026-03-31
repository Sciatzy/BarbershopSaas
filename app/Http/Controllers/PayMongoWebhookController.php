<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantLifecycleNotifier;
use App\Services\TenantLimitValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PayMongoWebhookController extends Controller
{
    public function __construct(
        private TenantLimitValidator $tenantLimitValidator,
        private TenantLifecycleNotifier $notifier,
    ) {}

    public function __invoke(Request $request): Response
    {
        if (! $this->isAuthorized($request)) {
            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $eventType = (string) data_get($payload, 'data.attributes.type', '');

        if (! $this->isSuccessfulEvent($eventType)) {
            return response('Event ignored', 200);
        }

        $metadata = $this->extractMetadata($payload);
        $tenantId = (string) ($metadata['tenant_id'] ?? '');
        $planTier = (string) ($metadata['plan_tier'] ?? '');

        if ($tenantId === '' || ! $this->isSupportedPlanTier($planTier)) {
            Log::warning('PayMongo webhook missing valid tenant metadata.', [
                'event_type' => $eventType,
                'payload_id' => data_get($payload, 'data.id'),
            ]);

            return response('Missing tenant metadata', 200);
        }

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant) {
            Log::warning('PayMongo webhook tenant not found.', [
                'event_type' => $eventType,
                'tenant_id' => $tenantId,
                'payload_id' => data_get($payload, 'data.id'),
            ]);

            return response('Tenant not found', 200);
        }

        $externalId = $this->resolveExternalId($payload);

        if ($externalId === '') {
            $externalId = 'pm_evt_'.str_replace('-', '', (string) Str::uuid());
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

        return response('OK', 200);
    }

    private function isAuthorized(Request $request): bool
    {
        $webhookToken = (string) config('services.paymongo.webhook.token', '');

        if ($webhookToken === '') {
            return true;
        }

        return hash_equals($webhookToken, (string) $request->query('token', ''));
    }

    private function isSuccessfulEvent(string $eventType): bool
    {
        return in_array($eventType, [
            'checkout_session.payment.paid',
            'payment.paid',
            'checkout.session.completed',
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractMetadata(array $payload): array
    {
        $metadataPaths = [
            'data.attributes.data.attributes.metadata',
            'data.attributes.data.attributes.checkout_session.attributes.metadata',
            'data.attributes.data.attributes.payment_intent.attributes.metadata',
            'data.attributes.metadata',
        ];

        foreach ($metadataPaths as $path) {
            $metadata = data_get($payload, $path);

            if (is_array($metadata) && $metadata !== []) {
                return $metadata;
            }
        }

        return [];
    }

    private function resolveExternalId(array $payload): string
    {
        $idPaths = [
            'data.attributes.data.id',
            'data.attributes.data.attributes.id',
            'data.id',
        ];

        foreach ($idPaths as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function isSupportedPlanTier(string $planTier): bool
    {
        return in_array($planTier, ['starter', 'professional', 'business', 'enterprise'], true);
    }
}