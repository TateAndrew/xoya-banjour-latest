<template>
    <Head title="Purchase Phone Number" />

    <DashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Purchase Phone Number
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Search Available Numbers</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Country Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <select 
                                    v-model="searchForm.country_code" 
                                    @change="onCountryChange"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">Select Country</option>
                                    <option v-for="country in countries" :key="country.country_code" :value="country.country_code">
                                        {{ country.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Area Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Area Code</label>
                                <select 
                                    v-model="searchForm.area_code" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    :disabled="!searchForm.country_code"
                                >
                                    <option value="">Any Area Code</option>
                                    <option v-for="areaCode in areaCodes" :key="areaCode.area_code" :value="areaCode.area_code">
                                        {{ areaCode.area_code }} ({{ areaCode.city }}, {{ areaCode.state }})
                                    </option>
                                </select>
                            </div>

                            <!-- Features -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input 
                                            v-model="searchForm.features" 
                                            type="checkbox" 
                                            value="voice" 
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 text-sm">Voice</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            v-model="searchForm.features" 
                                            type="checkbox" 
                                            value="sms" 
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 text-sm">SMS</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            v-model="searchForm.features" 
                                            type="checkbox" 
                                            value="mms" 
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 text-sm">MMS</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Search Button -->
                            <div class="flex items-end">
                                <button 
                                    @click="searchNumbers" 
                                    :disabled="searching || !canSearch"
                                    class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="searching">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Searching...
                                    </span>
                                    <span v-else>Search Numbers</span>
                                </button>
                            </div>
                        </div>

                        <!-- Advanced Options -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button 
                                @click="showAdvanced = !showAdvanced"
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Options
                            </button>
                            
                            <div v-if="showAdvanced" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Number Type</label>
                                    <select v-model="searchForm.number_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Any Type</option>
                                        <option value="local">Local</option>
                                        <option value="toll-free">Toll Free</option>
                                        <option value="vanity">Vanity</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Monthly Cost</label>
                                    <input 
                                        v-model="searchForm.max_monthly_cost" 
                                        type="number" 
                                        min="0" 
                                        step="0.01" 
                                        placeholder="0.00"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    >
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Results Limit</label>
                                    <select v-model="searchForm.limit" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Results -->
                <div v-if="searchResults.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Available Numbers ({{ searchResults.length }})
                            </h3>
                            <div class="text-sm text-gray-500">
                                Showing {{ searchResults.length }} of {{ totalResults }} results
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="number in searchResults" 
                                :key="number.phone_number" 
                                class="border rounded-lg p-4 hover:shadow-md transition-shadow bg-gray-50"
                            >
                                <div class="flex justify-between items-start mb-3">
                                    <div class="text-lg font-semibold text-gray-900">
                                        {{ formatPhoneNumber(number.phone_number) }}
                                    </div>
                                    <button 
                                        @click="purchaseNumber(number)" 
                                        :disabled="purchasing === number.phone_number"
                                        class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="purchasing === number.phone_number">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Buying...
                                        </span>
                                        <span v-else>Purchase</span>
                                    </button>
                                </div>

                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded capitalize">
                                            {{ number.phone_number_type }}
                                        </span>
                                    </div>
                                    
                                    <div v-if="number.city || number.state" class="text-gray-500">
                                        {{ number.city }}{{ number.city && number.state ? ', ' : '' }}{{ number.state }}
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <span class="font-medium">Monthly:</span>
                                            <span class="text-green-600">${{ number.cost_information?.monthly_cost || '0.00' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Setup:</span>
                                            <span class="text-orange-600">${{ number.cost_information?.upfront_cost || '0.00' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="feature in number.features" 
                                            :key="feature" 
                                            class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded"
                                        >
                                            {{ feature.name.toUpperCase()}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <div v-if="hasMoreResults" class="mt-6 text-center">
                            <button 
                                @click="loadMoreResults" 
                                :disabled="loadingMore"
                                class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 disabled:opacity-50"
                            >
                                {{ loadingMore ? 'Loading...' : 'Load More Results' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- No Results Message -->
                <div v-if="searched && searchResults.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 mb-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.554.89l-1.9 9.5a2 2 0 01-1.9 1.5H3a2 2 0 01-2-2V5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h3m-3 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Phone Numbers Found</h3>
                        <p class="text-gray-500">Try adjusting your search criteria or expanding your search area.</p>
                    </div>
                </div>

                <!-- Purchase Confirmation Modal -->
                <div v-if="showPurchaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3 text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Purchase</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Are you sure you want to purchase 
                                <span class="font-semibold">{{ formatPhoneNumber(selectedNumber?.phone_number) }}</span>?
                            </p>
                            
                            <div class="bg-gray-50 p-4 rounded-lg mb-4 text-left">
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>Monthly Cost: <span class="font-medium text-green-600">${{ selectedNumber?.cost_information?.monthly_cost || '0.00' }}</span></div>
                                    <div>Setup Fee: <span class="font-medium text-orange-600">${{ selectedNumber?.cost_information?.upfront_cost || '0.00' }}</span></div>
                                </div>
                            </div>
                            
                            <div class="flex justify-center space-x-3">
                                <button 
                                    @click="showPurchaseModal = false"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600"
                                >
                                    Cancel
                                </button>
                                <button 
                                    @click="confirmPurchase"
                                    :disabled="purchasing === selectedNumber?.phone_number"
                                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50"
                                >
                                    {{ purchasing === selectedNumber?.phone_number ? 'Purchasing...' : 'Confirm Purchase' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const searchForm = reactive({
    country_code: 'US',
    area_code: '',
    features: ['voice', 'sms'],
    number_type: '',
    max_monthly_cost: '',
    limit: 20
})

const countries = ref([])
const areaCodes = ref([])
const searchResults = ref([])
const searching = ref(false)
const purchasing = ref(null)
const searched = ref(false)
const showAdvanced = ref(false)
const showPurchaseModal = ref(false)
const selectedNumber = ref(null)
const totalResults = ref(0)
const hasMoreResults = ref(false)
const loadingMore = ref(false)

const canSearch = computed(() => {
    return searchForm.country_code && searchForm.features.length > 0
})

onMounted(async () => {
    await loadCountries()
    await loadAreaCodes()
})

const loadCountries = async () => {
    try {
        const response = await axios.get('/api/telnyx/countries')
        if (response.data.success) {
            countries.value = response.data.data
        }
    } catch (error) {
        console.error('Error loading countries:', error)
    }
}

const loadAreaCodes = async () => {
    if (!searchForm.country_code) return
    
    try {
        const response = await axios.get(`/api/telnyx/area-codes/${searchForm.country_code}`)
        if (response.data.success) {
            areaCodes.value = response.data.data
        }
    } catch (error) {
        console.error('Error loading area codes:', error)
    }
}

const onCountryChange = () => {
    searchForm.area_code = ''
    loadAreaCodes()
}

const searchNumbers = async () => {
    if (!canSearch.value) return
    
    searching.value = true
    searchResults.value = []
    
    try {
        const response = await axios.post(route('phone-numbers.search'), searchForm)
        
        if (response.data.success) {
            searchResults.value = response.data.data
            totalResults.value = response.data.total
            hasMoreResults.value = response.data.total > searchForm.limit
            searched.value = true
        } else {
            alert('Error searching numbers: ' + response.data.error)
        }
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

const loadMoreResults = async () => {
    if (loadingMore.value) return
    
    loadingMore.value = true
    const currentLimit = searchForm.limit
    searchForm.limit = Math.min(currentLimit + 20, 100)
    
    try {
        const response = await axios.post(route('phone-numbers.search'), searchForm)
        
        if (response.data.success) {
            searchResults.value = [...searchResults.value, ...response.data.data]
            hasMoreResults.value = response.data.total > searchResults.value.length
        }
    } catch (error) {
        console.error('Load more error:', error)
    } finally {
        loadingMore.value = false
        searchForm.limit = currentLimit
    }
}

const purchaseNumber = (number) => {
    selectedNumber.value = number
    showPurchaseModal.value = true
}

const confirmPurchase = async () => {
    if (!selectedNumber.value) return
    
    purchasing.value = selectedNumber.value.phone_number
    
    try {
        const response = await axios.post(route('phone-numbers.purchase'), {
            phone_number: selectedNumber.value.phone_number,
            country_code: searchForm.country_code,
            area_code: searchForm.area_code,
            features: searchForm.features,
            city: selectedNumber.value.city,
            state: selectedNumber.value.state
        })
        
        if (response.data.success) {
            alert('Phone number purchased successfully!')
            showPurchaseModal.value = false
            selectedNumber.value = null
            router.visit(route('phone-numbers.index'))
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
        purchasing.value = null
    }
}

const formatPhoneNumber = (number) => {
    return number;
}
</script>
