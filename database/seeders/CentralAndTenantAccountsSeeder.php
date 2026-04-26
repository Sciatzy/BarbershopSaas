<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CentralAndTenantAccountsSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password123';

    public function run(): void
    {
        $this->ensureRoles();

        $tenant = $this->upsertPrimaryTenant();
        $mainBranch = $this->upsertPrimaryBranch($tenant);

        $this->upsertUser(
            tenantId: null,
            branchId: null,
            name: 'Platform Admin',
            email: 'admin@platform.com',
            roleName: 'Platform Admin',
        );

        $tenantOwner = $this->upsertUser(
            tenantId: (string) $tenant->id,
            branchId: null,
            name: 'Barbershop Admin',
            email: 'manager@barbershop.test',
            roleName: 'Barbershop Admin',
        );

        $this->upsertUser(
            tenantId: (string) $tenant->id,
            branchId: (int) $mainBranch->id,
            name: 'Branch Manager',
            email: 'branchmanager@barbershop.test',
            roleName: 'Branch Manager',
        );

        $this->upsertUser(
            tenantId: (string) $tenant->id,
            branchId: (int) $mainBranch->id,
            name: 'Barber',
            email: 'barber@barbershop.test',
            roleName: 'Barber',
        );

        $this->upsertUser(
            tenantId: (string) $tenant->id,
            branchId: null,
            name: 'Customer',
            email: 'customer@barbershop.test',
            roleName: 'Customer',
            pointsBalance: 0,
        );

        $tenant->owner_user_id = $tenantOwner->id;
        $tenant->save();
    }

    private function ensureRoles(): void
    {
        $roles = [
            'Platform Admin',
            'Barbershop Admin',
            'Branch Manager',
            'Barber',
            'Customer',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }
    }

    private function upsertPrimaryTenant(): Tenant
    {
        $tenant = Tenant::query()
            ->withoutGlobalScopes()
            ->firstOrNew(['primary_domain' => 'barbershop.test']);

        if (empty($tenant->id)) {
            $tenant->id = (string) Str::uuid();
        }

        $tenant->forceFill([
            'name' => 'Barbershop Demo',
            'plan_tier' => 'professional',
            'status' => 'active',
            'database_name' => 'tenant_barbershop_demo',
            'database_provisioned_at' => Carbon::now(),
            'activated_at' => Carbon::now(),
            'brand_color' => '#C9A84C',
            'brand_color_secondary' => '#B54B2A',
            'customer_theme' => 'dark',
            'customer_font' => 'dm_sans',
            'customer_button_style' => 'rounded',
        ]);

        $tenant->save();

        return $tenant;
    }

    private function upsertPrimaryBranch(Tenant $tenant): Branch
    {
        $branch = Branch::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', (string) $tenant->id)
            ->where('name', 'Main Branch')
            ->first();

        if (! $branch) {
            $branch = new Branch();
            $branch->tenant_id = (string) $tenant->id;
            $branch->name = 'Main Branch';
        }

        $branch->address = '123 Main Street';
        $branch->save();

        return $branch;
    }

    private function upsertUser(
        ?string $tenantId,
        ?int $branchId,
        string $name,
        string $email,
        string $roleName,
        int $pointsBalance = 0,
    ): User {
        $query = User::query()
            ->withoutGlobalScopes()
            ->where('email', $email);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        $user = $query->first() ?? new User();

        $user->tenant_id = $tenantId;
        $user->branch_id = $branchId;
        $user->name = $name;
        $user->email = $email;
        $user->password = self::DEFAULT_PASSWORD;
        $user->email_verified_at = Carbon::now();
        $user->points_balance = $pointsBalance;
        $user->save();

        $user->syncRoles([$roleName]);

        return $user;
    }
}
