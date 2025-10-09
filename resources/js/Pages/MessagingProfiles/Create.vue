<template>
    <Head title="Create Messaging Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Messaging Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Error Message -->
                <div v-if="$page.props.flash?.error || $page.props.errors?.error" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    <div class="font-semibold">Error:</div>
                    <div>{{ $page.props.flash.error || $page.props.errors.error }}</div>
                </div>

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

                            <!-- Number Pool Settings -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Number Pool Settings</h3>
                                
                                <div class="mb-4">
                                    <div class="flex items-center">
                                        <input
                                            v-model="enableNumberPool"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            @change="toggleNumberPool"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">Enable number pool distribution</label>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Configure how messages are distributed across assigned numbers</p>
                                </div>

                                <div v-if="enableNumberPool" class="space-y-4 pl-6 border-l-2 border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Toll-Free Weight <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model.number="form.number_pool_settings.toll_free_weight"
                                                type="number"
                                                min="0"
                                                step="0.1"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                placeholder="1"
                                                required
                                            />
                                            <p class="text-sm text-gray-500 mt-1">Numeric value, minimum 0 (required)</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Long Code Weight <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model.number="form.number_pool_settings.long_code_weight"
                                                type="number"
                                                min="0"
                                                step="0.1"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                placeholder="1"
                                                required
                                            />
                                            <p class="text-sm text-gray-500 mt-1">Numeric value, minimum 0 (required)</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input
                                                v-model="form.number_pool_settings.skip_unhealthy"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">Skip unhealthy numbers</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                v-model="form.number_pool_settings.sticky_sender"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">Maintain same sender per recipient</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                v-model="form.number_pool_settings.geomatch"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">Match recipient's area code (NANP only)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- URL Shortener Settings -->
                            <div class="mb-8">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">URL Shortener Settings</h3>
                                
                                <div class="mb-4">
                                    <div class="flex items-center">
                                        <input
                                            v-model="enableUrlShortener"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            @change="toggleUrlShortener"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">Enable URL shortener</label>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Replace public shortener URLs with Telnyx branded short links</p>
                                </div>

                                <div v-if="enableUrlShortener" class="space-y-4 pl-6 border-l-2 border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Domain <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model="form.url_shortener_settings.domain"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                placeholder="Domain provided by Telnyx"
                                                required
                                            />
                                            <p class="text-sm text-gray-500 mt-1">Required when URL shortener is enabled</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Brand Prefix
                                            </label>
                                            <input
                                                v-model="form.url_shortener_settings.prefix"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                placeholder="Optional brand prefix"
                                            />
                                            <p class="text-sm text-gray-500 mt-1">Optional string (nullable)</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input
                                                v-model="form.url_shortener_settings.replace_blacklist_only"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">Replace only blacklisted shortener URLs</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                v-model="form.url_shortener_settings.send_webhooks"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label class="ml-2 text-sm text-gray-700">Enable click-tracking webhooks</label>
                                        </div>
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
                                            pattern="^[A-Za-z0-9 ]{1,11}$"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="MYSENDER"
                                        />
                                        <p class="text-sm text-gray-500 mt-1">1-11 alphanumeric characters and spaces only (regex: ^[A-Za-z0-9 ]{1,11}$)</p>
                                        <div v-if="errors.alpha_sender" class="text-red-500 text-sm mt-1">{{ errors.alpha_sender }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Daily Spend Limit (USD)
                                        </label>
                                        
                                        <div class="mb-3">
                                            <div class="flex items-center">
                                                <input
                                                    v-model="form.daily_spend_limit_enabled"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                />
                                                <label class="ml-2 text-sm text-gray-700">Enable daily spend limit</label>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1">Set a maximum USD amount that can be spent per day</p>
                                        </div>

                                        <div v-if="form.daily_spend_limit_enabled">
                                            <input
                                                v-model="form.daily_spend_limit"
                                                type="text"
                                                pattern="^[0-9]+(?:\.[0-9]+)?$"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                placeholder="100.00"
                                                required
                                            />
                                            <p class="text-sm text-gray-500 mt-1">Format: numbers and decimal only (regex: ^[0-9]+(?:\.[0-9]+)?$)</p>
                                            <div v-if="errors.daily_spend_limit" class="text-red-500 text-sm mt-1">{{ errors.daily_spend_limit }}</div>
                                        </div>

                                        <div v-else class="text-sm text-gray-500 italic">
                                            Daily spend limit is disabled - no spending restrictions will apply
                                        </div>
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
                                    {{ processing ? 'Creating...' : 'Create Profile' }}
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
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
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
    number_pool_settings: null,
    url_shortener_settings: null,
    alpha_sender: '',
    daily_spend_limit: '',
    daily_spend_limit_enabled: false,
    mms_fall_back_to_sms: false,
    mms_transcoding: false,
})

const processing = ref(false)
const allowAllCountries = ref(false)
const enableNumberPool = ref(false)
const enableUrlShortener = ref(false)
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

const toggleNumberPool = () => {
    if (enableNumberPool.value) {
        form.number_pool_settings = {
            toll_free_weight: 1,
            long_code_weight: 1,
            skip_unhealthy: true,
            sticky_sender: false,
            geomatch: false
        }
    } else {
        form.number_pool_settings = null
    }
}

const toggleUrlShortener = () => {
    if (enableUrlShortener.value) {
        form.url_shortener_settings = {
            domain: '',
            prefix: '',
            replace_blacklist_only: false,
            send_webhooks: false
        }
    } else {
        form.url_shortener_settings = null
    }
}

const submit = () => {
    processing.value = true
    
    // Ensure we have at least one destination
    if (form.whitelisted_destinations.length === 0) {
        form.whitelisted_destinations = ['*']
        allowAllCountries.value = true
    }
    
    router.post(route('messaging-profiles.store'), form, {
        onFinish: () => {
            processing.value = false
        },
        onError: (errors) => {
            console.error('Form errors:', errors)
            showToast('Please fix the errors below', 'error')
        }
    })
}
</script>

