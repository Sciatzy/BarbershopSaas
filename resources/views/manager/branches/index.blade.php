<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-800">Branch Management</h2>
        <p class="text-sm text-slate-500 mt-1">Create and maintain your branch locations under one owner domain.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('branch_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('branch_status') }}
                </div>
            @endif

            @if (session('branch_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('branch_error') }}
                </div>
            @endif

            @if (session('manager_status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('manager_status') }}
                </div>
            @endif

            @if (session('manager_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('manager_error') }}
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
                    <p class="text-slate-400">Current Branches</p>
                    <p class="mt-1 text-xl font-semibold text-slate-800">{{ $usage['branch_count'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <p class="text-slate-400">Branch Limit</p>
                    <p class="mt-1 text-xl font-semibold text-slate-800">{{ $usage['branch_limit'] ?? 'Unlimited' }}</p>
                </div>
            </div>

            @php
                $branchCount = (int) ($usage['branch_count'] ?? 0);
                $branchLimit = $usage['branch_limit'] ?? null;
                $isUnlimited = $branchLimit === null;
                $limitValue = $isUnlimited ? 1 : max((int) $branchLimit, 1);
                $usagePercent = min(100, (int) round(($branchCount / $limitValue) * 100));
            @endphp

            <div class="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                <p class="font-semibold">
                    @if ($isUnlimited)
                        You are using {{ $branchCount }} branch{{ $branchCount === 1 ? '' : 'es' }} on an unlimited branch plan.
                    @else
                        You are using {{ $branchCount }} of {{ $branchLimit }} branch{{ (int) $branchLimit === 1 ? '' : 'es' }}.
                    @endif
                </p>
                @if (! $isUnlimited)
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-indigo-100">
                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $usagePercent }}%"></div>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-semibold text-slate-800">Add Branch</h3>
                <p class="text-sm text-slate-500 mt-1">Customers will select a branch during booking under your tenant domain.</p>

                <form method="POST" action="{{ route('manager.branches.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-600">Branch Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-slate-600">Branch Address</label>
                        <input id="address" name="address" type="text" value="{{ old('address') }}" required class="mt-1 w-full rounded-md border-slate-200 bg-slate-50 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-md bg-blue-500 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600" >
                            Create Branch
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-slate-800">Existing Branches</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y-0 border-b border-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-500 rounded-t-xl">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Name</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Address</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Branch Manager</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Created</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wider text-xs text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse ($branches as $branch)
                                @php
                                    $branchManager = $branchManagers[$branch->id] ?? null;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $branch->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $branch->address }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if ($branchManager)
                                            <p class="font-semibold text-slate-800">{{ $branchManager->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $branchManager->email }}</p>
                                        @else
                                            <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded">No manager assigned</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ optional($branch->created_at)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @if (! $branchManager)
                                            <form method="POST" action="{{ route('manager.branches.assign-manager', $branch) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                                                @csrf
                                                <input name="manager_name" type="text" value="{{ old('manager_name') }}" placeholder="Manager Name" required class="rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500">
                                                <input name="manager_email" type="email" value="{{ old('manager_email') }}" placeholder="Manager Email" required class="rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500">
                                                <button type="submit" class="rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500">Add Manager</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('manager.branches.update', $branch) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                                            @csrf
                                            @method('PATCH')
                                            <input name="name" type="text" value="{{ $branch->name }}" required class="rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500">
                                            <input name="address" type="text" value="{{ $branch->address }}" required class="rounded-md border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500">
                                            <button type="submit" class="rounded-md bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('manager.branches.destroy', $branch) }}" onsubmit="return confirm('Delete this branch?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">No branches yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
