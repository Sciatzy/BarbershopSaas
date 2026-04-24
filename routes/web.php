<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminSupportTicketController;
use App\Http\Controllers\AdminSystemReleaseController;
use App\Http\Controllers\AdminTenantController;
use App\Http\Controllers\BarberDashboardController;
use App\Http\Controllers\BarberManagementController;
use App\Http\Controllers\BillingPlansController;
use App\Http\Controllers\Manager\BranchController as ManagerBranchController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Manager\QueueController;
use App\Http\Controllers\Manager\ScheduleController as ManagerScheduleController;
use App\Http\Controllers\Manager\ServiceController as ManagerServiceController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\ManagerSetupController;
use App\Http\Controllers\ManagerSupportTicketController;
use App\Http\Controllers\ManagerSystemUpdateController;
use App\Http\Controllers\PayMongoWebhookController;
use App\Http\Controllers\SystemVersionController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WalkInWorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/welcome', [LandingController::class, 'show'])->name('public.landing');

Route::get('/system/version', SystemVersionController::class)->name('system.version');

Route::get('/booking/login-required', function () {
    return redirect()->route('login')->with('status', 'Please log in to reserve your spot.');
})->name('booking.login-required');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::match(['get', 'head'], '/paymongo/webhook', function () {
    return response('PayMongo webhook endpoint is online. Use POST for webhook deliveries.', 200);
})->name('paymongo.webhook.health');

Route::post('/paymongo/webhook', PayMongoWebhookController::class)->name('paymongo.webhook');

