<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTenantController;
use App\Http\Controllers\BarberDashboardController;
use App\Http\Controllers\BarberManagementController;
use App\Http\Controllers\BillingPlansController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
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

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::match(['get', 'head'], '/paymongo/webhook', function () {
    return response('PayMongo webhook endpoint is online. Use POST for webhook deliveries.', 200);
})->name('paymongo.webhook.health');

Route::post('/paymongo/webhook', PayMongoWebhookController::class)->name('paymongo.webhook');

Route::middleware(['auth', 'verified', 'role:Platform Admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware(['auth', 'verified', 'role:Barbershop Admin|Branch Manager'])->group(function () {
    Route::get('/manager', [ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    Route::get('/manager/setup', [ManagerSetupController::class, 'create'])->name('manager.setup');
    Route::post('/manager/setup', [ManagerSetupController::class, 'store'])->name('manager.setup.store');
    Route::middleware('active_plan')->group(function () {
        Route::get('/manager/barbers', [BarberManagementController::class, 'index'])->name('manager.barbers.index');
        Route::post('/manager/barbers', [BarberManagementController::class, 'store'])->name('manager.barbers.store');
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
        Route::get('/', [BookingController::class, 'index'])->name('booking.index');
        Route::post('/', [BookingController::class, 'store'])->name('booking.store');
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

require __DIR__.'/auth.php';
