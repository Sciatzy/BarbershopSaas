<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupportTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owner_and_platform_admin_can_exchange_support_ticket_replies(): void
    {
        [$platformAdmin, $tenantOwner, $tenant] = $this->createUsersAndTenant();

        Storage::fake('public');

        $this->actingAs($tenantOwner)
            ->post(route('manager.support-tickets.store'), [
                'subject' => 'Queue board not loading',
                'category' => 'bug',
                'priority' => 'high',
                'description' => 'Queue board freezes after applying a filter and blocks branch operations.',
                'attachment' => UploadedFile::fake()->create('tenant-log.txt', 10, 'text/plain'),
            ])
            ->assertRedirect(route('manager.dashboard'));

        $ticket = SupportTicket::query()->where('tenant_id', $tenant->id)->first();

        $this->assertNotNull($ticket);
        $this->assertSame('open', $ticket->status);
        $this->assertNotEmpty($ticket->ticket_number);
        $this->assertDatabaseCount('support_ticket_messages', 1);
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'sender_role' => 'tenant_owner',
            'attachment_original_name' => 'tenant-log.txt',
        ]);

        $this->actingAs($platformAdmin)
            ->post(route('admin.support-tickets.reply', ['ticket' => $ticket->id]), [
                'message' => 'We reproduced this issue and are preparing a fix.',
                'status' => 'resolved',
                'attachment' => UploadedFile::fake()->create('fix-status.pdf', 24, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.dashboard'));

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertDatabaseCount('support_ticket_messages', 2);
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'sender_role' => 'platform_admin',
            'attachment_original_name' => 'fix-status.pdf',
        ]);

        $this->actingAs($tenantOwner)
            ->post(route('manager.support-tickets.reply', ['ticket' => $ticket->id]), [
                'message' => 'Issue persists in one branch. Reopening ticket.',
            ])
            ->assertRedirect(route('manager.dashboard'));

        $ticket->refresh();
        $this->assertSame('open', $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertDatabaseCount('support_ticket_messages', 3);

        $attachments = SupportTicketMessage::query()
            ->where('support_ticket_id', $ticket->id)
            ->whereNotNull('attachment_path')
            ->pluck('attachment_path');

        foreach ($attachments as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_platform_admin_can_filter_support_tickets_on_dashboard(): void
    {
        [$platformAdmin, $tenantOwner, $tenant] = $this->createUsersAndTenant();

        $matchingTicket = SupportTicket::query()->create([
            'tenant_id' => $tenant->id,
            'owner_user_id' => $tenantOwner->id,
            'subject' => 'Billing invoice mismatch',
            'category' => 'billing',
            'priority' => 'high',
            'status' => 'open',
            'description' => 'Invoice total and plan tier look inconsistent.',
            'latest_reply_at' => now(),
        ]);

        $nonMatchingTicket = SupportTicket::query()->create([
            'tenant_id' => $tenant->id,
            'owner_user_id' => $tenantOwner->id,
            'subject' => 'Queue board timeout',
            'category' => 'bug',
            'priority' => 'urgent',
            'status' => 'resolved',
            'description' => 'Queue board times out when loading appointment history.',
            'latest_reply_at' => now(),
        ]);

        $this->actingAs($platformAdmin)
            ->get(route('admin.dashboard', [
                'ticket_status' => 'open',
                'ticket_category' => 'billing',
                'ticket_priority' => 'high',
                'ticket_search' => 'invoice',
            ]))
            ->assertOk()
            ->assertSee($matchingTicket->ticket_number)
            ->assertDontSee($nonMatchingTicket->ticket_number);
    }

    /**
     * @return array{User, User, Tenant}
     */
    private function createUsersAndTenant(): array
    {
        Role::findOrCreate('Platform Admin', 'web');
        Role::findOrCreate('Barbershop Admin', 'web');

        $tenant = new Tenant();
        $tenant->id = (string) Str::uuid();
        $tenant->name = 'Tenant '.Str::random(5);
        $tenant->plan_tier = 'starter';
        $tenant->status = 'active';
        $tenant->activated_at = Carbon::now();
        $tenant->save();

        $tenantOwner = new User();
        $tenantOwner->tenant_id = $tenant->id;
        $tenantOwner->name = 'Tenant Owner';
        $tenantOwner->email = 'owner_'.Str::random(6).'@example.com';
        $tenantOwner->email_verified_at = Carbon::now();
        $tenantOwner->password = bcrypt('password123');
        $tenantOwner->save();
        $tenantOwner->assignRole('Barbershop Admin');

        $tenant->owner_user_id = $tenantOwner->id;
        $tenant->save();

        $platformAdmin = new User();
        $platformAdmin->tenant_id = null;
        $platformAdmin->name = 'Platform Admin';
        $platformAdmin->email = 'platform_'.Str::random(6).'@example.com';
        $platformAdmin->email_verified_at = Carbon::now();
        $platformAdmin->password = bcrypt('password123');
        $platformAdmin->save();
        $platformAdmin->assignRole('Platform Admin');

        return [$platformAdmin, $tenantOwner, $tenant];
    }
}
