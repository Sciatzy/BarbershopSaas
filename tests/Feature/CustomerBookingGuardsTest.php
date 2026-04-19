<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerBookingGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_create_second_active_booking(): void
    {
        [$tenant, $branch, $service, $barber, $customer] = $this->createBookingContext();

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'barber_id' => $barber->id,
            'staff_id' => $barber->id,
            'service_id' => $service->id,
            'status' => 'queued',
            'booked_at' => now(),
            'appointment_datetime' => now()->addHour(),
            'source' => 'online',
            'created_by' => $customer->id,
        ]);

        $response = $this->actingAs($customer)->post(route('customer.book.store'), [
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'staff_id' => $barber->id,
            'notes' => 'Second attempt should be blocked',
        ]);

        $response->assertSessionHasErrors('booking');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_customer_cancellation_triggers_calendar_sync(): void
    {
        [$tenant, $branch, $service, $barber, $customer] = $this->createBookingContext();

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'barber_id' => $barber->id,
            'staff_id' => $barber->id,
            'service_id' => $service->id,
            'status' => 'queued',
            'booked_at' => now(),
            'appointment_datetime' => now()->addHour(),
            'source' => 'online',
            'created_by' => $customer->id,
        ]);

        $calendarSyncMock = Mockery::mock(GoogleCalendarSyncService::class);
        $calendarSyncMock
            ->shouldReceive('syncCancellation')
            ->once()
            ->withArgs(fn ($appointment): bool => (int) $appointment->id === (int) $booking->id);

        $this->app->instance(GoogleCalendarSyncService::class, $calendarSyncMock);

        $response = $this->actingAs($customer)->delete(route('customer.bookings.cancel', $booking));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Booking cancelled successfully.');

        $this->assertDatabaseHas('appointments', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * @return array{Tenant, Branch, Service, User, User}
     */
    private function createBookingContext(): array
    {
        Role::findOrCreate('Customer', 'web');
        Role::findOrCreate('Barber', 'web');

        $tenant = new Tenant();
        $tenant->id = (string) Str::uuid();
        $tenant->name = 'Tenant '.Str::random(6);
        $tenant->plan_tier = 'starter';
        $tenant->status = 'active';
        $tenant->save();

        $tenant->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'test_sub_'.Str::lower(Str::random(12)),
            'stripe_status' => 'active',
            'stripe_price' => 'test_price_starter',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $branch = new Branch();
        $branch->tenant_id = $tenant->id;
        $branch->name = 'Main Branch';
        $branch->address = 'Demo Address';
        $branch->save();

        $serviceId = DB::table('services')->insertGetId([
            'tenant_id' => $tenant->id,
            'name' => 'Haircut',
            'type' => 'standard',
            'duration_minutes' => 30,
            'base_price' => 250.00,
            'price' => 250.00,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = Service::query()->findOrFail($serviceId);

        $barber = new User();
        $barber->tenant_id = $tenant->id;
        $barber->branch_id = $branch->id;
        $barber->name = 'Barber Test';
        $barber->email = 'barber_'.Str::random(8).'@example.com';
        $barber->email_verified_at = Carbon::now();
        $barber->password = bcrypt('password123');
        $barber->save();
        $barber->assignRole('Barber');

        $customer = new User();
        $customer->tenant_id = $tenant->id;
        $customer->branch_id = $branch->id;
        $customer->name = 'Customer Test';
        $customer->email = 'customer_'.Str::random(8).'@example.com';
        $customer->email_verified_at = Carbon::now();
        $customer->password = bcrypt('password123');
        $customer->save();
        $customer->assignRole('Customer');

        return [$tenant, $branch, $service, $barber, $customer];
    }
}
