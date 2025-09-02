<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    SIP Trunk: {{ sipTrunk.name }}
                </h2>
                <div class="flex space-x-3">
                    <Link
                        :href="route('sip-trunks.edit', sipTrunk.id)"
                        class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md font-semibold text-xs text-blue-700 uppercase tracking-widest shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('sip-trunks.index')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash.success" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ $page.props.flash.success }}
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ sipTrunk.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1">
                                            <span
                                                :class="{
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                                                    'bg-green-100 text-green-800': sipTrunk.status === 'active',
                                                    'bg-yellow-100 text-yellow-800': sipTrunk.status === 'pending',
                                                    'bg-red-100 text-red-800': sipTrunk.status === 'failed',
                                                    'bg-gray-100 text-gray-800': sipTrunk.status === 'inactive'
                                                }"
                                            >
                                                {{ sipTrunk.status }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Connection Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ sipTrunk.connection_type }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(sipTrunk.created_at) }}</dd>
                                    </div>
                                    <div v-if="sipTrunk.activated_at">
                                        <dt class="text-sm font-medium text-gray-500">Activated</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(sipTrunk.activated_at) }}</dd>
                                    </div>
                                    <div v-if="sipTrunk.last_health_check">
                                        <dt class="text-sm font-medium text-gray-500">Last Health Check</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(sipTrunk.last_health_check) }}</dd>
                                    </div>
                                </div>
                                
                                <div v-if="sipTrunk.notes" class="mt-4">
                                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ sipTrunk.notes }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Telnyx Connection Details -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Telnyx Connection</h3>
                                <div v-if="telnyxDetails.success" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Connection ID</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ telnyxDetails.connection_id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ telnyxDetails.status }}</dd>
                                    </div>
                                    <div v-if="telnyxDetails.created_at">
                                        <dt class="text-sm font-medium text-gray-500">Created in Telnyx</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(telnyxDetails.created_at) }}</dd>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-red-600">
                                    Failed to load Telnyx connection details: {{ telnyxDetails.error }}
                                </div>
                            </div>
                        </div>

                        <!-- Phone Numbers -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Assigned Phone Numbers</h3>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm text-gray-500">{{ sipTrunk.phone_numbers.length }} assigned</span>
                                        <button
                                            @click="showAssignModal = true"
                                            class="inline-flex items-center px-3 py-1 border border-blue-300 rounded-md text-xs text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        >
                                            Assign Number
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="sipTrunk.phone_numbers.length > 0" class="space-y-3">
                                    <div
                                        v-for="phoneNumber in sipTrunk.phone_numbers"
                                        :key="phoneNumber.id"
                                        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ phoneNumber.phone_number }}
                                                </span>
                                                <span
                                                    :class="{
                                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium': true,
                                                        'bg-blue-100 text-blue-800': phoneNumber.pivot.assignment_type === 'primary',
                                                        'bg-green-100 text-green-800': phoneNumber.pivot.assignment_type === 'secondary',
                                                        'bg-orange-100 text-orange-800': phoneNumber.pivot.assignment_type === 'backup'
                                                    }"
                                                >
                                                    {{ phoneNumber.pivot.assignment_type }}
                                                </span>
                                                <span
                                                    :class="{
                                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium': true,
                                                        'bg-green-100 text-green-800': phoneNumber.pivot.is_active,
                                                        'bg-red-100 text-red-800': !phoneNumber.pivot.is_active
                                                    }"
                                                >
                                                    {{ phoneNumber.pivot.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                Assigned: {{ formatDate(phoneNumber.pivot.assigned_at) }}
                                                <span v-if="phoneNumber.pivot.last_used_at">
                                                    • Last used: {{ formatDate(phoneNumber.pivot.last_used_at) }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                            <Link
                                                :href="route('phone-numbers.show', phoneNumber.id)"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                            >
                                                View Details
                                            </Link>
                                            <button
                                                @click="unassignPhoneNumber(phoneNumber.id)"
                                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                :disabled="unassigning === phoneNumber.id"
                                            >
                                                {{ unassigning === phoneNumber.id ? 'Unassigning...' : 'Unassign' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-8 text-gray-500">
                                    <PhoneIcon class="mx-auto h-12 w-12 text-gray-400 mb-2" />
                                    <p>No phone numbers assigned to this trunk</p>
                                    <button
                                        @click="showAssignModal = true"
                                        class="mt-3 inline-flex items-center px-4 py-2 border border-blue-300 rounded-md text-sm text-blue-700 bg-blue-50 hover:bg-blue-100"
                                    >
                                        Assign Phone Number
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Call History -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Calls</h3>
                                <div v-if="sipTrunk.calls && sipTrunk.calls.length > 0" class="space-y-3">
                                    <div
                                        v-for="call in sipTrunk.calls.slice(0, 5)"
                                        :key="call.id"
                                        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ call.from }} → {{ call.to }}
                                                </span>
                                                <span
                                                    :class="{
                                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium': true,
                                                        'bg-green-100 text-green-800': call.status === 'completed',
                                                        'bg-red-100 text-red-800': call.status === 'failed',
                                                        'bg-yellow-100 text-yellow-800': call.status === 'in-progress'
                                                    }"
                                                >
                                                    {{ call.status }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ formatDate(call.created_at) }}
                                                <span v-if="call.duration">
                                                    • Duration: {{ formatDuration(call.duration) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="sipTrunk.calls.length > 5" class="text-center py-2">
                                        <Link
                                            :href="route('dialer.history')"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            View all calls →
                                        </Link>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-8 text-gray-500">
                                    <p>No calls made through this trunk yet</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                                <div class="space-y-3">
                                    <!-- Test Connection -->
                                    <button
                                        @click="testConnection"
                                        :disabled="testing"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                    >
                                        <span v-if="testing">Testing...</span>
                                        <span v-else>Test Connection</span>
                                    </button>

                                    <!-- Status Toggle -->
                                    <button
                                        v-if="sipTrunk.status === 'active'"
                                        @click="deactivateTrunk"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-yellow-300 rounded-md font-semibold text-xs text-yellow-700 uppercase tracking-widest shadow-sm hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                    >
                                        Deactivate Trunk
                                    </button>
                                    <button
                                        v-else
                                        @click="activateTrunk"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-green-300 rounded-md font-semibold text-xs text-green-700 uppercase tracking-widest shadow-sm hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Activate Trunk
                                    </button>

                                    <!-- Use for Calls -->
                                    <Link
                                        :href="route('dialer')"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-blue-300 rounded-md font-semibold text-xs text-blue-700 uppercase tracking-widest shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Use for Calls
                                    </Link>

                                    <!-- Delete -->
                                    <button
                                        @click="deleteTrunk"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        Delete Trunk
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Phone Numbers</span>
                                        <span class="text-sm font-medium text-gray-900">{{ sipTrunk.phone_numbers.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Total Calls</span>
                                        <span class="text-sm font-medium text-gray-900">{{ sipTrunk.calls ? sipTrunk.calls.length : 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Days Active</span>
                                        <span class="text-sm font-medium text-gray-900">{{ getDaysActive() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phone Number Assignment Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Assign Phone Number</h3>
                        <button
                            @click="showAssignModal = false"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div v-if="availablePhoneNumbers.length > 0" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Phone Number</label>
                            <select
                                v-model="selectedPhoneNumber"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Choose a phone number</option>
                                <option v-for="phoneNumber in availablePhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
                                    {{ phoneNumber.phone_number }} ({{ phoneNumber.number_type || 'local' }})
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Type</label>
                            <select
                                v-model="assignmentType"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="backup">Backup</option>
                            </select>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                @click="showAssignModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                            >
                                Cancel
                            </button>
                            <button
                                @click="assignPhoneNumber"
                                :disabled="!selectedPhoneNumber || assigning"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ assigning ? 'Assigning...' : 'Assign' }}
                            </button>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-6">
                        <PhoneIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <p class="text-gray-500 mb-4">No phone numbers available for assignment</p>
                        <Link
                            :href="route('phone-numbers.index')"
                            class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md text-sm text-blue-700 bg-blue-50 hover:bg-blue-100"
                        >
                            Purchase Phone Numbers
                        </Link>
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
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { PhoneIcon } from '@heroicons/vue/24/outline'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
    sipTrunk: Object,
    telnyxDetails: Object,
    availablePhoneNumbers: Array
})

const testing = ref(false)
const unassigning = ref(null)
const assigning = ref(false)
const showAssignModal = ref(false)
const selectedPhoneNumber = ref('')
const assignmentType = ref('primary')
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString()
}

const formatDuration = (seconds) => {
    if (!seconds) return 'N/A'
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const getDaysActive = () => {
    if (!props.sipTrunk.created_at) return 0
    const created = new Date(props.sipTrunk.created_at)
    const now = new Date()
    const diffTime = Math.abs(now - created)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays
}

const assignPhoneNumber = async () => {
    if (!selectedPhoneNumber.value) return
    
    assigning.value = true
    try {
        const response = await fetch(route('sip-trunks.assign-number', props.sipTrunk.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                phone_number_id: selectedPhoneNumber.value,
                assignment_type: assignmentType.value
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            showToast('Phone number assigned successfully!', 'success')
            showAssignModal.value = false
            selectedPhoneNumber.value = ''
            assignmentType.value = 'primary'
            window.location.reload()
        } else {
            showToast('Failed to assign phone number: ' + result.message, 'error')
        }
    } catch (error) {
        showToast('Failed to assign phone number. Please try again.', 'error')
    } finally {
        assigning.value = false
    }
}

const testConnection = async () => {
    testing.value = true
    try {
        const response = await fetch(route('sip-trunks.test', props.sipTrunk.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        
        const result = await response.json()
        
        if (result.success) {
            showToast('Connection test successful!', 'success')
            window.location.reload()
        } else {
            showToast('Connection test failed: ' + result.message, 'error')
        }
    } catch (error) {
        showToast('Test failed. Please try again.', 'error')
    } finally {
        testing.value = false
    }
}

const activateTrunk = async () => {
    if (confirm('Are you sure you want to activate this SIP trunk?')) {
        try {
            await fetch(route('sip-trunks.activate', props.sipTrunk.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            showToast('SIP trunk activated successfully!', 'success')
            window.location.reload()
        } catch (error) {
            showToast('Failed to activate trunk. Please try again.', 'error')
        }
    }
}

const deactivateTrunk = async () => {
    if (confirm('Are you sure you want to deactivate this SIP trunk?')) {
        try {
            await fetch(route('sip-trunks.deactivate', props.sipTrunk.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            showToast('SIP trunk deactivated successfully!', 'success')
            window.location.reload()
        } catch (error) {
            showToast('Failed to deactivate trunk. Please try again.', 'error')
        }
    }
}

const deleteTrunk = async () => {
    if (confirm('Are you sure you want to delete this SIP trunk? This action cannot be undone.')) {
        try {
            await fetch(route('sip-trunks.destroy', props.sipTrunk.id), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            showToast('SIP trunk deleted successfully!', 'success')
            window.location.href = route('sip-trunks.index')
        } catch (error) {
            showToast('Failed to delete trunk. Please try again.', 'error')
        }
    }
}

const unassignPhoneNumber = async (phoneNumberId) => {
    if (confirm('Are you sure you want to unassign this phone number from the SIP trunk?')) {
        unassigning.value = phoneNumberId
        try {
            const response = await fetch(route('sip-trunks.unassign-number', [props.sipTrunk.id, phoneNumberId]), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            
            const result = await response.json()
            
            if (result.success) {
                showToast('Phone number unassigned successfully!', 'success')
                window.location.reload()
            } else {
                showToast('Failed to unassign phone number: ' + result.message, 'error')
            }
        } catch (error) {
            showToast('Failed to unassign phone number. Please try again.', 'error')
        } finally {
            unassigning.value = null
        }
    }
}
</script>
