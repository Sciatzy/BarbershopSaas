<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenantHasActivePlan;
use App\Models\SystemRelease;
use App\Models\Tenant;
use App\Models\TenantSystemRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemReleaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_publish_release_to_plan_tier_cohort(): void
    {
        Role::findOrCreate('Platform Admin', 'web');
        Role::findOrCreate('Barbershop Admin', 'web');

        $platformAdmin = $this->createUser('Platform Admin', null, 'platform-admin@example.com');

        $starterTenant = $this->createTenant('starter', 'active');
        $professionalTenant = $this->createTenant('professional', 'active');

        $release = SystemRelease::query()->create([
            'version' => 'v2.3.0',
            'display_version' => 'v2.3.0',
            'publication_status' => 'draft',
            'source' => 'test-suite',
            'branch' => 'main',
            'commit_hash' => Str::random(40),
            'short_commit' => Str::random(7),
            'synced_at' => now(),
            'synced_by_user_id' => $platformAdmin->id,
        ]);

        $this->actingAs($platformAdmin)
            ->post(route('admin.releases.publish', ['release' => $release->id]), [
                'cohort_mode' => 'plan_tier',
                'cohort_plan_tier' => 'starter',
                'release_notes' => 'Starter rollout first for validation.',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $release->refresh();

        $this->assertSame('published', $release->publication_status);
        $this->assertNotNull($release->published_at);

        $this->assertDatabaseHas('tenant_system_release_states', [
            'tenant_id' => $starterTenant->id,
            'system_release_id' => $release->id,
            'state' => 'pending',
        ]);

        $this->assertDatabaseMissing('tenant_system_release_states', [
            'tenant_id' => $professionalTenant->id,
            'system_release_id' => $release->id,
        ]);
    }

    public function test_tenant_owner_can_hold_then_apply_published_release(): void
    {
        Role::findOrCreate('Barbershop Admin', 'web');

        $tenant = $this->createTenant('starter', 'active');
        $owner = $this->createUser('Barbershop Admin', $tenant->id, 'owner@example.com');

        $tenant->owner_user_id = $owner->id;
        $tenant->save();

        $release = SystemRelease::query()->create([
            'version' => 'v2.3.1',
            'display_version' => 'v2.3.1',
            'publication_status' => 'published',
            'source' => 'test-suite',
            'published_at' => now(),
        ]);

        $tenantRelease = TenantSystemRelease::query()->create([
            'tenant_id' => $tenant->id,
            'system_release_id' => $release->id,
            'state' => 'pending',
            'available_at' => now(),
        ]);

        $this->withoutMiddleware(EnsureTenantHasActivePlan::class);

        $this->actingAs($owner)
            ->post(route('manager.system-updates.hold', ['tenantRelease' => $tenantRelease->id]), [
                'hold_note' => 'Hold until after Saturday closing.',
            ])
            ->assertRedirect(route('manager.dashboard'));

        $tenantRelease->refresh();
        $this->assertSame('held', $tenantRelease->state);
        $this->assertSame('Hold until after Saturday closing.', $tenantRelease->hold_note);

        $this->actingAs($owner)
            ->post(route('manager.system-updates.apply', ['tenantRelease' => $tenantRelease->id]))
            ->assertRedirect(route('manager.dashboard'));

        $tenantRelease->refresh();
        $tenant->refresh();

        $this->assertSame('applied', $tenantRelease->state);
        $this->assertNotNull($tenantRelease->applied_at);
        $this->assertSame($owner->id, $tenantRelease->applied_by_user_id);
        $this->assertSame('v2.3.1', $tenant->applied_system_version);
        $this->assertNotNull($tenant->applied_system_version_at);
    }

    private function createTenant(string $planTier, string $status): Tenant
    {
        $tenant = new Tenant();
        $tenant->id = (string) Str::uuid();
        $tenant->name = 'Tenant '.Str::upper(Str::random(4));
        $tenant->plan_tier = $planTier;
        $tenant->status = $status;
        $tenant->activated_at = Carbon::now();
        $tenant->save();

        return $tenant;
    }

    private function createUser(string $role, ?string $tenantId, string $email): User
    {
        $user = new User();
        $user->tenant_id = $tenantId;
        $user->name = Str::title(str_replace(['-', '@example.com'], [' ', ''], $email));
        $user->email = $email;
        $user->password = bcrypt('password123');
        $user->email_verified_at = Carbon::now();
        $user->save();

        $user->assignRole($role);

        return $user;
    }
}
