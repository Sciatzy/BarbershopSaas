<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-800">Customize Your Barbershop</h2>
        <p id="setup-desc" class="text-sm text-slate-500 mt-1">Please provide the details below so we can set up your workspace.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8">
                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm mb-6">
                            <p class="font-semibold">Please fix the following issues:</p>
                            <ul class="list-disc ml-5 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('manager.setup.store', [], false) }}" class="space-y-6">
                        @csrf

                        <!-- Shop Name -->
                        <div>
                            <x-input-label for="tenant_name" value="Barbershop Display Name" />
                            <p class="text-xs text-gray-500 mb-2">This is the name that will be displayed on your booking page and dashboard.</p>
                            <x-text-input id="tenant_name" name="tenant_name" type="text" class="block w-full" :value="old('tenant_name', $tenant->name)" required autofocus />
                        </div>

                        <!-- Subdomain -->
                        <div class="mt-6">
                            <x-input-label for="primary_domain" value="Platform Subdomain" />
                            <p class="text-xs text-gray-500 mb-2">Choose a unique identifier for your shop. Letters, numbers, and dashes only.</p>

                            <div class="flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-amber-600">
                                @php
                                    $defaultSubdomain = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', str_replace('\'s Barbershop', '', $tenant->name)));
                                @endphp
                                <input type="text" name="primary_domain" id="primary_domain" class="block w-full flex-1 border-0 py-2 pl-3 bg-transparent text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6" placeholder="myshop" value="{{ old('primary_domain', $defaultSubdomain) }}" required>
                                <span class="flex select-none items-center pr-3 text-gray-500 sm:text-sm">
                                @php
                                    $domainBase = config('app.url');
                                    $host = parse_url($domainBase, PHP_URL_HOST) ?? 'barbershopsaas.test';
                                @endphp
                                    .{{ $host }}
                                </span>
                            </div>
                        </div>

                        <!-- Custom Domain (Optional) -->
                        <div class="mt-6">
                            <x-input-label for="custom_domain" value="Custom Domain (Optional)" />
                            <p class="text-xs text-gray-500 mb-2">If you have your own domain name (e.g., greatbarber.com), enter it here.</p>
                            <x-text-input id="custom_domain" name="custom_domain" type="text" class="block w-full" :value="old('custom_domain')" placeholder="e.g., www.mybarbershop.com" />
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="brand_color" value="Shop Accent Color" />
                                <p class="text-xs text-gray-500 mb-2">Primary accent used in the customer dashboard.</p>
                                <input id="brand_color" name="brand_color" type="color" value="{{ old('brand_color', $tenant->brand_color ?? '#C9A84C') }}" class="h-11 w-full rounded border border-gray-300 bg-white p-1">
                            </div>

                            <div>
                                <x-input-label for="brand_color_secondary" value="Shop Secondary Color" />
                                <p class="text-xs text-gray-500 mb-2">Button/CTA color used in the customer dashboard.</p>
                                <input id="brand_color_secondary" name="brand_color_secondary" type="color" value="{{ old('brand_color_secondary', $tenant->brand_color_secondary ?? '#B54B2A') }}" class="h-11 w-full rounded border border-gray-300 bg-white p-1">
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex justify-end">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md hover:shadow-lg flex items-center">
                                Save Details & Proceed to Payment
                                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 text-center text-sm text-gray-500 flex justify-center items-center">
                <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00- 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Secure setup and checkout process
            </div>
        </div>
    </div>
</x-app-layout>
