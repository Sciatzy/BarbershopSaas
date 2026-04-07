<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTenantController;
use App\Http\Controllers\BarberDashboardController;
use App\Http\Controllers\BarberManagementController;
use App\Http\Controllers\BillingPlansController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Manager\QueueController;
use App\Http\Controllers\Manager\ServiceController as ManagerServiceController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\ManagerSetupController;
use App\Http\Controllers\PayMongoWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WalkInWorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/welcome', [LandingController::class, 'show'])->name('public.landing');

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
});

Route::middleware(['auth', 'verified', 'role:Barbershop Admin|Branch Manager'])->group(function () {
    Route::get('/manager', [ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    Route::patch('/manager/domain', [ManagerDashboardController::class, 'updateDomain'])->name('manager.domain.update');
    Route::get('/manager/setup', [ManagerSetupController::class, 'create'])->name('manager.setup');
    Route::post('/manager/setup', [ManagerSetupController::class, 'store'])->name('manager.setup.store');
    Route::middleware('active_plan')->group(function () {
        Route::get('/manager/barbers', [BarberManagementController::class, 'index'])->name('manager.barbers.index');
        Route::post('/manager/barbers', [BarberManagementController::class, 'store'])->name('manager.barbers.store');
        Route::get('/manager/services', [ManagerServiceController::class, 'index'])->name('manager.services.index');
        Route::post('/manager/services', [ManagerServiceController::class, 'store'])->name('manager.services.store');
        Route::patch('/manager/services/{service}', [ManagerServiceController::class, 'update'])->name('manager.services.update');
        Route::get('/manager/queue', [QueueController::class, 'index'])->name('manager.queue.index');
        Route::post('/manager/queue/{booking}/status', [QueueController::class, 'updateStatus'])->name('manager.queue.status');
    });
});

Route::post('/manager/walk-ins', [WalkInWorkController::class, 'store'])
    ->middleware(['auth', 'verified', 'role:Barbershop Admin', 'active_plan'])
    ->name('manager.walkins.store');

Route::middleware(['auth', 'verified', 'role:Barber'])->group(function () {
    Route::middleware('active_plan')->group(function () {
        Route::get('/barber', [BarberDashboardController::class, 'index'])->name('barber.dashboard');
    });
});

Route::prefix('booking')->middleware(['auth', 'verified', 'role:Customer'])->group(function () {
    Route::middleware('active_plan')->group(function () {
        Route::get('/', [CustomerBookingController::class, 'index'])->name('booking.index');
        Route::get('/create', [CustomerBookingController::class, 'create'])->name('booking.create');
        Route::post('/', [CustomerBookingController::class, 'store'])->name('booking.store');
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


Route::prefix('customer')->name('customer.')->middleware(['auth', 'verified', 'role:Customer|Platform Admin|Barbershop Admin'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
});

Route::prefix('customer')->name('customer.')->middleware(['auth', 'verified', 'role:Customer'])->group(function () {
    Route::get('/services', [App\Http\Controllers\Customer\ServiceController::class, 'index'])->name('services');
    Route::get('/book/{service}', [App\Http\Controllers\Customer\BookingController::class, 'create'])->name('book');
    Route::post('/book', [App\Http\Controllers\Customer\BookingController::class, 'store'])->name('book.store');
    Route::get('/bookings', [App\Http\Controllers\Customer\BookingController::class, 'index'])->name('bookings');
    Route::delete('/bookings/{booking}/cancel', [App\Http\Controllers\Customer\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/points', [App\Http\Controllers\Customer\PointsController::class, 'index'])->name('points');
    Route::get('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [App\Http\Controllers\Customer\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/notifications', [App\Http\Controllers\Customer\NotificationController::class, 'index'])->name('notifications');
});


require __DIR__.'/auth.php';
