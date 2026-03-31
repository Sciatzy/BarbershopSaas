<?php

namespace App\Services;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TenantLimitValidator
{
    private const CACHE_TTL_MINUTES = 5;

    public function validateBranchCreation(Tenant|string $tenant): bool
    {
        $tenantModel = $this->resolveTenant($tenant);
        $limit = $this->branchLimit($tenantModel->plan_tier);

        if ($limit === null) {
            return true;
        }

        $count = $this->cachedBranchCount((string) $tenantModel->id);

        if ($count >= $limit) {
            $count = $this->refreshBranchCount((string) $tenantModel->id);

            if ($count >= $limit) {
                throw SubscriptionLimitExceededException::forBranches($tenantModel->plan_tier, $limit);
            }
        }

        return true;
    }

    public function validateBarberCreation(Tenant|string $tenant): bool
    {
        $tenantModel = $this->resolveTenant($tenant);
        $limit = $this->barberLimit($tenantModel->plan_tier);

        if ($limit === null) {
            return true;
        }

        $count = $this->cachedBarberCount((string) $tenantModel->id);

        if ($count >= $limit) {
            $count = $this->refreshBarberCount((string) $tenantModel->id);

            if ($count >= $limit) {
                throw SubscriptionLimitExceededException::forBarbers($tenantModel->plan_tier, $limit);
            }
        }

        return true;
    }

    public function ensureBarberLimitNotExceeded(Tenant|string $tenant): bool
    {
        $tenantModel = $this->resolveTenant($tenant);
        $limit = $this->barberLimit($tenantModel->plan_tier);

        if ($limit === null) {
            return true;
        }

        $count = $this->cachedBarberCount((string) $tenantModel->id);

        if ($count > $limit) {
            $count = $this->refreshBarberCount((string) $tenantModel->id);

            if ($count > $limit) {
                throw SubscriptionLimitExceededException::forBarbers($tenantModel->plan_tier, $limit);
            }
        }

        return true;
    }

    /**
     * @return array{branch_count:int, barber_count:int, branch_limit:int|null, barber_limit:int|null}
     */
    public function getTenantUsage(Tenant|string $tenant): array
    {
        $tenantModel = $this->resolveTenant($tenant);

        return [
            'branch_count' => $this->cachedBranchCount((string) $tenantModel->id),
            'barber_count' => $this->cachedBarberCount((string) $tenantModel->id),
            'branch_limit' => $this->branchLimit($tenantModel->plan_tier),
            'barber_limit' => $this->barberLimit($tenantModel->plan_tier),
        ];
    }

    public function forgetTenantCounts(string $tenantId): void
    {
        Cache::forget($this->branchCacheKey($tenantId));
        Cache::forget($this->barberCacheKey($tenantId));
    }

    private function resolveTenant(Tenant|string $tenant): Tenant
    {
        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        /** @var Tenant $tenantModel */
        $tenantModel = Tenant::withoutGlobalScopes()->findOrFail($tenant);

        return $tenantModel;
    }

    private function branchLimit(string $planTier): ?int
    {
        return match ($planTier) {
            'starter' => 1,
            'professional' => 1,
            'business' => 3,
            'enterprise' => null,
            default => 1,
        };
    }

    private function barberLimit(string $planTier): ?int
    {
        return match ($planTier) {
            'starter' => 2,
            'professional' => 5,
            'business' => null,
            'enterprise' => null,
            default => 2,
        };
    }

    private function cachedBranchCount(string $tenantId): int
    {
        return Cache::remember(
            $this->branchCacheKey($tenantId),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): int => Branch::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
        );
    }

    private function cachedBarberCount(string $tenantId): int
    {
        return Cache::remember(
            $this->barberCacheKey($tenantId),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): int => User::withoutGlobalScopes()->role('Barber')->where('tenant_id', $tenantId)->count(),
        );
    }

    private function refreshBranchCount(string $tenantId): int
    {
        $count = Branch::withoutGlobalScopes()->where('tenant_id', $tenantId)->count();

        Cache::put($this->branchCacheKey($tenantId), $count, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $count;
    }

    private function refreshBarberCount(string $tenantId): int
    {
        $count = User::withoutGlobalScopes()->role('Barber')->where('tenant_id', $tenantId)->count();

        Cache::put($this->barberCacheKey($tenantId), $count, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $count;
    }

    private function branchCacheKey(string $tenantId): string
    {
        return "tenant:{$tenantId}:limits:branches";
    }

    private function barberCacheKey(string $tenantId): string
    {
        return "tenant:{$tenantId}:limits:barbers";
    }
}
