<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Service Catalog & Pricing</h2>
                <p class="text-sm text-slate-500 mt-1">Customize offered services and pricing for your tenant.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('billing_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('billing_status') }}
                </div>
            @endif

            @if (session('billing_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('billing_error') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Add New Service</h3>
                <form method="POST" action="{{ route('manager.services.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                    @csrf
                    <input type="text" name="name" placeholder="Service name" class="rounded-md border-slate-300" required>
                    <select name="type" class="rounded-md border-slate-300">
                        <option value="standard">Standard</option>
                        <option value="premium">Premium</option>
                    </select>
                    <input type="number" step="0.01" min="0" name="base_price" placeholder="Price (PHP)" class="rounded-md border-slate-300" required>
                    <input type="number" min="5" max="600" name="duration_minutes" placeholder="Duration (mins)" class="rounded-md border-slate-300" required>
                    <input type="text" name="description" placeholder="Short description" class="rounded-md border-slate-300">
                    <button type="submit" class="rounded-md bg-amber-600 hover:bg-amber-500 text-white font-semibold px-4 py-2">Add Service</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800">Manage Services</h3>
                    <p class="text-sm text-slate-500 mt-1">Archived services are hidden from customer booking but retained for historical records.</p>
                </div>

                <div class="p-6 space-y-4">
                    @forelse ($services as $service)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <form method="POST" action="{{ route('manager.services.update', ['service' => $service->id]) }}" class="grid grid-cols-1 md:grid-cols-8 gap-3 items-center">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="name" value="{{ $service->name }}" class="rounded-md border-slate-300 md:col-span-2" required>
                                <select name="type" class="rounded-md border-slate-300" required>
                                    <option value="standard" @selected(($service->type ?? 'standard') === 'standard')>Standard</option>
                                    <option value="premium" @selected(($service->type ?? 'standard') === 'premium')>Premium</option>
                                </select>
                                <input type="number" step="0.01" min="0" name="base_price" value="{{ $service->base_price ?? $service->price }}" class="rounded-md border-slate-300" required>
                                <input type="number" min="5" max="600" name="duration_minutes" value="{{ $service->duration_min ?? $service->duration_minutes }}" class="rounded-md border-slate-300" required>
                                <input type="text" name="description" value="{{ $service->description }}" class="rounded-md border-slate-300 md:col-span-2">
                                <div class="flex items-center gap-2 justify-end">
                                    <label class="inline-flex items-center text-sm text-slate-600">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($service->is_active) class="rounded border-slate-300">
                                        <span class="ml-2">Active</span>
                                    </label>
                                    <button type="submit" class="rounded-md bg-slate-900 hover:bg-slate-800 text-white font-semibold px-3 py-2 text-sm">Save</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('manager.services.destroy', ['service' => $service->id]) }}" class="mt-3 flex justify-end" onsubmit="return confirm('Archive this service? It will be hidden from new bookings.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-semibold px-3 py-2 text-sm">Archive Service</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No services configured yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800">Archived Services</h3>
                </div>

                <div class="p-6 space-y-3">
                    @forelse (($archivedServices ?? collect()) as $archivedService)
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 rounded-xl border border-slate-200 p-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $archivedService->name }}</p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ ucfirst($archivedService->type ?? 'standard') }} · PHP {{ number_format((float) ($archivedService->base_price ?? $archivedService->price ?? 0), 2) }} · {{ (int) ($archivedService->duration_min ?? $archivedService->duration_minutes ?? 0) }} mins
                                </p>
                                <p class="text-xs text-slate-400 mt-1">Archived {{ optional($archivedService->archived_at)->diffForHumans() ?? 'recently' }}</p>
                            </div>

                            <form method="POST" action="{{ route('manager.services.restore', ['service' => $archivedService->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-md bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-2 text-sm">Restore Service</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No archived services.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
