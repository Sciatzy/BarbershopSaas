<?php

namespace App\Services;

use App\Models\BarberCashout;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BarberCashoutService
{
    /**
     * Tier format: points => amount_php
     */
    private const TIERS = [
        300 => 150,
        500 => 300,
        800 => 550,
        1200 => 900,
    ];

    /**
     * @return array<int, array{points:int, amount_php:float}>
     */
    public function tiers(): array
    {
        $tiers = [];

        foreach (self::TIERS as $points => $amountPhp) {
            $tiers[] = [
                'points' => (int) $points,
                'amount_php' => (float) $amountPhp,
            ];
        }

        usort($tiers, fn (array $a, array $b) => $a['points'] <=> $b['points']);

        return $tiers;
    }

    public function resolveAmountForPoints(int $points): ?float
    {
        $amount = Arr::get(self::TIERS, $points);

        return $amount === null ? null : (float) $amount;
    }

    public function requestCashout(User $barber, int $points, ?string $notes = null): bool
    {
        $amount = $this->resolveAmountForPoints($points);

        if ($points <= 0 || $amount === null) {
            return false;
        }

        return (bool) DB::transaction(function () use ($barber, $points, $amount, $notes): bool {
            /** @var User|null $lockedBarber */
            $lockedBarber = User::query()->withoutGlobalScopes()->lockForUpdate()->find($barber->id);

            if (! $lockedBarber) {
                return false;
            }

            $currentBalance = (int) DB::table('point_transactions')
                ->where('tenant_id', $lockedBarber->tenant_id)
                ->where('barber_id', $lockedBarber->id)
                ->sum('points_awarded');

            if ($currentBalance < $points) {
                return false;
            }

            BarberCashout::query()->create([
                'tenant_id' => $lockedBarber->tenant_id,
                'barber_id' => $lockedBarber->id,
                'branch_id' => $lockedBarber->branch_id,
                'points' => $points,
                'amount_php' => $amount,
                'status' => 'pending',
                'requested_by' => $lockedBarber->id,
                'notes' => $notes,
            ]);

            return true;
        });
    }

    public function approveCashout(BarberCashout $cashout, User $branchManager, PointsService $pointsService): bool
    {
        return (bool) DB::transaction(function () use ($cashout, $branchManager, $pointsService): bool {
            /** @var BarberCashout|null $lockedCashout */
            $lockedCashout = BarberCashout::query()->lockForUpdate()->find($cashout->id);

            if (! $lockedCashout || $lockedCashout->status !== 'pending') {
                return false;
            }

            if ((string) $lockedCashout->tenant_id !== (string) ($branchManager->tenant_id ?? '')) {
                return false;
            }

            if (empty($branchManager->branch_id) || (int) $lockedCashout->branch_id !== (int) $branchManager->branch_id) {
                return false;
            }

            /** @var User|null $barber */
            $barber = User::query()->withoutGlobalScopes()->find($lockedCashout->barber_id);

            if (! $barber) {
                return false;
            }

            $reason = 'Cash bonus approved (cashout #'.$lockedCashout->id.')';
            $deducted = $pointsService->adjustBarberPoints($barber, -((int) $lockedCashout->points), $reason);

            if (! $deducted) {
                return false;
            }

            $lockedCashout->status = 'approved';
            $lockedCashout->approved_by = $branchManager->id;
            $lockedCashout->approved_at = now();
            $lockedCashout->save();

            return true;
        });
    }

    public function rejectCashout(BarberCashout $cashout, User $branchManager, ?string $reason = null): bool
    {
        return (bool) DB::transaction(function () use ($cashout, $branchManager, $reason): bool {
            /** @var BarberCashout|null $lockedCashout */
            $lockedCashout = BarberCashout::query()->lockForUpdate()->find($cashout->id);

            if (! $lockedCashout || $lockedCashout->status !== 'pending') {
                return false;
            }

            if ((string) $lockedCashout->tenant_id !== (string) ($branchManager->tenant_id ?? '')) {
                return false;
            }

            if (empty($branchManager->branch_id) || (int) $lockedCashout->branch_id !== (int) $branchManager->branch_id) {
                return false;
            }

            $lockedCashout->status = 'rejected';
            $lockedCashout->approved_by = $branchManager->id;
            $lockedCashout->approved_at = now();
            $lockedCashout->rejection_reason = $reason;
            $lockedCashout->save();

            return true;
        });
    }

    public function markPaid(BarberCashout $cashout, User $branchManager): bool
    {
        return (bool) DB::transaction(function () use ($cashout, $branchManager): bool {
            /** @var BarberCashout|null $lockedCashout */
            $lockedCashout = BarberCashout::query()->lockForUpdate()->find($cashout->id);

            if (! $lockedCashout || $lockedCashout->status !== 'approved') {
                return false;
            }

            if ((string) $lockedCashout->tenant_id !== (string) ($branchManager->tenant_id ?? '')) {
                return false;
            }

            if (empty($branchManager->branch_id) || (int) $lockedCashout->branch_id !== (int) $branchManager->branch_id) {
                return false;
            }

            $lockedCashout->status = 'paid';
            $lockedCashout->paid_by = $branchManager->id;
            $lockedCashout->paid_at = now();
            $lockedCashout->save();

            return true;
        });
    }
}
