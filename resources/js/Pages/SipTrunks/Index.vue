<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                SIP Trunks
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-semibold text-gray-900">SIP Trunks</h1>
                            <Link
                                :href="route('sip-trunks.create')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create New SIP Trunk
                            </Link>
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
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Phone Numbers
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
                                    <tr v-for="sipTrunk in sipTrunks.data" :key="sipTrunk.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ sipTrunk.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: {{ sipTrunk.telnyx_connection_id }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="{
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800': sipTrunk.status === 'active',
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800': sipTrunk.status === 'pending',
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800': sipTrunk.status === 'failed',
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800': sipTrunk.status === 'inactive'
                                                }"
                                            >
                                                {{ sipTrunk.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ sipTrunk.connection_type }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ sipTrunk.phone_numbers?.length || 0 }} assigned
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(sipTrunk.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button
                                                    @click="testConnection(sipTrunk.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                    :disabled="testing === sipTrunk.id"
                                                >
                                                    {{ testing === sipTrunk.id ? 'Testing...' : 'Test' }}
                                                </button>
                                                <button
                                                    v-if="sipTrunk.status === 'inactive'"
                                                    @click="activateTrunk(sipTrunk.id)"
                                                    class="text-green-600 hover:text-green-900"
                                                >
                                                    Activate
                                                </button>
                                                <button
                                                    v-if="sipTrunk.status === 'active'"
                                                    @click="deactivateTrunk(sipTrunk.id)"
                                                    class="text-yellow-600 hover:text-yellow-900"
                                                >
                                                    Deactivate
                                                </button>
                                                <Link
                                                    :href="route('sip-trunks.show', sipTrunk.id)"
                                                    class="text-blue-600 hover:text-blue-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('sip-trunks.edit', sipTrunk.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    @click="deleteTrunk(sipTrunk.id)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Delete
                                                </button>
                                                <button
                                                    @click="showAssignNumberModal(sipTrunk)"
                                                    class="text-green-600 hover:text-green-900"
                                                >
                                                    Assign Number
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Assign Number Modal -->
                        <div v-if="showAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <div class="mt-3">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Phone Number</h3>
                                    <form @submit.prevent="assignPhoneNumber">
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Select Phone Number
                                            </label>
                                            <select 
                                                v-model="selectedPhoneNumber" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                required
                                            >
                                                <option value="">Choose a phone number...</option>
                                                <option 
                                                    v-for="number in availablePhoneNumbers" 
                                                    :key="number.id" 
                                                    :value="number.id"
                                                >
                                                    {{ number.formatted_number || number.phone_number }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Assignment Type
                                            </label>
                                            <select 
                                                v-model="assignmentType" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            >
                                                <option value="primary">Primary</option>
                                                <option value="secondary">Secondary</option>
                                                <option value="backup">Backup</option>
                                            </select>
                                        </div>
                                        <div class="flex justify-end space-x-3">
                                            <button
                                                type="button"
                                                @click="closeAssignModal"
                                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                type="submit"
                                                :disabled="assigning"
                                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                            >
                                                {{ assigning ? 'Assigning...' : 'Assign Number' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="sipTrunks.links && sipTrunks.links.length > 3" class="mt-6">
                            <nav class="flex justify-center">
                                <div class="flex space-x-1">
                                    <Link
                                        v-for="(link, index) in sipTrunks.links"
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
                        <div v-if="sipTrunks.data.length === 0" class="text-center py-12">
                            <div class="text-gray-500">
                                <p class="text-lg font-medium">No SIP trunks found</p>
                                <p class="mt-2">Get started by creating your first SIP trunk.</p>
                                <div class="mt-6">
                                    <Link
                                        :href="route('sip-trunks.create')"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Create SIP Trunk
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
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
    sipTrunks: Object
})

const testing = ref(null)
const showAssignModal = ref(false)
const selectedSipTrunk = ref(null)
const selectedPhoneNumber = ref('')
const assignmentType = ref('primary')
const assigning = ref(false)
const availablePhoneNumbers = ref([])
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

const testConnection = async (trunkId) => {
    testing.value = trunkId
    try {
        const response = await fetch(`/sip-trunks/${trunkId}/test`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        })
        const result = await response.json()
        
        if (result.success) {
            showToast('Connection test successful!', 'success')
        } else {
            showToast('Connection test failed: ' + result.message, 'error')
        }
    } catch (error) {
        showToast('Test failed. Please try again.', 'error')
    } finally {
        testing.value = null
    }
}

const activateTrunk = async (trunkId) => {
    if (confirm('Are you sure you want to activate this SIP trunk?')) {
        try {
            const response = await fetch(`/sip-trunks/${trunkId}/activate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            
            if (response.ok) {
                showToast('SIP trunk activated successfully!', 'success')
                window.location.reload()
            } else {
                showToast('Failed to activate SIP trunk', 'error')
            }
        } catch (error) {
            showToast('Failed to activate SIP trunk', 'error')
        }
    }
}

const deactivateTrunk = async (trunkId) => {
    if (confirm('Are you sure you want to deactivate this SIP trunk?')) {
        try {
            const response = await fetch(`/sip-trunks/${trunkId}/deactivate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            
            if (response.ok) {
                showToast('SIP trunk deactivated successfully!', 'success')
                window.location.reload()
            } else {
                showToast('Failed to deactivate SIP trunk', 'error')
            }
        } catch (error) {
            showToast('Failed to deactivate SIP trunk', 'error')
        }
    }
}

const deleteTrunk = async (trunkId) => {
    if (confirm('Are you sure you want to delete this SIP trunk? This action cannot be undone.')) {
        try {
            const response = await fetch(`/sip-trunks/${trunkId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            
            if (response.ok) {
                showToast('SIP trunk deleted successfully!', 'success')
                window.location.reload()
            } else {
                showToast('Failed to delete SIP trunk', 'error')
            }
        } catch (error) {
            showToast('Failed to delete SIP trunk', 'error')
        }
    }
}

const showAssignNumberModal = async (sipTrunk) => {
    selectedSipTrunk.value = sipTrunk
    selectedPhoneNumber.value = ''
    assignmentType.value = 'primary'
    
    try {
        // Fetch available phone numbers for this user
        const response = await fetch('/api/phone-numbers', {
            headers: {
                'Accept': 'application/json',
            }
        })
        
        if (response.ok) {
            const data = await response.json()
            // Filter out numbers already assigned to any SIP trunk
            availablePhoneNumbers.value = data.filter(number => 
                !number.sip_trunks || number.sip_trunks.length === 0
            )
        }
    } catch (error) {
        console.error('Failed to fetch phone numbers:', error)
    }
    
    showAssignModal.value = true
}

const closeAssignModal = () => {
    showAssignModal.value = false
    selectedSipTrunk.value = null
    selectedPhoneNumber.value = ''
    assignmentType.value = 'primary'
    availablePhoneNumbers.value = []
}

const assignPhoneNumber = async () => {
    if (!selectedPhoneNumber.value) {
        showToast('Please select a phone number', 'warning')
        return
    }
    
    assigning.value = true
    
    try {
        const response = await fetch(`/sip-trunks/${selectedSipTrunk.value.id}/assign-number`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                phone_number_id: selectedPhoneNumber.value,
                assignment_type: assignmentType.value
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            showToast('Phone number assigned successfully!', 'success')
            closeAssignModal()
            window.location.reload() // Refresh to show updated data
        } else {
            showToast('Failed to assign phone number: ' + result.message, 'error')
        }
    } catch (error) {
        console.error('Assignment failed:', error)
        showToast('Failed to assign phone number. Please try again.', 'error')
    } finally {
        assigning.value = false
    }
}
</script>
