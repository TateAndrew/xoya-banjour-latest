<template>
    <Head title="Edit Outbound Voice Profile" />

    <DashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Outbound Voice Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Error Messages -->
                        <div v-if="form.errors && Object.keys(form.errors).length > 0" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                            <div class="font-medium mb-2">Please fix the following errors:</div>
                            <ul class="list-disc list-inside text-sm">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">
                            <!-- Basic Information -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Profile Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            :class="[
                                                'w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2',
                                                form.errors.name ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:ring-indigo-500'
                                            ]"
                                            placeholder="Enter profile name"
                                            required
                                        />
                                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Status
                                        </label>
                                        <select
                                            v-model="form.enabled"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option :value="true">Enabled</option>
                                            <option :value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Call Limits -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Call Limits</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Concurrent Call Limit
                                        </label>
                                        <input
                                            v-model.number="form.concurrent_call_limit"
                                            type="number"
                                            min="1"
                                            :class="[
                                                'w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2',
                                                form.errors.concurrent_call_limit ? 'border-red-300 focus:ring-red-500' : 'border-gray-300 focus:ring-indigo-500'
                                            ]"
                                            placeholder="Leave empty for unlimited"
                                        />
                                        <div v-if="form.errors.concurrent_call_limit" class="text-red-500 text-sm mt-1">{{ form.errors.concurrent_call_limit }}</div>
                                        <p class="text-xs text-gray-500 mt-1">Maximum number of simultaneous calls</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Max Destination Rate
                                        </label>
                                        <input
                                            v-model.number="form.max_destination_rate"
                                            type="number"
                                            min="0"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="Maximum rate per minute"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">Maximum rate per minute (in cents)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Spend Limits -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Spend Limits</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Daily Spend Limit (cents)
                                        </label>
                                        <input
                                            v-model.number="form.daily_spend_limit"
                                            type="number"
                                            min="0"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="Enter daily limit in cents"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Daily Spend Limit Status
                                        </label>
                                        <select
                                            v-model="form.daily_spend_limit_enabled"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="disabled">Disabled</option>
                                            <option value="enabled">Enabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Call Recording Settings -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Call Recording Settings</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Recording Type
                                        </label>
                                        <select
                                            v-model="form.call_recording_type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="">No Recording</option>
                                            <option value="all">Record All Calls</option>
                                            <option value="none">No Recording</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Recording Channels
                                        </label>
                                        <select
                                            v-model="form.call_recording_channels"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="">Default</option>
                                            <option value="single">Single Channel</option>
                                            <option value="dual">Dual Channel</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Recording Format
                                        </label>
                                        <select
                                            v-model="form.call_recording_format"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="">Default</option>
                                            <option value="wav">WAV</option>
                                            <option value="mp3">MP3</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Tags
                                        </label>
                                        <input
                                            v-model="form.tags"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="Optional tags"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Settings</h3>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Billing Group ID
                                    </label>
                                    <input
                                        v-model="form.billing_group_id"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Optional billing group ID"
                                    />
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-4">
                                <Link
                                    :href="route('outbound-voice-profiles.index')"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-25': processing }"
                                >
                                    {{ processing ? 'Updating...' : 'Update Profile' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    profile: Object
})

const form = useForm({
    name: props.profile.name || '',
    enabled: props.profile.enabled !== undefined ? props.profile.enabled : true,
    concurrent_call_limit: props.profile.concurrent_call_limit || null,
    max_destination_rate: props.profile.max_destination_rate || null,
    daily_spend_limit: props.profile.daily_spend_limit || null,
    daily_spend_limit_enabled: props.profile.daily_spend_limit_enabled || 'disabled',
    call_recording_type: props.profile.call_recording_type || 'all',
    call_recording_channels: props.profile.call_recording_channels || '',
    call_recording_format: props.profile.call_recording_format || 'mp3',
    tags: props.profile.tags || '',
    billing_group_id: props.profile.billing_group_id || '',
})

const processing = ref(false)

const submit = () => {
    processing.value = true
    form.put(route('outbound-voice-profiles.update', props.profile.id), {
        onSuccess: () => {
            processing.value = false
        },
        onError: () => {
            processing.value = false
            // Scroll to top to show error message
            window.scrollTo({ top: 0, behavior: 'smooth' })
        }
    })
}
</script>

