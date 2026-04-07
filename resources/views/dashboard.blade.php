<x-app-layout>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">

        <!-- Left Column: Cards & Roles/Activity -->
        <div class="xl:col-span-2 space-y-10">

            <!-- Cards Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">My Access</h2>
                <a href="#" class="text-sm font-semibold text-gray-400 hover:text-gray-900 transition-colors">
                    Add New
                </a>
            </div>

            <!-- "My Cards" Interface -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Primary Card -->
                <div class="relative bg-[#0F0F12] rounded-[28px] p-7 text-white shadow-2xl shadow-black/20 overflow-hidden group hover:-translate-y-2 hover:shadow-3xl hover:shadow-[#E2D4FF]/40 transition-all duration-400 cursor-pointer">
                    <div class="absolute right-0 top-0 bottom-0 w-28 bg-[#E2D4FF] z-0 transition-transform duration-500 group-hover:scale-x-110 origin-right"></div>
                    <div class="relative z-10 flex justify-between flex-col h-full min-h-[170px]">
                        <div class="flex justify-between items-start">
                            <div class="font-bold text-2xl tracking-widest text-[#E2D4FF]">ROLE</div>
                            <div class="text-black transform -rotate-90 translate-x-5 translate-y-3">
                                <svg class="w-8 h-8 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5.5 8.5C5.5 10 6.5 11 8.5 11C10.5 11 11.5 10 11.5 8.5C11.5 7 10.5 6 8.5 6C6.5 6 5.5 7 5.5 8.5Z"/><path d="M12.5 15.5C12.5 17 13.5 18 15.5 18C17.5 18 18.5 17 18.5 15.5C18.5 14 17.5 13 15.5 13C13.5 13 12.5 14 12.5 15.5Z"/></svg>
                            </div>
                        </div>
                        <div class="mt-8">
                            <div class="text-xl font-medium tracking-widest mb-1">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</div>
                            <div class="text-sm text-gray-400 font-medium">{{ Auth::user()->name }}</div>
                            <div class="absolute right-0 bottom-0 text-xs text-black font-bold uppercase transform -rotate-90 translate-x-9 -translate-y-4 tracking-widest">
                                {{ Auth::user()->created_at->format('m/y') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Card -->
                <div class="relative bg-[#0F0F12] rounded-[28px] p-7 text-white shadow-2xl shadow-black/20 overflow-hidden group hover:-translate-y-2 hover:shadow-3xl hover:shadow-[#B8EBD0]/40 transition-all duration-400 cursor-pointer">
                    <div class="absolute right-0 top-0 bottom-0 w-28 bg-[#B8EBD0] z-0 transition-transform duration-500 group-hover:scale-x-110 origin-right"></div>
                    <div class="relative z-10 flex justify-between flex-col h-full min-h-[170px]">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-white/20"></div>
                                <div class="w-10 h-10 rounded-full bg-white/40 -ml-4 z-10 backdrop-blur-sm"></div>
                            </div>
                            <div class="ml-auto text-black transform -rotate-90 translate-x-5 translate-y-3">
                                <svg class="w-8 h-8 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5.5 8.5C5.5 10 6.5 11 8.5 11C10.5 11 11.5 10 11.5 8.5C11.5 7 10.5 6 8.5 6C6.5 6 5.5 7 5.5 8.5Z"/><path d="M12.5 15.5C12.5 17 13.5 18 15.5 18C17.5 18 18.5 17 18.5 15.5C18.5 14 17.5 13 15.5 13C13.5 13 12.5 14 12.5 15.5Z"/></svg>
                            </div>
                        </div>
                        <div class="mt-8">
                            <div class="text-xl font-medium tracking-widest mb-1">{{ Auth::user()->tenant ? Auth::user()->tenant->name : 'No Tenant' }}</div>
                            <div class="text-sm text-gray-400 font-medium">Workspace</div>
                            <div class="absolute right-0 bottom-0 text-xs text-black font-bold uppercase transform -rotate-90 translate-x-9 -translate-y-4 tracking-widest">
                                ACTIVE
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity List (Mocks 'Transactions') -->
            <div class="flex items-center justify-between mt-6">
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Recent Activity</h2>
                <div class="px-5 py-2 rounded-full border border-gray-200 bg-white text-sm font-semibold text-gray-600 cursor-pointer shadow-sm hover:bg-gray-50 hover:text-black transition-all">
                    Month <span class="text-gray-400 ml-2">▾</span>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-2 overflow-hidden">
                <div class="flex flex-col">
                    @forelse (Auth::user()->roles as $role)
                        <div class="flex items-center justify-between p-5 hover:bg-[#FAFAFC] transition-colors rounded-[20px] group cursor-pointer border-b border-gray-50 last:border-0">
                            <div class="flex items-center gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-black text-white flex items-center justify-center shrink-0 shadow-lg group-hover:scale-105 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-[#E2D4FF]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.954 11.954 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-lg text-gray-900">{{ $role->name }} Role Active</div>
                                    <div class="text-sm font-medium text-gray-400">System Notification</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-10">
                                <div class="text-sm font-medium text-gray-400 hidden sm:block">{{ date('M d, Y') }}</div>
                                <div class="font-bold text-lg text-gray-900 w-24 text-right">Active</div>
                                <button class="w-8 h-8 flex items-center justify-center text-gray-300 group-hover:text-black transition-colors rounded-full hover:bg-gray-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path></svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 font-medium">No recent activity found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column: Chart & History -->
        <div class="space-y-10">
            <!-- Chart Area Header -->
            <div class="flex items-center justify-end gap-3">
                <button class="px-5 py-2.5 rounded-full bg-[#E2D4FF] text-black text-sm font-bold shadow-sm transition-transform hover:scale-105">Usage</button>
                <button class="px-5 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-colors shadow-sm">Stats</button>
                <div class="flex items-center gap-4 ml-6 text-sm font-bold text-gray-400">
                    <span class="hover:text-black cursor-pointer transition-colors">D</span>
                    <span class="hover:text-black cursor-pointer transition-colors text-black">M</span>
                    <span class="hover:text-black cursor-pointer transition-colors">W</span>
                    <span class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-black hover:bg-gray-50 cursor-pointer">All</span>
                </div>
            </div>

            <!-- Simulated Chart Area -->
            <div class="h-64 w-full relative pt-10 border-b border-gray-100 pb-10">
                <!-- We do a mock SVG line exactly like the inspo -->
                <svg class="w-full h-full overflow-visible" viewBox="0 0 400 150" preserveAspectRatio="none">
                    <path d="M 0 150 Q 20 120 40 120 T 80 90 T 120 60 T 160 50 T 200 40 T 240 60 T 280 60 T 320 20 T 360 40 T 400 0" fill="none" stroke="black" stroke-width="4" stroke-linecap="round" class="animate-[draw_1.5s_ease-out_forwards] [stroke-dasharray:1000] [stroke-dashoffset:1000]" />

                    <!-- Tooltip dot & popup -->
                    <g transform="translate(200, 40)" class="animate-fade-in stagger-3">
                        <line x1="0" y1="0" x2="0" y2="110" stroke="#f3f4f6" stroke-width="2" />
                        <circle cx="0" cy="0" r="6" fill="#E2D4FF" stroke="black" stroke-width="3" />
                        <rect x="-40" y="-45" width="80" height="30" rx="15" fill="black" />
                        <text x="0" y="-25" fill="white" font-size="12" font-weight="bold" text-anchor="middle" font-family="sans-serif">Active</text>
                        <polygon points="-5,-15 5,-15 0,-8" fill="black" />
                    </g>
                </svg>

                <div class="absolute bottom-0 w-full flex justify-between text-xs font-bold text-gray-400">
                    <span>JAN</span><span>FEB</span><span>MAR</span><span>APR</span><span>MAY</span><span>JUN</span><span>JUL</span>
                </div>
            </div>

            <!-- Side History (Mocks right history list) -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">History</h2>
                    <a href="#" class="text-sm font-semibold text-gray-400 hover:text-gray-900 transition-colors">See all</a>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center justify-between group cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-black text-white flex items-center justify-center font-bold text-lg group-hover:scale-110 transition-transform">
                                LI
                            </div>
                            <div>
                                <div class="font-bold text-base text-gray-900 group-hover:text-[#E2D4FF] transition-colors">Login Event</div>
                                <div class="text-xs font-medium text-gray-400 mt-0.5">10 Mar 2021</div>
                            </div>
                        </div>
                        <div class="font-bold text-gray-900">-</div>
                    </div>

                    <div class="flex items-center justify-between group cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-black text-white flex items-center justify-center font-bold text-lg group-hover:scale-110 transition-transform">
                                SF
                            </div>
                            <div>
                                <div class="font-bold text-base text-gray-900 group-hover:text-[#E2D4FF] transition-colors">Session Finish</div>
                                <div class="text-xs font-medium text-gray-400 mt-0.5">09 Mar 2021</div>
                            </div>
                        </div>
                        <div class="font-bold text-gray-900">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes draw {
            to { stroke-dashoffset: 0; }
        }
    </style>
</x-app-layout>
