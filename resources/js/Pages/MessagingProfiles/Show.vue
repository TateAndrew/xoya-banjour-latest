<template>
    <Head title="Messaging Profile Details" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Messaging Profile Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ $page.props.flash.success }}
                </div>

                <!-- Error Message -->
                <div v-if="$page.props.flash?.error || $page.props.errors?.error" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {{ $page.props.flash.error || $page.props.errors.error }}
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header Actions -->
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold text-gray-900">{{ messagingProfile.name }}</h1>
                            <div class="flex space-x-3">
                                <Link
                                    :href="route('messaging-profiles.edit', messagingProfile.id)"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Edit Profile
                                </Link>
                                <button
                                    @click="deleteProfile"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Delete Profile
                                </button>
                            </div>
                        </div>

                        <!-- Profile Information Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Profile Name:</span>
                                            <span class="text-sm text-gray-900">{{ messagingProfile.name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Telnyx Profile ID:</span>
                                            <span class="text-sm text-gray-900 font-mono">{{ messagingProfile.telnyx_profile_id }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Status:</span>
                                            <span
                                                :class="{
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800': messagingProfile.enabled,
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800': !messagingProfile.enabled
                                                }"
                                            >
                                                {{ messagingProfile.enabled ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Created:</span>
                                            <span class="text-sm text-gray-900">{{ new Date(messagingProfile.created_at).toLocaleDateString() }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Updated:</span>
                                            <span class="text-sm text-gray-900">{{ new Date(messagingProfile.updated_at).toLocaleDateString() }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Whitelisted Destinations -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Whitelisted Destinations</h3>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div v-if="messagingProfile.whitelisted_destinations.includes('*')" class="flex items-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                🌍 All Countries
                                            </span>
                                        </div>
                                        <div v-else class="flex flex-wrap gap-2">
                                            <span
                                                v-for="country in messagingProfile.whitelisted_destinations"
                                                :key="country"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800"
                                            >
                                                {{ country }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Settings -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h3>
                                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Alpha Sender:</span>
                                            <span class="text-sm text-gray-900">{{ messagingProfile.alpha_sender || 'Not set' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Daily Spend Limit:</span>
                                            <span class="text-sm text-gray-900">
                                                <span v-if="messagingProfile.daily_spend_limit && messagingProfile.daily_spend_limit_enabled">
                                                    ${{ messagingProfile.daily_spend_limit }} (Enabled)
                                                </span>
                                                <span v-else>Not set</span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">MMS Fallback to SMS:</span>
                                            <span class="text-sm text-gray-900">{{ messagingProfile.mms_fall_back_to_sms ? 'Yes' : 'No' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">MMS Transcoding:</span>
                                            <span class="text-sm text-gray-900">{{ messagingProfile.mms_transcoding ? 'Yes' : 'No' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Webhook Configuration & Phone Numbers -->
                            <div class="space-y-6">
                                <!-- Webhook Configuration -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Webhook Configuration</h3>
                                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Webhook URL:</span>
                                            <span class="text-sm text-gray-900 break-all">{{ messagingProfile.webhook_url || 'Not set' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">Failover URL:</span>
                                            <span class="text-sm text-gray-900 break-all">{{ messagingProfile.webhook_failover_url || 'Not set' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm font-medium text-gray-500">API Version:</span>
                                            <span class="text-sm text-gray-900">{{ messagingProfile.webhook_api_version }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assigned Phone Numbers -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Assigned Phone Numbers</h3>
                                        <span class="text-sm text-gray-500">{{ assignedPhoneNumbers.length }} assigned</span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div v-if="assignedPhoneNumbers.length === 0" class="text-center py-4">
                                            <div class="text-sm text-gray-500">No phone numbers assigned to this profile</div>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <div
                                                v-for="number in assignedPhoneNumbers"
                                                :key="number.id"
                                                class="flex justify-between items-center p-3 bg-white rounded border"
                                            >
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium text-gray-900">{{ number.phone_number }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ number.country_code }} • {{ number.number_type || 'Standard' }}
                                                    </div>
                                                    <div v-if="number.assigned_to_profile_at" class="text-xs text-gray-400">
                                                        Assigned: {{ new Date(number.assigned_to_profile_at).toLocaleDateString() }}
                                                    </div>
                                                </div>
                                                <button
                                                    @click="unassignPhoneNumber(number.id)"
                                                    :disabled="assigningNumber"
                                                    class="ml-3 px-3 py-1 text-xs text-red-600 hover:text-red-900 border border-red-300 rounded hover:bg-red-50 disabled:opacity-50"
                                                >
                                                    Unassign
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Available Phone Numbers -->
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Available Phone Numbers</h3>
                                        <span class="text-sm text-gray-500">{{ availablePhoneNumbers.length }} available</span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div v-if="availablePhoneNumbers.length === 0" class="text-center py-4">
                                            <div class="text-sm text-gray-500">No available phone numbers to assign</div>
                                            <Link
                                                :href="route('phone-numbers.purchase')"
                                                class="text-sm text-indigo-600 hover:text-indigo-900 mt-2 inline-block"
                                            >
                                                Purchase phone numbers
                                            </Link>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <div
                                                v-for="number in availablePhoneNumbers"
                                                :key="number.id"
                                                class="flex justify-between items-center p-3 bg-white rounded border"
                                            >
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium text-gray-900">{{ number.phone_number }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ number.country_code }} • {{ number.number_type || 'Standard' }}
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        Purchased: {{ new Date(number.purchased_at).toLocaleDateString() }}
                                                    </div>
                                                </div>
                                                <button
                                                    @click="assignPhoneNumber(number.id)"
                                                    :disabled="assigningNumber"
                                                    class="ml-3 px-3 py-1 text-xs text-indigo-600 hover:text-indigo-900 border border-indigo-300 rounded hover:bg-indigo-50 disabled:opacity-50"
                                                >
                                                    {{ assigningNumber ? 'Assigning...' : 'Assign' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Back Button -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <Link
                                :href="route('messaging-profiles.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                ← Back to Profiles
                            </Link>
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
    messagingProfile: Object,
    assignedPhoneNumbers: Array,
    availablePhoneNumbers: Array
})

const assignedPhoneNumbers = ref(props.assignedPhoneNumbers || [])
const availablePhoneNumbers = ref(props.availablePhoneNumbers || [])
const loadingNumbers = ref(false)
const assigningNumber = ref(false)
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

const assignPhoneNumber = async (phoneNumberId) => {
    assigningNumber.value = true
    try {
        const response = await fetch(`/messaging-profiles/${props.messagingProfile.id}/assign-phone`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                phone_number_id: phoneNumberId
            })
        })
        
        if (response.ok) {
            showToast('Phone number assigned successfully!', 'success')
            // Refresh the page to update the assignments
            setTimeout(() => {
                router.reload()
            }, 1000)
        } else {
            const result = await response.json()
            showToast('Failed to assign phone number: ' + (result.message || 'Unknown error'), 'error')
        }
    } catch (error) {
        console.error('Failed to assign phone number:', error)
        showToast('Failed to assign phone number', 'error')
    } finally {
        assigningNumber.value = false
    }
}

const unassignPhoneNumber = async (phoneNumberId) => {
    if (!confirm('Are you sure you want to unassign this phone number from the messaging profile?')) {
        return
    }
    
    assigningNumber.value = true
    try {
        const response = await fetch(`/messaging-profiles/${props.messagingProfile.id}/unassign-phone`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                phone_number_id: phoneNumberId
            })
        })
        
        if (response.ok) {
            showToast('Phone number unassigned successfully!', 'success')
            // Refresh the page to update the assignments
            setTimeout(() => {
                router.reload()
            }, 1000)
        } else {
            const result = await response.json()
            showToast('Failed to unassign phone number: ' + (result.message || 'Unknown error'), 'error')
        }
    } catch (error) {
        console.error('Failed to unassign phone number:', error)
        showToast('Failed to unassign phone number', 'error')
    } finally {
        assigningNumber.value = false
    }
}

const deleteProfile = async () => {
    if (confirm('Are you sure you want to delete this messaging profile? This action cannot be undone.')) {
        try {
            const response = await fetch(`/messaging-profiles/${props.messagingProfile.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            
            if (response.ok) {
                showToast('Messaging profile deleted successfully!', 'success')
                setTimeout(() => {
                    router.visit(route('messaging-profiles.index'))
                }, 1000)
            } else {
                const result = await response.json()
                showToast('Failed to delete messaging profile: ' + (result.message || 'Unknown error'), 'error')
            }
        } catch (error) {
            showToast('Failed to delete messaging profile', 'error')
        }
    }
}

// Data is already loaded from the backend via props, no need for onMounted
</script>

