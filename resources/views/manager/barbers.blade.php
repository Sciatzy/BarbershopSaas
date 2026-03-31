<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Barber Management</h2>
            <a href="{{ route('manager.dashboard') }}" class="rounded-md bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600">Back to Manager Dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('barber_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('barber_status') }}
                </div>
            @endif

            @if (session('barber_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('barber_error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="list-disc ml-5 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-gray-500">Current Barbers</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $usage['barber_count'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-gray-500">Barber Limit</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900">{{ $usage['barber_limit'] ?? 'Unlimited' }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">Add Barber</h3>
                <p class="text-sm text-gray-600 mt-1">Create a login for a barber under your tenant.</p>

                <form method="POST" action="{{ route('manager.barbers.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Temporary Password</label>
                        <input id="password" name="password" type="password" required class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    @if (auth()->user()->hasRole('Barbershop Admin'))
                        <div>
                            <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch (optional)</label>
                            <select id="branch_id" name="branch_id" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Unassigned</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600" style="background-color:#0f766e;color:#fff;cursor:pointer;">
                            Create Barber Account
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Existing Barbers</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Email</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Branch ID</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($barbers as $barber)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $barber->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $barber->email }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $barber->branch_id ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ optional($barber->created_at)->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">No barber accounts yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
