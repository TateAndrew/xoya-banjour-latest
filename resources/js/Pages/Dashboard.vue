<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
    <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-2xl font-bold mb-6">Welcome to your Dashboard!</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- User Management Card -->
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                <h3 class="text-lg font-semibold text-blue-900 mb-3">User Management</h3>
                                <p class="text-blue-700 mb-4">Manage users, roles, and permissions</p>
                                <Link
                                    :href="route('users.index')"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                >
                                    Manage Users
                                </Link>
                            </div>

                            <!-- Phone Numbers Card -->
                            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                <h3 class="text-lg font-semibold text-green-900 mb-3">Phone Numbers</h3>
                                <p class="text-green-700 mb-4">Manage your phone numbers</p>
                                <Link
                                    :href="route('phone-numbers.index')"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                                >
                                    View Numbers
                                </Link>
                            </div>

                            <!-- SIP Trunks Card -->
                            <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                <h3 class="text-lg font-semibold text-purple-900 mb-3">SIP Trunks</h3>
                                <p class="text-purple-700 mb-4">Manage your SIP trunk configurations</p>
                                <Link
                                    :href="route('sip-trunks.index')"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors"
                                >
                                    Manage Trunks
                                </Link>
                            </div>

                            <!-- Messaging Profiles Card -->
                            <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-200">
                                <h3 class="text-lg font-semibold text-indigo-900 mb-3">Messaging Profiles</h3>
                                <p class="text-indigo-700 mb-4">Configure messaging settings and destinations</p>
                                <Link
                                    :href="route('messaging-profiles.index')"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors"
                                >
                                    Manage Profiles
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
