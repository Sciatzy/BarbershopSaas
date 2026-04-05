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

    /**
     * @param  array<string, string>  $details
     */
    public function notifyOwnerWithDetails(Tenant $tenant, string $subject, string $intro, array $details, ?string $footer = null): void
    {
        $owner = $this->resolveOwner($tenant);

        if (! $owner || $owner->email === '') {
            Log::warning('Detailed tenant owner notification skipped: owner not resolvable.', [
                'tenant_id' => $tenant->id,
                'subject' => $subject,
            ]);

            return;
        }

        $this->notifyUserWithDetails($owner, $subject, $intro, $details, $footer);
    }

    /**
     * @param  array<string, string>  $details
     */
    public function notifyUserWithDetails(User $user, string $subject, string $intro, array $details, ?string $footer = null): void
    {
        if ($user->email === '') {
            Log::warning('Detailed user notification skipped: user email is empty.', [
                'user_id' => $user->id,
                'subject' => $subject,
            ]);

            return;
        }

        $html = $this->buildProfessionalEmailHtml($subject, $intro, $details, $footer);

        try {
            Mail::html($html, function ($mail) use ($user, $subject): void {
                $mail->to($user->email, $user->name)->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::error('Detailed user notification failed to send.', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $details
     */
    private function buildProfessionalEmailHtml(string $title, string $intro, array $details, ?string $footer): string
    {
        $safeTitle = e($title);
        $safeIntro = nl2br(e($intro));
        $rows = '';

        foreach ($details as $label => $value) {
            $safeLabel = e($label);
            $safeValue = $this->formatDetailValue($value);
            $rows .= "<tr><td style=\"padding:8px 0;font-weight:600;color:#0f172a;vertical-align:top;width:180px;\">{$safeLabel}</td><td style=\"padding:8px 0;color:#1e293b;\">{$safeValue}</td></tr>";
        }

        $footerHtml = $footer ? '<p style="margin:18px 0 0 0;color:#475569;font-size:13px;">'.e($footer).'</p>' : '';

        return <<<HTML
<!doctype html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Segoe UI,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:18px 24px;background:#0f766e;color:#ffffff;font-size:18px;font-weight:700;">Barbershop Saas</td>
        </tr>
        <tr>
            <td style="padding:22px 24px;">
                <h2 style="margin:0 0 12px 0;color:#0f172a;font-size:20px;">{$safeTitle}</h2>
                <p style="margin:0 0 16px 0;color:#334155;font-size:14px;line-height:1.55;">{$safeIntro}</p>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;">
                    {$rows}
                </table>
                {$footerHtml}
                <p style="margin:18px 0 0 0;color:#64748b;font-size:12px;">This is an automated message from Barbershop Saas.</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    private function formatDetailValue(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed !== '' && filter_var($trimmed, FILTER_VALIDATE_URL)) {
            $safeHref = e($trimmed);
            $safeText = e($trimmed);

            return "<a href=\"{$safeHref}\" style=\"color:#0f766e;text-decoration:underline;\">{$safeText}</a>";
        }

        if (
            $trimmed !== ''
            && ! str_contains($trimmed, ' ')
            && str_contains($trimmed, '.')
            && preg_match('/^[a-z0-9.-]+$/i', $trimmed) === 1
        ) {
            $safeHref = e('http://'.$trimmed);
            $safeText = e($trimmed);

            return "<a href=\"{$safeHref}\" style=\"color:#0f766e;text-decoration:underline;\">{$safeText}</a>";
        }

        return nl2br(e($value));
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
