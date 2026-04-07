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