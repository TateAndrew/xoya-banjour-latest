<template>
    <Head title="Manage Phone Numbers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manage Phone Numbers
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header with Actions -->
                <div class="mb-6 flex justify-between items-center">
                    <h3 class="text-2xl font-bold text-gray-900">Your Phone Numbers</h3>
                    <div class="flex space-x-3">
                        <Link 
                            :href="route('phone-numbers.purchase-page')" 
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors"
                        >
                            Purchase New
                        </Link>
                        <button 
                            @click="refreshAllNumbers"
                            :disabled="refreshing"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50"
                        >
                            <span v-if="refreshing">Refreshing...</span>
                            <span v-else>Refresh All</span>
                        </button>
                    </div>
                </div>

                <!-- Phone Numbers Grid -->
                <div v-if="phoneNumbers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="number in phoneNumbers" 
                        :key="number.id" 
                        class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">
                                        {{ formatPhoneNumber(number.phone_number) }}
                                    </h4>
                                    <p class="text-sm text-gray-500">{{ number.country_code }} • {{ number.area_code || 'N/A' }}</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button 
                                        @click="syncNumber(number)"
                                        :disabled="syncing === number.id"
                                        class="text-blue-600 hover:text-blue-800 p-1"
                                        title="Sync with Telnyx"
                                    >
                                        <svg v-if="syncing === number.id" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="showEditModal(number)"
                                        class="text-indigo-600 hover:text-indigo-800 p-1"
                                        title="Edit Settings"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button 
                                        @click="confirmDelete(number)"
                                        class="text-red-600 hover:text-red-800 p-1"
                                        title="Release Number"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <!-- Status -->
                            <div class="mb-3">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getStatusClasses(number.status)"
                                >
                                    {{ number.status }}
                                </span>
                            </div>

                            <!-- Capabilities -->
                            <div class="mb-3">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Capabilities</h5>
                                <div class="flex flex-wrap gap-1">
                                    <span 
                                        v-for="capability in number.capabilities" 
                                        :key="capability" 
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800"
                                    >
                                        {{ capability.toUpperCase() }}
                                    </span>
                                </div>
                            </div>

                            <!-- Cost Information -->
                            <div class="mb-3">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">Costs</h5>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-gray-500">Monthly:</span>
                                        <span class="font-medium text-green-600 ml-1">${{ number.monthly_rate || '0.00' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Setup:</span>
                                        <span class="font-medium text-orange-600 ml-1">${{ number.setup_fee || '0.00' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Location -->
                            <div v-if="number.city || number.state" class="mb-3">
                                <h5 class="text-sm font-medium text-gray-700 mb-1">Location</h5>
                                <p class="text-sm text-gray-600">
                                    {{ number.city }}{{ number.city && number.state ? ', ' : '' }}{{ number.state }}
                                </p>
                            </div>

                            <!-- Purchase Date -->
                            <div class="text-xs text-gray-500">
                                Purchased: {{ formatDate(number.purchased_at) }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-2">
                                    <Link 
                                        :href="route('phone-numbers.show', number.id)"
                                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                    >
                                        View Details
                                    </Link>
                                    <span class="text-gray-300">|</span>
                                    <Link 
                                        :href="route('phone-numbers.edit-recording-settings', number.id)"
                                        class="text-green-600 hover:text-green-800 text-sm font-medium"
                                    >
                                        Recording Settings
                                    </Link>
                                </div>
                                <Link 
                                    :href="route('dialer')"
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors"
                                >
                                    Use for Calls
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.554.89l-1.9 9.5a2 2 0 01-1.9 1.5H3a2 2 0 01-2-2V5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h3m-3 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Phone Numbers Yet</h3>
                    <p class="text-gray-500 mb-6">Get started by purchasing your first phone number.</p>
                    <Link 
                        :href="route('phone-numbers.purchase-page')" 
                        class="bg-indigo-600 text-white px-6 py-3 rounded-md hover:bg-indigo-700 transition-colors"
                    >
                        Purchase Your First Number
                    </Link>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEdit" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Phone Number Settings</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Capabilities</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input 
                                        v-model="editForm.capabilities" 
                                        type="checkbox" 
                                        value="voice" 
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="ml-2 text-sm">Voice</span>
                                </label>
                                <label class="flex items-center">
                                    <input 
                                        v-model="editForm.capabilities" 
                                        type="checkbox" 
                                        value="sms" 
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="ml-2 text-sm">SMS</span>
                                </label>
                                <label class="flex items-center">
                                    <input 
                                        v-model="editForm.capabilities" 
                                        type="checkbox" 
                                        value="mms" 
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                    <span class="ml-2 text-sm">MMS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button 
                            @click="showEdit = false"
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="saveChanges"
                            :disabled="saving"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ saving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Release Phone Number</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Are you sure you want to release 
                        <span class="font-semibold">{{ formatPhoneNumber(numberToDelete?.phone_number) }}</span>?
                        This action cannot be undone.
                    </p>
                    
                    <div class="flex justify-center space-x-3">
                        <button 
                            @click="showDeleteModal = false"
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="deleteNumber"
                            :disabled="deleting"
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ deleting ? 'Releasing...' : 'Release Number' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const props = defineProps({
    phoneNumbers: Array
})

const phoneNumbers = ref(props.phoneNumbers || [])
const refreshing = ref(false)
const syncing = ref(null)
const saving = ref(false)
const deleting = ref(false)
const showEdit = ref(false)
const showDeleteModal = ref(false)
const editingNumber = ref(null)
const numberToDelete = ref(null)

const editForm = reactive({
    capabilities: []
})

onMounted(() => {
    // Initialize phone numbers from props
    phoneNumbers.value = props.phoneNumbers || []
})

const refreshAllNumbers = async () => {
    refreshing.value = true
    try {
        await router.reload()
    } finally {
        refreshing.value = false
    }
}

const syncNumber = async (number) => {
    syncing.value = number.id
    try {
        const response = await axios.post(route('phone-numbers.sync', number.id))
        
        if (response.data.success) {
            // Update the local number data
            const index = phoneNumbers.value.findIndex(n => n.id === number.id)
            if (index !== -1) {
                phoneNumbers.value[index] = response.data.data
            }
            alert('Phone number synced successfully!')
        } else {
            alert('Error syncing number: ' + response.data.error)
        }
    } catch (error) {
        console.error('Sync error:', error)
        if (error.response?.data?.error) {
            alert('Error syncing number: ' + error.response.data.error)
        } else {
            alert('Error syncing number. Please try again.')
        }
    } finally {
        syncing.value = null
    }
}

const showEditModal = (number) => {
    editingNumber.value = number
    editForm.capabilities = [...(number.capabilities || [])]
    showEdit.value = true
}

const saveChanges = async () => {
    if (!editingNumber.value) return
    
    saving.value = true
    try {
        const response = await axios.put(route('phone-numbers.update', editingNumber.value.id), {
            capabilities: editForm.capabilities
        })
        
        if (response.data.success) {
            // Update the local number data
            const index = phoneNumbers.value.findIndex(n => n.id === editingNumber.value.id)
            if (index !== -1) {
                phoneNumbers.value[index] = response.data.data
            }
            showEdit.value = false
            editingNumber.value = null
            alert('Phone number updated successfully!')
        } else {
            alert('Error updating number: ' + response.data.error)
        }
    } catch (error) {
        console.error('Update error:', error)
        if (error.response?.data?.error) {
            alert('Error updating number: ' + error.response.data.error)
        } else {
            alert('Error updating number. Please try again.')
        }
    } finally {
        saving.value = false
    }
}

const confirmDelete = (number) => {
    numberToDelete.value = number
    showDeleteModal.value = true
}

const deleteNumber = async () => {
    if (!numberToDelete.value) return
    
    deleting.value = true
    try {
        const response = await axios.delete(route('phone-numbers.destroy', numberToDelete.value.id))
        
        if (response.data.success) {
            // Remove from local array
            phoneNumbers.value = phoneNumbers.value.filter(n => n.id !== numberToDelete.value.id)
            showDeleteModal.value = false
            numberToDelete.value = null
            alert('Phone number released successfully!')
        } else {
            alert('Error releasing number: ' + response.data.error)
        }
    } catch (error) {
        console.error('Delete error:', error)
        if (error.response?.data?.error) {
            alert('Error releasing number: ' + error.response.data.error)
        } else {
            alert('Error releasing number. Please try again.')
        }
    } finally {
        deleting.value = false
    }
}

const formatPhoneNumber = (number) => {
    if (!number) return ''
    return number.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString()
}

const getStatusClasses = (status) => {
    switch (status) {
        case 'purchased':
            return 'bg-green-100 text-green-800'
        case 'pending':
            return 'bg-yellow-100 text-yellow-800'
        case 'failed':
            return 'bg-red-100 text-red-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}
</script>
