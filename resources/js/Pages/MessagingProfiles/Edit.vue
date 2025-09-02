<template>
    <Head title="Edit Messaging Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Messaging Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
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
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="Enter profile name"
                                            required
                                        />
                                        <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</div>
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

                                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-600">
                                        <strong>Telnyx Profile ID:</strong> {{ messagingProfile.telnyx_profile_id }}
                                    </p>
                                </div>
                            </div>

                            <!-- Whitelisted Destinations -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Whitelisted Destinations</h3>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Allowed Countries <span class="text-red-500">*</span>
                                    </label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input
                                                v-model="allowAllCountries"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                @change="toggleAllCountries"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">Allow all countries</label>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!allowAllCountries" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                                    <div v-for="country in countries.filter(c => c.code !== '*')" :key="country.code" class="flex items-center">
                                        <input
                                            v-model="form.whitelisted_destinations"
                                            :value="country.code"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">{{ country.name }}</label>
                                    </div>
                                </div>
                                
                                <div v-if="errors.whitelisted_destinations" class="text-red-500 text-sm mt-1">{{ errors.whitelisted_destinations }}</div>
                            </div>

                            <!-- Webhook Configuration -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Webhook Configuration</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Webhook URL
                                        </label>
                                        <input
                                            v-model="form.webhook_url"
                                            type="url"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="https://your-domain.com/webhook"
                                        />
                                        <div v-if="errors.webhook_url" class="text-red-500 text-sm mt-1">{{ errors.webhook_url }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Webhook Failover URL
                                        </label>
                                        <input
                                            v-model="form.webhook_failover_url"
                                            type="url"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="https://your-domain.com/webhook-failover"
                                        />
                                        <div v-if="errors.webhook_failover_url" class="text-red-500 text-sm mt-1">{{ errors.webhook_failover_url }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Webhook API Version
                                        </label>
                                        <select
                                            v-model="form.webhook_api_version"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="1">Version 1</option>
                                            <option value="2">Version 2</option>
                                            <option value="2010-04-01">Version 2010-04-01</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced Settings -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Alpha Sender
                                        </label>
                                        <input
                                            v-model="form.alpha_sender"
                                            type="text"
                                            maxlength="11"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="MYSENDER"
                                        />
                                        <p class="text-sm text-gray-500 mt-1">1-11 alphanumeric characters</p>
                                        <div v-if="errors.alpha_sender" class="text-red-500 text-sm mt-1">{{ errors.alpha_sender }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Daily Spend Limit (USD)
                                        </label>
                                        <div class="flex space-x-3">
                                            <input
                                                v-model="form.daily_spend_limit"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                placeholder="100.00"
                                            />
                                            <div class="flex items-center">
                                                <input
                                                    v-model="form.daily_spend_limit_enabled"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                />
                                                <label class="ml-2 text-sm text-gray-700">Enable</label>
                                            </div>
                                        </div>
                                        <div v-if="errors.daily_spend_limit" class="text-red-500 text-sm mt-1">{{ errors.daily_spend_limit }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- MMS Settings -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">MMS Settings</h3>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input
                                            v-model="form.mms_fall_back_to_sms"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">Fall back to SMS if MMS fails</label>
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            v-model="form.mms_transcoding"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">Enable MMS media transcoding/resizing</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <Link
                                    :href="route('messaging-profiles.index')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    {{ processing ? 'Updating...' : 'Update Profile' }}
                                </button>
                            </div>
                        </form>
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
import { ref, reactive, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
    messagingProfile: Object,
    countries: Array,
    errors: Object
})

const form = reactive({
    name: '',
    whitelisted_destinations: [],
    enabled: true,
    webhook_url: '',
    webhook_failover_url: '',
    webhook_api_version: '2',
    alpha_sender: '',
    daily_spend_limit: '',
    daily_spend_limit_enabled: false,
    mms_fall_back_to_sms: false,
    mms_transcoding: false,
})

const processing = ref(false)
const allowAllCountries = ref(false)
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

const toggleAllCountries = () => {
    if (allowAllCountries.value) {
        form.whitelisted_destinations = ['*']
    } else {
        form.whitelisted_destinations = []
    }
}

const submit = () => {
    processing.value = true
    
    // Ensure we have at least one destination
    if (form.whitelisted_destinations.length === 0) {
        form.whitelisted_destinations = ['*']
        allowAllCountries.value = true
    }
    
    router.put(route('messaging-profiles.update', props.messagingProfile.id), form, {
        onFinish: () => {
            processing.value = false
        },
        onError: (errors) => {
            console.error('Form errors:', errors)
            showToast('Please fix the errors below', 'error')
        }
    })
}

onMounted(() => {
    // Initialize form with existing data
    form.name = props.messagingProfile.name || ''
    form.whitelisted_destinations = props.messagingProfile.whitelisted_destinations || []
    form.enabled = props.messagingProfile.enabled
    form.webhook_url = props.messagingProfile.webhook_url || ''
    form.webhook_failover_url = props.messagingProfile.webhook_failover_url || ''
    form.webhook_api_version = props.messagingProfile.webhook_api_version || '2'
    form.alpha_sender = props.messagingProfile.alpha_sender || ''
    form.daily_spend_limit = props.messagingProfile.daily_spend_limit || ''
    form.daily_spend_limit_enabled = props.messagingProfile.daily_spend_limit_enabled || false
    form.mms_fall_back_to_sms = props.messagingProfile.mms_fall_back_to_sms || false
    form.mms_transcoding = props.messagingProfile.mms_transcoding || false
    
    // Check if all countries are allowed
    allowAllCountries.value = form.whitelisted_destinations.includes('*')
})
</script>

