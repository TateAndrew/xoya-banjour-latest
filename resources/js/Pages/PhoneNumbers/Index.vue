<template>
    <Head title="Phone Numbers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Phone Numbers
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Search Section -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium mb-4">Search Available Numbers</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Country</label>
                                    <select v-model="searchForm.country_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="AU">Australia</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Area Code</label>
                                    <input v-model="searchForm.area_code" type="text" placeholder="e.g., 212" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Features</label>
                                    <div class="mt-1 space-y-2">
                                        <label class="flex items-center">
                                            <input v-model="searchForm.features" type="checkbox" value="voice" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <span class="ml-2 text-sm">Voice</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input v-model="searchForm.features" type="checkbox" value="sms" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            <span class="ml-2 text-sm">SMS</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="flex items-end">
                                    <button @click="searchNumbers" :disabled="searching" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                                        {{ searching ? 'Searching...' : 'Search' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div v-if="searchResults.length > 0" class="mb-8">
                            <h3 class="text-lg font-medium mb-4">Available Numbers ({{ searchResults.length }})</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="number in searchResults" :key="number.phone_number" class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-lg font-semibold">{{ formatPhoneNumber(number.phone_number) }}</div>
                                        <button @click="purchaseNumber(number)" :disabled="purchasing" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 disabled:opacity-50">
                                            {{ purchasing ? 'Purchasing...' : 'Purchase' }}
                                        </button>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <div class="mb-2">
                                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded capitalize">
                                                {{ number.phone_number_type }}
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Monthly Cost:</strong> ${{ number.cost_information?.monthly_cost || 'N/A' }}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Setup Cost:</strong> ${{ number.cost_information?.upfront_cost || 'N/A' }}
                                        </div>
                                        <div class="mt-1">
                                            <span v-for="feature in getFeatureNames(number.features)" :key="feature" class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                                {{ feature.toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User's Numbers -->
                        <div v-if="userNumbers.length > 0">
                            <h3 class="text-lg font-medium mb-4">Your Phone Numbers</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="number in userNumbers" :key="number.id" class="border rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-lg font-semibold">{{ formatPhoneNumber(number.phone_number) }}</div>
                                        <Link :href="route('phone-numbers.show', number.id)" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                            View Details
                                        </Link>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <div>Status: <span class="font-medium" :class="getStatusColor(number.status)">{{ number.status }}</span></div>
                                        <div class="mt-1">
                                            <span v-for="capability in number.capabilities" :key="capability" class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                                {{ capability.toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">
                                            Purchased: {{ formatDate(number.purchased_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No Results Message -->
                        <div v-if="searched && searchResults.length === 0" class="text-center py-8">
                            <p class="text-gray-500">No phone numbers found matching your criteria.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    userNumbers: Array
})

const searchForm = reactive({
    country_code: 'US',
    area_code: '',
    features: ['voice', 'sms'],
    limit: 20
})

const searchResults = ref([])
const searching = ref(false)
const purchasing = ref(false)
const searched = ref(false)

const searchNumbers = async () => {
    searching.value = true
    try {
        const response = await axios.post(route('phone-numbers.search'), searchForm)
        
        if (response.data.success) {
            searchResults.value = response.data.data
        } else {
            alert('Error searching numbers: ' + response.data.error)
        }
        searched.value = true
    } catch (error) {
        console.error('Search error:', error)
        if (error.response?.data?.error) {
            alert('Error searching numbers: ' + error.response.data.error)
        } else {
            alert('Error searching numbers. Please try again.')
        }
    } finally {
        searching.value = false
    }
}

const purchaseNumber = async (number) => {
    if (!confirm(`Are you sure you want to purchase ${formatPhoneNumber(number.phone_number)}?`)) {
        return
    }

    purchasing.value = true
    try {
        const response = await axios.post(route('phone-numbers.purchase'), {
            phone_number: number.phone_number,
            country_code: searchForm.country_code,
            features: searchForm.features
        })
        
        if (response.data.success) {
            alert('Phone number purchased successfully!')
            router.reload()
        } else {
            alert('Error purchasing number: ' + response.data.error)
        }
    } catch (error) {
        console.error('Purchase error:', error)
        if (error.response?.data?.error) {
            alert('Error purchasing number: ' + error.response.data.error)
        } else {
            alert('Error purchasing number. Please try again.')
        }
    } finally {
        purchasing.value = false
    }
}

const formatPhoneNumber = (number) => {
    return number.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const getStatusColor = (status) => {
    switch (status) {
        case 'purchased': return 'text-green-600'
        case 'pending': return 'text-yellow-600'
        case 'failed': return 'text-red-600'
        default: return 'text-gray-600'
    }
}

const getFeatureNames = (features) => {
    if (!features || !Array.isArray(features)) return []
    return features.map(feature => feature.name || feature)
}
</script> 