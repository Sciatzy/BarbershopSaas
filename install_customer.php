<?php

$routes = <<<'ROUTES'

Route::prefix('customer')->name('customer.')->middleware(['auth', 'verified', 'role:Customer'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
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

ROUTES;

$webphp = file_get_contents('routes/web.php');
if (strpos($webphp, 'Route::prefix(\'customer\')') === false) {
    if (strpos($webphp, 'require __DIR__.\'/auth.php\';') !== false) {
        $webphp = str_replace('require __DIR__.\'/auth.php\';', $routes . "\n\nrequire __DIR__.'/auth.php';", $webphp);
    } else {
        $webphp .= "\n" . $routes;
    }
    file_put_contents('routes/web.php', $webphp);
}

// Write ServiceController
file_put_contents('app/Http/Controllers/Customer/ServiceController.php', <<<'CODE'
<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $tenant   = auth()->user()->tenant;
        $services = Service::where('tenant_id', $tenant->id ?? null)
            ->where('is_active', true)
            ->get();
        return view('customer.services.index', compact('services', 'tenant'));
    }
}
CODE
);

// Write PointsController
file_put_contents('app/Http/Controllers/Customer/PointsController.php', <<<'CODE'
<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PointsLedger;

class PointsController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $balance = $user->points_balance ?? 0;

        $ledger  = class_exists(PointsLedger::class) ? PointsLedger::where('customer_id', $user->id)
            ->with(['booking.service']) // Eager load depending on relations
            ->latest()
            ->paginate(15) : collect();

        // Milestones: define redemption thresholds
        $milestones = [
            ['points' => 300,  'reward' => 'Free Beard Lineup'],
            ['points' => 500,  'reward' => 'Free Classic Cut'],
            ['points' => 800,  'reward' => 'Free Skin Fade'],
            ['points' => 1200, 'reward' => 'Free Cut + Beard Combo'],
        ];

        return view('customer.points.index', compact('balance', 'ledger', 'milestones'));
    }
}
CODE
);

// Write ProfileController
file_put_contents('app/Http/Controllers/Customer/ProfileController.php', <<<'CODE'
<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('customer.profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', Rule::unique('users')->ignore(auth()->id())],
        ]);

        auth()->user()->update($validated);

        return redirect()->route('customer.profile')->with('success', 'Profile updated.');
    }
}
CODE
);

// Write NotificationController scaffold
file_put_contents('app/Http/Controllers/Customer/NotificationController.php', <<<'CODE'
<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
class NotificationController extends Controller
{
    public function index() { return view('customer.dashboard')->with('success', 'No new notifications'); }
}
CODE
);

