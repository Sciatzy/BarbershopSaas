<?php

namespace Tests\Feature;

use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantLimitValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_blocks_second_branch_creation(): void
    {
        $tenant = $this->createTenant('starter');

        $this->createBranch($tenant->id, 'Branch 1');

        $this->expectException(SubscriptionLimitExceededException::class);

        $this->createBranch($tenant->id, 'Branch 2');
    }

    public function test_professional_plan_blocks_sixth_barber_role_assignment(): void
    {
        $tenant = $this->createTenant('professional');
        $branch = $this->createBranch($tenant->id, 'Main Branch');

        Role::findOrCreate('Barber', 'web');

        for ($i = 1; $i <= 5; $i++) {
            $user = $this->createUser($tenant->id, $branch->id, "barber{$i}@example.com");
            $user->assignRole('Barber');
            $this->assertTrue($user->fresh()->hasRole('Barber'));
        }

        $sixthBarber = $this->createUser($tenant->id, $branch->id, 'barber6@example.com');

        try {
            $sixthBarber->assignRole('Barber');
            $this->fail('Expected SubscriptionLimitExceededException was not thrown.');
        } catch (SubscriptionLimitExceededException $exception) {
            $this->assertFalse($sixthBarber->fresh()->hasRole('Barber'));
        }
    }

    public function test_business_plan_allows_more_than_five_barbers_but_blocks_fourth_branch(): void
    {
        $tenant = $this->createTenant('business');

        $branch1 = $this->createBranch($tenant->id, 'Branch 1');
        $this->createBranch($tenant->id, 'Branch 2');
        $this->createBranch($tenant->id, 'Branch 3');

        Role::findOrCreate('Barber', 'web');

        for ($i = 1; $i <= 6; $i++) {
            $user = $this->createUser($tenant->id, $branch1->id, "bizbarber{$i}@example.com");
            $user->assignRole('Barber');
            $this->assertTrue($user->fresh()->hasRole('Barber'));
        }

        $this->expectException(SubscriptionLimitExceededException::class);

        $this->createBranch($tenant->id, 'Branch 4');
    }

    public function test_enterprise_plan_allows_unlimited_branches_and_barbers(): void
    {
        $tenant = $this->createTenant('enterprise');
        Role::findOrCreate('Barber', 'web');

        $lastBranch = null;

        for ($i = 1; $i <= 5; $i++) {
            $lastBranch = $this->createBranch($tenant->id, "Enterprise Branch {$i}");
        }

        for ($i = 1; $i <= 10; $i++) {
            $user = $this->createUser($tenant->id, $lastBranch->id, "entbarber{$i}@example.com");
            $user->assignRole('Barber');
            $this->assertTrue($user->fresh()->hasRole('Barber'));
        }

        $this->assertDatabaseCount('branches', 5);
    }

    private function createTenant(string $planTier): Tenant
    {
        $tenant = new Tenant();
        $tenant->id = (string) Str::uuid();
        $tenant->name = 'Tenant '.Str::random(6);
        $tenant->plan_tier = $planTier;
        $tenant->save();

        return $tenant;
    }

    private function createBranch(string $tenantId, string $name): Branch
    {
        $branch = new Branch();
        $branch->tenant_id = $tenantId;
        $branch->name = $name;
        $branch->address = 'Sample Address';
        $branch->save();

        return $branch;
    }

    private function createUser(string $tenantId, int $branchId, string $email): User
    {
        $user = new User();
        $user->tenant_id = $tenantId;
        $user->branch_id = $branchId;
        $user->name = 'User '.Str::random(4);
        $user->email = $email;
        $user->password = bcrypt('password');
        $user->save();

        return $user;
    }
}
