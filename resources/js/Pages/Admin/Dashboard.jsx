import React from 'react';
import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import AppLayout from '../../Layouts/AppLayout';

export default function Dashboard({ auth, canLogin, canRegister }) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.3 }}
            className="p-6 bg-white min-h-screen text-slate-800"
        >
            <Head title="Admin Dashboard" />
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-bold">Admin Dashboard</h1>
                <div className="flex space-x-4">
                    {auth?.user ? (
                        <a href="/dashboard" className="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            Go to Dashboard
                        </a>
                    ) : (
                        <>
                            {canLogin && (
                                <a href="/login" className="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                                    Log in
                                </a>
                            )}
                            {canRegister && (
                                <a href="/register" className="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                                    Register
                                </a>
                            )}
                        </>
                    )}
                </div>
            </div>
            <p>Welcome to your modernized React + Tailwind dashboard!</p>
        </motion.div>
    );
}

Dashboard.layout = page => <AppLayout>{page}</AppLayout>;
