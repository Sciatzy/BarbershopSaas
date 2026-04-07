<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->get(['id']);

        foreach ($tenants as $tenant) {
            $existingCount = Service::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->count();

            if ($existingCount > 0) {
                continue;
            }

            $services = [
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Classic Cut',
                    'description' => 'Clean classic haircut with finishing touches.',
                    'base_price' => 280,
                    'duration_min' => 30,
                    'is_active' => true,
                    'type' => 'standard',
                    'price' => 280,
                    'duration_minutes' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Skin Fade',
                    'description' => 'Precision skin fade with sharp blend and edge-up.',
                    'base_price' => 350,
                    'duration_min' => 45,
                    'is_active' => true,
                    'type' => 'premium',
                    'price' => 350,
                    'duration_minutes' => 45,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            Service::query()->withoutGlobalScopes()->insert($services);
        }
    }
}
