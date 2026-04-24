<?php

namespace App\Http\Requests\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => Str::lower(trim((string) $this->input('email'))),
            'password' => trim((string) $this->input('password')),
        ];

        if ($this->isCentralDomainRequest() && $this->hasDuplicateCustomerAccountsAcrossTenants($credentials['email'])) {
            $shopUrls = $this->duplicateCustomerShopUrls($credentials['email']);
            $message = empty($shopUrls)
                ? 'This email is used by multiple customer accounts. Please log in using your shop URL.'
                : 'This email is used by multiple customer accounts. Please log in using one of your shop URLs: '.implode(', ', $shopUrls);

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function isCentralDomainRequest(): bool
    {
        $host = Str::lower((string) $this->getHost());
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        $centralDomains = array_map(
            static fn ($domain) => Str::lower((string) $domain),
            (array) config('tenancy.central_domains', [])
        );

        return in_array($host, $centralDomains, true);
    }

    private function hasDuplicateCustomerAccountsAcrossTenants(string $email): bool
    {
        $tenantCount = User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->role('Customer')
            ->whereNotNull('tenant_id')
            ->distinct('tenant_id')
            ->count('tenant_id');

        return $tenantCount > 1;
    }

    /**
     * @return array<int, string>
     */
    private function duplicateCustomerShopUrls(string $email): array
    {
        $tenantIds = User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->role('Customer')
            ->whereNotNull('tenant_id')
            ->distinct()
            ->pluck('tenant_id')
            ->filter()
            ->values();

        if ($tenantIds->count() <= 1) {
            return [];
        }

        $domains = Tenant::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $tenantIds->all())
            ->pluck('primary_domain')
            ->map(static fn ($domain) => Str::lower(trim((string) $domain)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $port = (int) $this->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':'.$port;

        $urls = array_map(
            static fn (string $domain) => 'http://'.$domain.$portSuffix,
            $domains,
        );

        sort($urls);

        return $urls;
    }
}
