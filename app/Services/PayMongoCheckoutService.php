<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoCheckoutService
{
    /**
     * @return array{checkout_url:string, checkout_id:string|null, raw:array}
     */
    public function createCheckoutSession(
        Tenant $tenant,
        string $planTier,
        int $amountPhp,
        string $successUrl,
        string $cancelUrl,
        ?string $customerEmail = null,
    ): array {
        $secretKey = (string) config('services.paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured. Set PAYMONGO_SECRET_KEY in .env.');
        }

        $paymentMethodTypes = config('services.paymongo.payment_method_types', []);

        if (! is_array($paymentMethodTypes) || $paymentMethodTypes === []) {
            $paymentMethodTypes = ['gcash', 'paymaya', 'grab_pay', 'card'];
        }

        $attributes = [
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => $amountPhp * 100,
                'name' => ucfirst($planTier).' Plan',
                'quantity' => 1,
                'description' => "Barbershop SaaS {$planTier} monthly plan",
            ]],
            'payment_method_types' => array_values($paymentMethodTypes),
            'description' => "Barbershop SaaS {$planTier} monthly plan",
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'show_description' => true,
            'show_line_items' => true,
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'plan_tier' => $planTier,
                'plan_amount_php' => (string) $amountPhp,
            ],
        ];

        if ($customerEmail !== null && $customerEmail !== '') {
            $attributes['billing'] = [
                'email' => $customerEmail,
            ];

            $attributes['send_email_receipt'] = true;
        }

        $response = Http::asJson()
            ->acceptJson()
            ->withBasicAuth($secretKey, '')
            ->post($this->apiBase().'/checkout_sessions', [
                'data' => [
                    'attributes' => $attributes,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException($this->buildErrorMessage($response->json(), $response->status()));
        }

        $json = $response->json();

        $checkoutUrl = (string) (
            data_get($json, 'data.attributes.checkout_url')
            ?: data_get($json, 'data.attributes.url')
        );

        if ($checkoutUrl === '') {
            throw new RuntimeException('PayMongo response is missing a checkout URL.');
        }

        $checkoutId = data_get($json, 'data.id');

        return [
            'checkout_url' => $checkoutUrl,
            'checkout_id' => is_string($checkoutId) ? $checkoutId : null,
            'raw' => is_array($json) ? $json : [],
        ];
    }

    /**
     * @return array{checkout_id:string, is_paid:bool, raw:array}
     */
    public function getCheckoutSessionStatus(string $checkoutId): array
    {
        $secretKey = (string) config('services.paymongo.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured. Set PAYMONGO_SECRET_KEY in .env.');
        }

        $response = Http::acceptJson()
            ->withBasicAuth($secretKey, '')
            ->get($this->apiBase().'/checkout_sessions/'.$checkoutId);

        if ($response->failed()) {
            throw new RuntimeException($this->buildErrorMessage($response->json(), $response->status()));
        }

        $json = $response->json();

        $statusCandidates = [
            (string) data_get($json, 'data.attributes.status', ''),
            (string) data_get($json, 'data.attributes.payment_intent.attributes.status', ''),
            (string) data_get($json, 'data.attributes.payments.0.attributes.status', ''),
        ];

        $isPaid = collect($statusCandidates)
            ->filter(fn (string $value): bool => $value !== '')
            ->contains(fn (string $value): bool => in_array(strtolower($value), ['paid', 'succeeded'], true));

        return [
            'checkout_id' => (string) (data_get($json, 'data.id') ?: $checkoutId),
            'is_paid' => $isPaid,
            'raw' => is_array($json) ? $json : [],
        ];
    }

    private function apiBase(): string
    {
        $apiBase = (string) config('services.paymongo.api_base', 'https://api.paymongo.com/v1');

        return rtrim($apiBase, '/');
    }

    private function buildErrorMessage(mixed $json, int $status): string
    {
        $errorDetail = is_array($json)
            ? (string) (data_get($json, 'errors.0.detail') ?: data_get($json, 'errors.0.code'))
            : '';

        if ($errorDetail === '') {
            return "PayMongo checkout request failed with HTTP {$status}.";
        }

        return "PayMongo checkout request failed: {$errorDetail}";
    }
}