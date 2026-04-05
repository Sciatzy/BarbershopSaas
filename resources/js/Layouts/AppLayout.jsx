import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { usePage } from '@inertiajs/react';

export default function AppLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-100 flex flex-col">
            <nav className="bg-white shadow">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="shrink-0 flex items-center">
                                <a href="/" className="font-bold text-xl text-indigo-600">Barbershop SaaS</a>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            {auth?.user ? (
                                <>
                                    <span className="text-gray-700">{auth.user.name}</span>
                                    <a href="/dashboard" className="text-indigo-600 hover:text-indigo-900 font-medium">
                                        Dashboard
                                    </a>
                                </>
                            ) : (
                                <>
                                    <a href="/login" className="text-gray-600 hover:text-indigo-600 font-medium">Log in</a>
                                    <a href="/register" className="text-gray-600 hover:text-indigo-600 font-medium">Register</a>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            <AnimatePresence mode="wait">
                <main className="flex-grow w-full max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" key={window.location?.pathname || 'default'}>
                    {children}
                </main>
            </AnimatePresence>
        </div>
    );
}

// Ensure all new pages use this layout by default if not set
export const getLayout = page => <AppLayout>{page}</AppLayout>;
