<x-app-layout>
    <x-slot name="header">
            <h2 class="text-2xl font-bold text-slate-800">Barbers Management</h2>
            <p class="text-sm text-slate-500 mt-1">View, create, edit, and delete barber accounts.</p>
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
                    <p class="text-slate-400">Current Barbers</p>
                    <p class="mt-1 text-xl font-semibold text-slate-800">{{ $usage['barber_count'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-slate-400">Barber Limit</p>
                    <p class="mt-1 text-xl font-semibold text-slate-800">{{ $usage['barber_limit'] ?? 'Unlimited' }}</p>
                </div>
            </div>

            @php
                $isBarbershopAdmin = auth()->user()?->hasRole('Barbershop Admin');
                $isBranchManager = auth()->user()?->hasRole('Branch Manager');
                $managerBranch = $isBranchManager ? $branches->first() : null;
            @endphp

            @if ($isBarbershopAdmin || $isBranchManager)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-lg font-semibold text-slate-800">Add Barber</h3>
                    <p class="text-sm text-slate-500 mt-1">Create a login for a barber under your tenant.</p>
                    <p class="text-xs text-slate-500 mt-1">Temporary password is generated automatically and sent to the barber email.</p>

                    <form method="POST" action="{{ route('manager.barbers.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf

                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-600">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-600">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        @if ($isBarbershopAdmin)
                            <div>
                                <label for="branch_id" class="block text-sm font-medium text-slate-600">Branch (optional)</label>
                                <select id="branch_id" name="branch_id" class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Unassigned</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="branch_id" value="{{ $managerBranch?->id }}">
                            <div>
                                <label class="block text-sm font-medium text-slate-600">Branch Assignment</label>
                                <p class="mt-1 text-sm text-slate-700 rounded-md border border-slate-200 bg-slate-50 px-3 py-2">{{ $managerBranch?->name ?? 'No branch assigned' }}</p>
                                <p class="mt-1 text-xs text-slate-500">New barber accounts you create are automatically assigned to your branch.</p>
                            </div>
                        @endif

                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-md bg-blue-500 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600" >
                                Create Barber Account
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    You currently do not have permission to manage barber accounts.
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Existing Barbers</h3>
                </div>
                <div class="overflow-x-auto">
                    @php
                        $branchesById = $branches->keyBy('id');
                    @endphp
                    <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Name</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Email</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Branch Assignment</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Created</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse ($barbers as $barber)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $barber->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $barber->email }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($isBarbershopAdmin)
                                            <form method="POST" action="{{ route('manager.barbers.branch', ['barberId' => $barber->id]) }}" class="flex flex-col md:flex-row md:items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="branch_id" class="w-full md:w-56 rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">Unassigned</option>
                                                    @foreach ($branches as $branch)
                                                        <option value="{{ $branch->id }}" @selected((string) $barber->branch_id === (string) $branch->id)>
                                                            {{ $branch->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="rounded-md bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                                                    Save Assignment
                                                </button>
                                            </form>
                                        @else
                                            <p class="text-xs text-slate-700">{{ $barber->branch_id ? ($branchesById->get($barber->branch_id)?->name ?? 'Unknown branch') : 'Unassigned' }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Only the owner can reassign branch assignments.</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ optional($barber->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <div class="min-w-[250px] space-y-2">
                                            <form method="POST" action="{{ route('manager.barbers.update', ['barberId' => $barber->id]) }}" class="rounded-lg border border-slate-200 bg-slate-50/40 p-2">
                                                @csrf
                                                @method('PATCH')
                                                <div class="grid grid-cols-1 gap-2">
                                                    <input name="name" type="text" value="{{ $barber->name }}" required class="w-full rounded-md border-slate-200 bg-white text-xs focus:border-blue-500 focus:ring-blue-500">
                                                    <input name="email" type="email" value="{{ $barber->email }}" required class="w-full rounded-md border-slate-200 bg-white text-xs focus:border-blue-500 focus:ring-blue-500">
                                                </div>
                                                <button type="submit" class="mt-2 w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Save Details</button>
                                            </form>

                                            <form method="POST" action="{{ route('manager.barbers.destroy', ['barberId' => $barber->id]) }}" onsubmit="return confirm('Delete this barber account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500">Delete Barber</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">No barber accounts yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
