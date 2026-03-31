<?php

spl_autoload_register(static function (string $class): void {
    $prefixMap = [
        'Laravel\\Breeze\\' => __DIR__.'/../vendor/laravel/breeze/src/',
        'Laravel\\Cashier\\' => __DIR__.'/../vendor/laravel/cashier/src/',
        'Stripe\\' => __DIR__.'/../vendor/stripe/stripe-php/lib/',
        'Money\\' => __DIR__.'/../vendor/moneyphp/money/src/',
    ];

    foreach ($prefixMap as $prefix => $basePath) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $path = $basePath.str_replace('\\', '/', $relativeClass).'.php';

        if (is_file($path)) {
            require_once $path;
        }

        return;
    }
});

use App\Http\Middleware\TenantScopeMiddleware;
use App\Http\Middleware\EnsureTenantHasActivePlan;
use App\Http\Middleware\ResolveTenantFromDomain;
use App\Http\Middleware\SwitchTenantDatabase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active_plan' => EnsureTenantHasActivePlan::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'paymongo/webhook',
        ]);

        $middleware->append(ResolveTenantFromDomain::class);
        $middleware->append(SwitchTenantDatabase::class);
        $middleware->append(TenantScopeMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
