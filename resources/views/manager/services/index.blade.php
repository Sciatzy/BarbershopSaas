<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Service Catalog & Pricing</h2>
                <p class="text-sm text-slate-500 mt-1">Customize offered services and pricing for your tenant.</p>
            </div>
            <a href="{{ route('customer.dashboard') }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                Preview Customer Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('billing_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('billing_status') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Add New Service</h3>
                <form method="POST" action="{{ route('manager.services.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                    @csrf
                    <input type="text" name="name" placeholder="Service name" class="rounded-md border-slate-300" required>
                    <input type="number" step="0.01" min="0" name="base_price" placeholder="Price (PHP)" class="rounded-md border-slate-300" required>
                    <input type="number" min="5" max="600" name="duration_minutes" placeholder="Duration (mins)" class="rounded-md border-slate-300" required>
                    <input type="text" name="description" placeholder="Short description" class="rounded-md border-slate-300">
                    <button type="submit" class="rounded-md bg-amber-600 hover:bg-amber-500 text-white font-semibold px-4 py-2">Add Service</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800">Manage Services</h3>
                </div>

                <div class="p-6 space-y-4">
                    @forelse ($services as $service)
                        <form method="POST" action="{{ route('manager.services.update', ['service' => $service->id]) }}" class="grid grid-cols-1 md:grid-cols-7 gap-3 items-center rounded-xl border border-slate-200 p-4">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="name" value="{{ $service->name }}" class="rounded-md border-slate-300 md:col-span-2" required>
                            <input type="number" step="0.01" min="0" name="base_price" value="{{ $service->base_price ?? $service->price }}" class="rounded-md border-slate-300" required>
                            <input type="number" min="5" max="600" name="duration_minutes" value="{{ $service->duration_min ?? $service->duration_minutes }}" class="rounded-md border-slate-300" required>
                            <input type="text" name="description" value="{{ $service->description }}" class="rounded-md border-slate-300 md:col-span-2">
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center text-sm text-slate-600">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($service->is_active) class="rounded border-slate-300">
                                    <span class="ml-2">Active</span>
                                </label>
                                <button type="submit" class="rounded-md bg-slate-900 hover:bg-slate-800 text-white font-semibold px-3 py-2 text-sm">Save</button>
                            </div>
                        </form>
                    @empty
                        <p class="text-sm text-slate-500">No services configured yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
