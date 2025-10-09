<template>
    <Head title="View Outbound Voice Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Outbound Voice Profile Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Error Message -->
                <div v-if="$page.props.flash?.error" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {{ $page.props.flash.error }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header Actions -->
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold text-gray-900">{{ profile.name }}</h1>
                            <div class="flex space-x-2">
                                <Link
                                    :href="route('outbound-voice-profiles.edit', profile.id)"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Edit Profile
                                </Link>
                                <Link
                                    :href="route('outbound-voice-profiles.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Back to List
                                </Link>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Profile Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ profile.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Telnyx Profile ID</label>
                                    <p class="mt-1 text-sm text-gray-900 font-mono">{{ profile.telnyx_profile_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Status</label>
                                    <p class="mt-1">
                                        <span :class="{
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800': profile.enabled,
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800': !profile.enabled
                                        }">
                                            {{ profile.enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Created At</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ new Date(profile.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Call Limits -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Call Limits</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Concurrent Call Limit</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ profile.concurrent_call_limit || 'Unlimited' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Max Destination Rate</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ profile.max_destination_rate ? `${profile.max_destination_rate} cents/min` : 'Not set' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Spend Limits -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Spend Limits</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Daily Spend Limit</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ profile.daily_spend_limit ? `${profile.daily_spend_limit} cents` : 'Not set' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Daily Spend Limit Status</label>
                                    <p class="mt-1">
                                        <span :class="{
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800': profile.daily_spend_limit_enabled === 'enabled',
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800': profile.daily_spend_limit_enabled !== 'enabled'
                                        }">
                                            {{ profile.daily_spend_limit_enabled === 'enabled' ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Call Recording Settings -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Call Recording Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Recording Type</label>
                                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ profile.call_recording_type || 'Not configured' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Recording Channels</label>
                                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ profile.call_recording_channels || 'Default' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Recording Format</label>
                                    <p class="mt-1 text-sm text-gray-900 uppercase">{{ profile.call_recording_format || 'Default' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Tags</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ profile.tags || 'None' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Additional Settings</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500">Billing Group ID</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ profile.billing_group_id || 'Not set' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div v-if="profile.metadata" class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">Telnyx Metadata</h3>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-xs text-gray-700 overflow-x-auto">{{ JSON.stringify(profile.metadata, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <button
                                @click="deleteProfile"
                                class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Delete Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    profile: Object
})

const deleteProfile = async () => {
    if (confirm('Are you sure you want to delete this outbound voice profile? This action cannot be undone.')) {
        router.delete(route('outbound-voice-profiles.destroy', props.profile.id), {
            onSuccess: () => {
                router.visit(route('outbound-voice-profiles.index'))
            }
        })
    }
}
</script>

