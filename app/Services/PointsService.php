<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointsService
{
    public function awardPoints(Booking $booking): int
    {
        $price = (float) ($booking->total_price ?? 0);
        $points = (int) floor($price / 50);

        if ($points <= 0) {
            return 0;
        }

        $alreadyAwarded = DB::table('points_ledger')
            ->where('type', 'earn')
            ->where('booking_id', $booking->id)
            ->exists();

        if ($alreadyAwarded) {
            return 0;
        }

        return DB::transaction(function () use ($booking, $points): int {
            /** @var User|null $customer */
            $customer = User::query()->withoutGlobalScopes()->lockForUpdate()->find($booking->customer_id);

            if (! $customer) {
                return 0;
            }

            $newBalance = (int) ($customer->points_balance ?? 0) + $points;

            $customer->forceFill(['points_balance' => $newBalance])->save();

            DB::table('points_ledger')->insert([
                'tenant_id' => $booking->tenant_id,
                'customer_id' => $customer->id,
                'booking_id' => $booking->id,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $newBalance,
                'notes' => 'Earned from booking #'.$booking->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $points;
        });
    }

    public function redeemPoints(User $customer, int $points, ?int $bookingId = null): bool
    {
        if ($points <= 0) {
            return false;
        }

        return (bool) DB::transaction(function () use ($customer, $points, $bookingId): bool {
            /** @var User|null $lockedCustomer */
            $lockedCustomer = User::query()->withoutGlobalScopes()->lockForUpdate()->find($customer->id);

            if (! $lockedCustomer) {
                return false;
            }

            $currentBalance = (int) ($lockedCustomer->points_balance ?? 0);

            if ($currentBalance < $points) {
                return false;
            }

            $newBalance = $currentBalance - $points;
            $lockedCustomer->forceFill(['points_balance' => $newBalance])->save();

            DB::table('points_ledger')->insert([
                'tenant_id' => $lockedCustomer->tenant_id,
                'customer_id' => $lockedCustomer->id,
                'booking_id' => $bookingId,
                'type' => 'redeem',
                'points' => -$points,
                'balance_after' => $newBalance,
                'notes' => 'Redeemed points',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });
    }
}
