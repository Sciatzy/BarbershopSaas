<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TenantLifecycleNotifier
{
    public function notifyOwner(Tenant $tenant, string $subject, string $message): void
    {
        $owner = $this->resolveOwner($tenant);

        if (! $owner || $owner->email === '') {
            Log::warning('Tenant owner notification skipped: owner not resolvable.', [
                'tenant_id' => $tenant->id,
                'subject' => $subject,
            ]);
            return;
        }

        try {
            Mail::raw($message, function ($mail) use ($owner, $subject): void {
                $mail->to($owner->email, $owner->name)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::error('Tenant owner notification failed to send.', [
                'tenant_id' => $tenant->id,
                'owner_email' => $owner->email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function notifyUser(User $user, string $subject, string $message): void
    {
        if ($user->email === '') {
            Log::warning('User notification skipped: user email is empty.', [
                'user_id' => $user->id,
                'subject' => $subject,
            ]);

            return;
        }

        try {
            Mail::raw($message, function ($mail) use ($user, $subject): void {
                $mail->to($user->email, $user->name)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::error('User notification failed to send.', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveOwner(Tenant $tenant): ?User
    {
        if (! empty($tenant->owner_user_id)) {
            return User::query()->find($tenant->owner_user_id);
        }

        return User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->role('Barbershop Admin')
            ->orderBy('id')
            ->first()
            ?? User::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->orderBy('id')
                ->first();
    }
}
