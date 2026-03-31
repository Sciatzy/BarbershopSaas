<?php

namespace Tests\Feature;

use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BarberPointSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_awards_standard_service_points_when_appointment_is_completed(): void
    {
        [$tenantId, $branchId, $barberId, $customerId] = $this->createTenantBranchAndUsers();
        $serviceId = $this->createService($tenantId, 'standard');

        $appointment = $this->createAppointment(
            $tenantId,
            $branchId,
            $customerId,
            $barberId,
            $serviceId,
            'pending',
            false,
            null,
        );

        $appointment->status = 'completed';
        $appointment->save();

        $this->assertDatabaseHas('point_transactions', [
            'tenant_id' => $tenantId,
            'barber_id' => $barberId,
            'appointment_id' => $appointment->id,
            'points_awarded' => 10,
            'reason' => 'Service Points: Standard',
        ]);
    }

    public function test_it_awards_premium_service_points_when_appointment_is_completed(): void
    {
        [$tenantId, $branchId, $barberId, $customerId] = $this->createTenantBranchAndUsers();
        $serviceId = $this->createService($tenantId, 'premium');

        $appointment = $this->createAppointment(
            $tenantId,
            $branchId,
            $customerId,
            $barberId,
            $serviceId,
            'pending',
            false,
            null,
        );

        $appointment->status = 'completed';
        $appointment->save();

        $this->assertDatabaseHas('point_transactions', [
            'tenant_id' => $tenantId,
            'barber_id' => $barberId,
            'appointment_id' => $appointment->id,
            'points_awarded' => 15,
            'reason' => 'Service Points: Premium',
        ]);
    }

    public function test_it_awards_punctuality_points_when_appointment_is_on_time(): void
    {
        [$tenantId, $branchId, $barberId, $customerId] = $this->createTenantBranchAndUsers();
        $serviceId = $this->createService($tenantId, 'standard');

        $appointment = $this->createAppointment(
            $tenantId,
            $branchId,
            $customerId,
            $barberId,
            $serviceId,
            'pending',
            true,
            null,
        );

        $appointment->status = 'completed';
        $appointment->save();

        $this->assertDatabaseHas('point_transactions', [
            'tenant_id' => $tenantId,
            'barber_id' => $barberId,
            'appointment_id' => $appointment->id,
            'points_awarded' => 5,
            'reason' => 'Punctuality Points',
        ]);
    }

    public function test_it_awards_rating_points_for_five_star_review(): void
    {
        [$tenantId, $branchId, $barberId, $customerId] = $this->createTenantBranchAndUsers();
        $serviceId = $this->createService($tenantId, 'standard');

        $appointment = $this->createAppointment(
            $tenantId,
            $branchId,
            $customerId,
            $barberId,
            $serviceId,
            'pending',
            false,
            null,
        );

        $appointment->customer_rating = 5;
        $appointment->save();

        $this->assertDatabaseHas('point_transactions', [
            'tenant_id' => $tenantId,
            'barber_id' => $barberId,
            'appointment_id' => $appointment->id,
            'points_awarded' => 20,
            'reason' => 'Rating Points: 5-Star Review',
        ]);
    }

    public function test_it_awards_rebooking_points_when_customer_rebooks_same_barber_after_previous_completed_appointment(): void
    {
        [$tenantId, $branchId, $barberId, $customerId] = $this->createTenantBranchAndUsers();
        $serviceId = $this->createService($tenantId, 'standard');

        $this->createAppointment(
            $tenantId,
            $branchId,
            $customerId,
            $barberId,
            $serviceId,
            'completed',
            false,
            null,
        );

        $rebookedAppointment = $this->createAppointment(
            $tenantId,
            $branchId,
            $customerId,
            $barberId,
            $serviceId,
            'pending',
            false,
            null,
        );

        $this->assertDatabaseHas('point_transactions', [
            'tenant_id' => $tenantId,
            'barber_id' => $barberId,
            'appointment_id' => $rebookedAppointment->id,
            'points_awarded' => 25,
            'reason' => 'Rebooking Points',
        ]);
    }

    /**
     * @return array{string, int, int, int}
     */
    private function createTenantBranchAndUsers(): array
    {
        $tenantId = (string) Str::uuid();

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.Str::random(6),
            'plan_tier' => 'starter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Main Branch',
            'address' => '123 Demo Street',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $barberId = DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'name' => 'Barber '.Str::random(4),
            'email' => 'barber_'.Str::random(12).'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'name' => 'Customer '.Str::random(4),
            'email' => 'customer_'.Str::random(12).'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $branchId, $barberId, $customerId];
    }

    private function createService(string $tenantId, string $type): int
    {
        return DB::table('services')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => ucfirst($type).' Service',
            'type' => $type,
            'price' => 299.00,
            'duration_minutes' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAppointment(
        string $tenantId,
        int $branchId,
        int $customerId,
        int $barberId,
        int $serviceId,
        string $status,
        bool $isOnTime,
        ?int $customerRating,
    ): Appointment {
        $appointment = new Appointment();
        $appointment->tenant_id = $tenantId;
        $appointment->branch_id = $branchId;
        $appointment->customer_id = $customerId;
        $appointment->barber_id = $barberId;
        $appointment->service_id = $serviceId;
        $appointment->appointment_datetime = now()->addDay();
        $appointment->status = $status;
        $appointment->is_on_time = $isOnTime;
        $appointment->customer_rating = $customerRating;
        $appointment->save();

        return $appointment;
    }
}
