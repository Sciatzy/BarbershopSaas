<?php

namespace App\Exceptions;

use RuntimeException;

class SubscriptionLimitExceededException extends RuntimeException
{
    public static function forBranches(string $planTier, int $limit): self
    {
        return new self("The {$planTier} plan allows up to {$limit} branch(es). Upgrade your subscription to create more branches.");
    }

    public static function forBarbers(string $planTier, int $limit): self
    {
        return new self("The {$planTier} plan allows up to {$limit} barber account(s). Upgrade your subscription to add more staff.");
    }
}
