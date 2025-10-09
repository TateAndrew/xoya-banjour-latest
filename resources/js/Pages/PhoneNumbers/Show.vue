<template>
    <Head :title="`Phone Number - ${phoneNumber.phone_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Phone Number Details
                </h2>
                <Link :href="route('phone-numbers.index')" class="text-indigo-600 hover:text-indigo-800">
                    ← Back to Numbers
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Phone Number Info -->
                        <div class="mb-8">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900">{{ formatPhoneNumber(phoneNumber.phone_number) }}</h3>
                                    <p class="text-gray-600">{{ phoneNumber.city }}, {{ phoneNumber.state }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium" :class="getStatusBadgeClass(phoneNumber.status)">
                                        {{ phoneNumber.status }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="text-lg font-medium mb-3">Number Information</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Country</dt>
                                            <dd class="text-sm text-gray-900">{{ phoneNumber.country_code }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Area Code</dt>
                                            <dd class="text-sm text-gray-900">{{ phoneNumber.area_code || 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Carrier</dt>
                                            <dd class="text-sm text-gray-900">{{ phoneNumber.carrier || 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                                            <dd class="text-sm text-gray-900 capitalize">{{ phoneNumber.number_type }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div>
                                    <h4 class="text-lg font-medium mb-3">Capabilities</h4>
                                    <div class="space-y-2">
                                        <div v-for="capability in phoneNumber.capabilities" :key="capability" class="flex items-center">
                                            <span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                            <span class="text-sm text-gray-900 capitalize">{{ capability }}</span>
                                        </div>
                                    </div>

                                    <h4 class="text-lg font-medium mb-3 mt-6">Pricing</h4>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Monthly Rate</dt>
                                            <dd class="text-sm text-gray-900">${{ phoneNumber.monthly_rate }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Setup Fee</dt>
                                            <dd class="text-sm text-gray-900">${{ phoneNumber.setup_fee }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Telnyx Data -->
                        <!-- <div v-if="telnyxData.success" class="mb-8">
                            <h4 class="text-lg font-medium mb-3">Telnyx Information</h4>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-sm text-gray-700 overflow-x-auto">{{ JSON.stringify(telnyxData.data, null, 2) }}</pre>
                            </div>
                        </div> -->

                        <!-- Actions -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium mb-4">Actions</h4>
                            <div class="flex flex-wrap gap-4">
                                <Link 
                                    :href="route('phone-numbers.edit-recording-settings', phoneNumber.id)" 
                                    class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Recording Settings
                                </Link>
                                <button @click="releaseNumber" :disabled="releasing" class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:opacity-50 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    {{ releasing ? 'Releasing...' : 'Release Number' }}
                                </button>
                                <Link :href="route('phone-numbers.index')" class="inline-flex items-center bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back to Numbers
                                </Link>
                            </div>
                        </div>

                        <!-- Purchase History -->
                        <div class="border-t pt-6 mt-6">
                            <h4 class="text-lg font-medium mb-3">Purchase History</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Purchased On</dt>
                                    <dd class="text-sm text-gray-900">{{ formatDate(phoneNumber.purchased_at) }}</dd>
                                </div>
                                <div v-if="phoneNumber.expires_at">
                                    <dt class="text-sm font-medium text-gray-500">Expires On</dt>
                                    <dd class="text-sm text-gray-900">{{ formatDate(phoneNumber.expires_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const props = defineProps({
    phoneNumber: Object,
    telnyxData: Object
})

const releasing = ref(false)

const releaseNumber = async () => {
    if (!confirm('Are you sure you want to release this phone number? This action cannot be undone.')) {
        return
    }

    releasing.value = true
    try {
        const response = await axios.delete(route('phone-numbers.destroy', props.phoneNumber.id))
        
        if (response.data.success) {
            alert('Phone number released successfully!')
            router.visit(route('phone-numbers.index'))
        } else {
            alert('Error releasing number: ' + response.data.error)
        }
    } catch (error) {
        console.error('Release error:', error)
        if (error.response?.data?.error) {
            alert('Error releasing number: ' + error.response.data.error)
        } else {
            alert('Error releasing number. Please try again.')
        }
    } finally {
        releasing.value = false
    }
}

const formatPhoneNumber = (number) => {
    return number.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'purchased': return 'bg-green-100 text-green-800'
        case 'pending': return 'bg-yellow-100 text-yellow-800'
        case 'failed': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}
</script> 