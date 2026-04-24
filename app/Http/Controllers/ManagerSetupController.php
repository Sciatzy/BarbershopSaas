<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;

class ManagerSetupController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('Barbershop Admin') || !$user->tenant_id) {
            return redirect()->route('dashboard');
        }

        $tenant = Tenant::find($user->tenant_id);

        $plan = $request->session()->get('pending_setup_plan', $tenant?->plan_tier ?? 'starter');

        return view('manager.setup', [
            'tenant' => $tenant,
            'plan' => $plan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'primary_domain' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9-]+$/'
            ],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/'
            ],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'brand_color_secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'customer_theme' => ['nullable', Rule::in(['dark', 'light'])],
            'customer_font' => ['nullable', Rule::in(['dm_sans', 'poppins', 'space_grotesk', 'lora'])],
            'customer_button_style' => ['nullable', Rule::in(['rounded', 'pill', 'sharp'])],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        $tenant = Tenant::findOrFail($tenantId);

        $host = $this->resolvePlatformHost($request);
        $domainPathToSave = strtolower($request->primary_domain) . '.' . $host;

        if ($request->filled('custom_domain')) {
            $domainPathToSave = strtolower($request->custom_domain);
        }

        $logoPath = $tenant->logo_path;
        $heroImagePath = $tenant->hero_image_path;

        if ($request->hasFile('logo')) {
            if (! empty($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')->store('tenants/'.$tenant->id.'/branding', 'public');
        }

        if ($request->hasFile('hero_image')) {
            if (! empty($heroImagePath)) {
                Storage::disk('public')->delete($heroImagePath);
            }

            $heroImagePath = $request->file('hero_image')->store('tenants/'.$tenant->id.'/branding', 'public');
        }

        $tenant->update([
            'name' => $request->tenant_name,
            'primary_domain' => $domainPathToSave,
            'brand_color' => $request->input('brand_color') ?: '#C9A84C',
            'brand_color_secondary' => $request->input('brand_color_secondary') ?: '#B54B2A',
            'customer_theme' => $request->input('customer_theme') ?: 'dark',
            'customer_font' => $request->input('customer_font') ?: 'dm_sans',
            'customer_button_style' => $request->input('customer_button_style') ?: 'rounded',
            'logo_path' => $logoPath,
            'hero_image_path' => $heroImagePath,
        ]);

        if ($request->session()->has('pending_setup_plan')) {
            $plan = $request->session()->pull('pending_setup_plan');
            $request->session()->put('auto_checkout_plan', $plan);
        }

        return redirect()->route('billing.plans')->with('billing_status', 'Shop details saved. Please confirm your plan payment to activate your tenant.');
    }

    private function resolvePlatformHost(Request $request): string
    {
        $appHost = strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: ''));
        $requestHost = strtolower((string) $request->getHost());
        $host = $appHost !== '' ? $appHost : $requestHost;

        // Subdomains cannot reliably be created from raw IP hosts like 127.0.0.1.
        if (in_array($host, ['127.0.0.1', '::1'], true)) {
            return 'localhost';
        }

        return $host !== '' ? $host : 'localhost';
    }

    private function buildTenantUrl(Request $request, string $tenantHost, string $path): string
    {
        $scheme = $request->getScheme();
        $port = (int) $request->getPort();
        $portSegment = in_array($port, [80, 443], true) ? '' : ':' . $port;

        return sprintf('%s://%s%s%s', $scheme, $tenantHost, $portSegment, $path);
    }
}
