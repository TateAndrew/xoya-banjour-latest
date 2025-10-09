<template>
    <Head title="Outbound Voice Profiles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Outbound Voice Profiles
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Error Message -->
                <div v-if="$page.props.flash?.error" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {{ $page.props.flash.error }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold text-gray-900">Outbound Voice Profiles</h1>
                            <div class="flex space-x-2">
                                <button
                                    @click="syncProfiles"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Sync from Telnyx
                                </button>
                                <Link
                                    :href="route('outbound-voice-profiles.create')"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Create New Profile
                                </Link>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Call Limit
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="profile in profiles.data" :key="profile.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ profile.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: {{ profile.telnyx_profile_id }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="{
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800': profile.enabled,
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800': !profile.enabled
                                                }"
                                            >
                                                {{ profile.enabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ profile.concurrent_call_limit || 'Unlimited' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(profile.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <Link
                                                    :href="route('outbound-voice-profiles.show', profile.id)"
                                                    class="text-blue-600 hover:text-blue-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('outbound-voice-profiles.edit', profile.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    @click="deleteProfile(profile.id)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="profiles.links && profiles.links.length > 3" class="mt-6">
                            <nav class="flex justify-center">
                                <div class="flex space-x-1">
                                    <Link
                                        v-for="(link, index) in profiles.links"
                                        :key="index"
                                        :href="link.url"
                                        :class="{
                                            'px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50': !link.url,
                                            'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50': link.url && !link.active,
                                            'px-3 py-2 text-sm font-medium text-white bg-indigo-600 border border-indigo-600 rounded-md': link.active
                                        }"
                                        v-html="link.label"
                                    ></Link>
                                </div>
                            </nav>
                        </div>

                        <!-- Empty State -->
                        <div v-if="profiles.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500">
                                <div class="mx-auto h-12 w-12 text-gray-400 mb-4">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-12 h-12">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <p class="text-lg font-medium">No outbound voice profiles found</p>
                                <p class="mt-2">Get started by creating your first outbound voice profile.</p>
                                <div class="mt-6">
                                    <Link
                                        :href="route('outbound-voice-profiles.create')"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Create Outbound Voice Profile
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <Toast 
            v-if="toast.show" 
            :message="toast.message" 
            :type="toast.type" 
            :duration="3000"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
    profiles: Object
})

const toast = ref({
    show: false,
    message: '',
    type: 'info'
})

const showToast = (message, type = 'info') => {
    toast.value = {
        show: true,
        message,
        type
    }
}

const deleteProfile = (profileId) => {
    if (confirm('Are you sure you want to delete this outbound voice profile? This action cannot be undone.')) {
        router.delete(route('outbound-voice-profiles.destroy', profileId), {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Outbound voice profile deleted successfully!', 'success')
            },
            onError: (errors) => {
                const errorMessage = errors.error || errors.message || 'Failed to delete outbound voice profile'
                showToast(errorMessage, 'error')
            }
        })
    }
}

const syncProfiles = () => {
    router.post('/outbound-voice-profiles/sync', {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Profiles synced successfully!', 'success')
            router.reload()
        },
        onError: () => {
            showToast('Failed to sync profiles', 'error')
        }
    })
}
</script>