Route::middleware(['auth', 'verified', 'role:Platform Admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/customer-dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])
        ->name('admin.customer.dashboard');
    Route::post('/admin/system-releases/fetch-latest', [AdminSystemReleaseController::class, 'fetchLatest'])->name('admin.releases.fetch-latest');
    Route::post('/admin/system-releases/{release}/publish', [AdminSystemReleaseController::class, 'publish'])->name('admin.releases.publish');
    Route::patch('/admin/support-tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('admin.support-tickets.status');
    Route::post('/admin/support-tickets/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('admin.support-tickets.reply');
});

Route::middleware(['auth', 'verified', 'role:Barbershop Admin|Branch Manager'])->group(function () {
    Route::get('/manager', [ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    Route::middleware('active_plan')->group(function () {
        Route::get('/manager/barbers', [BarberManagementController::class, 'index'])->middleware('dashboard_access:branch_manager,manage_barbers')->name('manager.barbers.index');
        Route::patch('/manager/barbers/{barberId}/branch', [BarberManagementController::class, 'updateBranchAssignment'])->middleware('dashboard_access:branch_manager,manage_barbers')->name('manager.barbers.branch');
        Route::get('/manager/queue', [QueueController::class, 'index'])->middleware('dashboard_access:branch_manager,manage_queue')->name('manager.queue.index');
    });
});

Route::middleware(['auth', 'verified', 'role:Barbershop Admin'])->group(function () {
    Route::patch('/manager/dashboard-access', [ManagerDashboardController::class, 'updateDashboardAccess'])->name('manager.dashboard-access.update');
    Route::patch('/manager/domain', [ManagerDashboardController::class, 'updateDomain'])->name('manager.domain.update');
    Route::patch('/manager/appearance', [ManagerDashboardController::class, 'updateAppearance'])->name('manager.appearance.update');
    Route::post('/manager/support-tickets', [ManagerSupportTicketController::class, 'store'])->name('manager.support-tickets.store');
    Route::post('/manager/support-tickets/{ticket}/reply', [ManagerSupportTicketController::class, 'reply'])->name('manager.support-tickets.reply');
    Route::get('/manager/setup', [ManagerSetupController::class, 'create'])->name('manager.setup');
    Route::post('/manager/setup', [ManagerSetupController::class, 'store'])->name('manager.setup.store');
    Route::middleware('active_plan')->group(function () {
        Route::post('/manager/users', [ManagerDashboardController::class, 'storeManagedUser'])->name('manager.users.store');
        Route::patch('/manager/users/{userId}/password', [ManagerDashboardController::class, 'updateManagedUserPassword'])->name('manager.users.password');
        Route::delete('/manager/users/{userId}', [ManagerDashboardController::class, 'destroyManagedUser'])->name('manager.users.destroy');
        Route::post('/manager/barbers', [BarberManagementController::class, 'store'])->name('manager.barbers.store');
        Route::get('/manager/branches', [ManagerBranchController::class, 'index'])->name('manager.branches.index');
        Route::post('/manager/branches', [ManagerBranchController::class, 'store'])->name('manager.branches.store');
        Route::post('/manager/branches/{branch}/manager', [ManagerBranchController::class, 'assignManager'])->name('manager.branches.assign-manager');
        Route::patch('/manager/branches/{branch}', [ManagerBranchController::class, 'update'])->name('manager.branches.update');
        Route::delete('/manager/branches/{branch}', [ManagerBranchController::class, 'destroy'])->name('manager.branches.destroy');
    });
});

Route::middleware(['auth', 'verified', 'role:Barbershop Admin|Branch Manager', 'active_plan'])->group(function () {
    Route::post('/manager/system-updates/{tenantRelease}/apply', [ManagerSystemUpdateController::class, 'apply'])->name('manager.system-updates.apply');
    Route::post('/manager/system-updates/{tenantRelease}/hold', [ManagerSystemUpdateController::class, 'hold'])->name('manager.system-updates.hold');
});

Route::middleware(['auth', 'verified', 'role:Barbershop Admin|Branch Manager', 'active_plan', 'dashboard_access:branch_manager,manage_services'])->group(function () {
    Route::get('/manager/services', [ManagerServiceController::class, 'index'])->name('manager.services.index');
    Route::post('/manager/services', [ManagerServiceController::class, 'store'])->name('manager.services.store');
    Route::patch('/manager/services/{service}', [ManagerServiceController::class, 'update'])->name('manager.services.update');
    Route::delete('/manager/services/{service}', [ManagerServiceController::class, 'destroy'])->name('manager.services.destroy');
    Route::patch('/manager/services/{service}/restore', [ManagerServiceController::class, 'restore'])->name('manager.services.restore');
});

Route::middleware(['auth', 'verified', 'role:Branch Manager', 'active_plan'])->group(function () {
    Route::get('/manager/schedules', [ManagerScheduleController::class, 'index'])->middleware('dashboard_access:branch_manager,manage_schedules')->name('manager.schedules.index');
    Route::post('/manager/schedules', [ManagerScheduleController::class, 'store'])->middleware('dashboard_access:branch_manager,manage_schedules')->name('manager.schedules.store');
    Route::delete('/manager/schedules/{schedule}', [ManagerScheduleController::class, 'destroy'])->middleware('dashboard_access:branch_manager,manage_schedules')->name('manager.schedules.destroy');
    Route::post('/manager/queue/{booking}/status', [QueueController::class, 'updateStatus'])->middleware('dashboard_access:branch_manager,manage_queue')->name('manager.queue.status');
    Route::post('/manager/walk-ins', [WalkInWorkController::class, 'store'])->middleware('dashboard_access:branch_manager,record_walkins')->name('manager.walkins.store');
});

Route::middleware(['auth', 'verified', 'role:Barber'])->group(function () {
    Route::middleware('active_plan')->group(function () {
        Route::get('/barber', [BarberDashboardController::class, 'index'])->middleware('dashboard_access:barber,view_dashboard')->name('barber.dashboard');
        Route::post('/barber/appointments/{booking}/status', [BarberDashboardController::class, 'updateStatus'])->middleware('dashboard_access:barber,update_appointment_status')->name('barber.appointments.status');
    });
});

Route::middleware(['auth', 'verified', 'role:Customer'])->group(function () {
    Route::middleware('active_plan')->group(function () {
        // Legacy customer booking URLs now canonicalize to /customer/* pages.
        Route::get('/booking', static fn () => redirect()->route('customer.bookings'))->name('booking.index');
        Route::get('/booking/create', static fn () => redirect()->route('customer.bookings.create'))->name('booking.create');

        // Keep action endpoints for backward compatibility with existing forms.
        Route::post('/booking', [CustomerBookingController::class, 'store'])->name('booking.store');
        Route::delete('/booking/{booking}/cancel', [CustomerBookingController::class, 'cancel'])->name('booking.cancel');
        Route::patch('/booking/{booking}/reschedule', [CustomerBookingController::class, 'reschedule'])->name('booking.reschedule');
        Route::post('/booking/{booking}/feedback', [CustomerBookingController::class, 'submitFeedback'])->name('booking.feedback');
    });
});

Route::prefix('billing')->middleware(['auth', 'verified', 'role:Barbershop Admin'])->group(function () {
    Route::get('/plans', BillingPlansController::class)->name('billing.plans');
    Route::post('/{tenant}/checkout/starter', [SubscriptionController::class, 'checkoutStarter'])->name('billing.checkout.starter');
    Route::post('/{tenant}/checkout/professional', [SubscriptionController::class, 'checkoutProfessional'])->name('billing.checkout.professional');
    Route::post('/{tenant}/checkout/business', [SubscriptionController::class, 'checkoutBusiness'])->name('billing.checkout.business');
    Route::post('/{tenant}/checkout/enterprise', [SubscriptionController::class, 'checkoutEnterprise'])->name('billing.checkout.enterprise');
});

Route::post('/admin/tenants/{tenant}/suspend', [AdminDashboardController::class, 'suspend'])
    ->middleware(['auth', 'verified', 'role:Platform Admin'])
    ->name('admin.tenants.suspend');

Route::prefix('admin/tenants')->middleware(['auth', 'verified', 'role:Platform Admin'])->group(function () {
    Route::post('/', [AdminTenantController::class, 'store'])->name('admin.tenants.store');
    Route::patch('/{tenant}', [AdminTenantController::class, 'update'])->name('admin.tenants.update');
    Route::post('/{tenant}/resend-credentials', [AdminTenantController::class, 'resendCredentials'])->name('admin.tenants.resend-credentials');
    Route::post('/{tenant}/provision-database', [AdminTenantController::class, 'provisionDatabase'])->name('admin.tenants.provision-database');
});

Route::get('/billing/success', [SubscriptionController::class, 'success'])
    ->middleware(['auth', 'verified'])
    ->name('billing.success');

Route::get('/billing/cancel', [SubscriptionController::class, 'cancel'])
    ->middleware(['auth', 'verified'])
    ->name('billing.cancel');

Route::view('/billing/plan-required', 'billing.plan-required')
    ->middleware(['auth', 'verified'])
    ->name('billing.plan-required');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::prefix('customer')->name('customer.')->middleware(['auth', 'verified', 'role:Customer|Platform Admin'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('customer')->name('customer.')->middleware(['auth', 'verified', 'role:Customer'])->group(function () {
    Route::middleware('active_plan')->group(function () {
        Route::get('/services', [App\Http\Controllers\Customer\ServiceController::class, 'index'])->name('services');
        Route::get('/bookings/create', [App\Http\Controllers\Customer\BookingController::class, 'create'])->name('bookings.create');
        Route::get('/book/{service}', [App\Http\Controllers\Customer\BookingController::class, 'create'])->name('book');
        Route::post('/book', [App\Http\Controllers\Customer\BookingController::class, 'store'])->name('book.store');
        Route::get('/bookings', [App\Http\Controllers\Customer\BookingController::class, 'index'])->name('bookings');
        Route::delete('/bookings/{booking}/cancel', [App\Http\Controllers\Customer\BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::patch('/bookings/{booking}/reschedule', [App\Http\Controllers\Customer\BookingController::class, 'reschedule'])->name('bookings.reschedule');
        Route::post('/bookings/{booking}/feedback', [App\Http\Controllers\Customer\BookingController::class, 'submitFeedback'])->name('bookings.feedback');
    });
    Route::get('/points', [App\Http\Controllers\Customer\PointsController::class, 'index'])->name('points');
    Route::get('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/notifications', [App\Http\Controllers\Customer\NotificationController::class, 'index'])->name('notifications');
});


require __DIR__.'/auth.php';
