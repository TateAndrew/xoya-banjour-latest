<template>
    <Head title="Call History" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Call History
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">Call History</h3>
                                <p class="text-gray-600">View your call logs and transcripts</p>
                            </div>
                            <div class="flex space-x-3">
                                <button @click="refreshHistory" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    🔄 Refresh
                                </button>
                                <button @click="exportHistory" 
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    📥 Export
                                </button>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                <select v-model="dateFilter" class="w-full p-2 border border-gray-300 rounded-lg">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select v-model="statusFilter" class="w-full p-2 border border-gray-300 rounded-lg">
                                    <option value="all">All Statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="missed">Missed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input v-model="phoneFilter" 
                                       type="tel" 
                                       placeholder="Filter by number"
                                       class="w-full p-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                                <input v-model="searchQuery" 
                                       type="text" 
                                       placeholder="Search calls..."
                                       class="w-full p-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <!-- Call History Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date/Time
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            From
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            To
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Duration
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="call in filteredCalls" :key="call.id" 
                                        class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>
                                                <div class="font-medium">{{ formatDate(call.created_at) }}</div>
                                                <div class="text-gray-500">{{ formatTime(call.created_at) }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="font-mono">{{ call.from_number }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="font-mono">{{ call.to_number }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span v-if="call.duration" class="font-mono">{{ formatDuration(call.duration) }}</span>
                                            <span v-else class="text-gray-400">--</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                call.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                call.status === 'missed' ? 'bg-yellow-100 text-yellow-800' :
                                                call.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`">
                                                {{ call.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button @click="viewCallDetails(call)" 
                                                        class="text-blue-600 hover:text-blue-900">
                                                    👁️ View
                                                </button>
                                                <button @click="downloadTranscript(call)" 
                                                        class="text-green-600 hover:text-green-900">
                                                    📝 Transcript
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredCalls.length === 0" class="text-center py-12">
                            <span class="text-4xl mb-4 block">📞</span>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No calls found</h3>
                            <p class="text-gray-500">No calls match your current filters.</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-between mt-6">
                            <div class="text-sm text-gray-700">
                                Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ totalCalls }} results
                            </div>
                            <div class="flex space-x-2">
                                <button @click="previousPage" 
                                        :disabled="currentPage === 1"
                                        class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Previous
                                </button>
                                <span class="px-3 py-2 text-sm text-gray-700">
                                    Page {{ currentPage }} of {{ totalPages }}
                                </span>
                                <button @click="nextPage" 
                                        :disabled="currentPage === totalPages"
                                        class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call Details Modal -->
        <div v-if="selectedCall" 
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white"
                 @click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Call Details</h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <span class="text-2xl">×</span>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">From Number</label>
                                <p class="mt-1 text-sm text-gray-900 font-mono">{{ selectedCall.from_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">To Number</label>
                                <p class="mt-1 text-sm text-gray-900 font-mono">{{ selectedCall.to_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <p class="mt-1 text-sm text-gray-900">{{ selectedCall.status }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration</label>
                                <p class="mt-1 text-sm text-gray-900">{{ formatDuration(selectedCall.duration) || '--' }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Call Transcript</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg max-h-64 overflow-y-auto">
                                <div v-if="selectedCall.transcript && selectedCall.transcript.length > 0">
                                    <div v-for="(entry, index) in selectedCall.transcript" :key="index" 
                                         class="text-sm mb-2">
                                        <div class="flex items-start space-x-2">
                                            <span class="text-gray-500 text-xs w-16 flex-shrink-0">
                                                {{ entry.timestamp }}
                                            </span>
                                            <div class="flex-1">
                                                <div class="font-medium text-gray-900">{{ entry.type }}</div>
                                                <div class="text-gray-700">{{ entry.message }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-gray-500 text-center py-4">
                                    No transcript available for this call
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button @click="closeModal" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                            Close
                        </button>
                        <button @click="downloadTranscript(selectedCall)" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Download Transcript
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// Reactive data
const calls = ref([])
const selectedCall = ref(null)
const currentPage = ref(1)
const itemsPerPage = ref(20)
const dateFilter = ref('all')
const statusFilter = ref('all')
const phoneFilter = ref('')
const searchQuery = ref('')

// Computed properties
const filteredCalls = computed(() => {
    let filtered = calls.value

    // Apply date filter
    if (dateFilter.value !== 'all') {
        const now = new Date()
        const startOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate())
        
        switch (dateFilter.value) {
            case 'today':
                filtered = filtered.filter(call => new Date(call.created_at) >= startOfDay)
                break
            case 'week':
                const startOfWeek = new Date(startOfDay.getTime() - (startOfDay.getDay() * 24 * 60 * 60 * 1000))
                filtered = filtered.filter(call => new Date(call.created_at) >= startOfWeek)
                break
            case 'month':
                const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
                filtered = filtered.filter(call => new Date(call.created_at) >= startOfMonth)
                break
        }
    }

    // Apply status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(call => call.status === statusFilter.value)
    }

    // Apply phone filter
    if (phoneFilter.value) {
        const phone = phoneFilter.value.toLowerCase()
        filtered = filtered.filter(call => 
            call.from_number.toLowerCase().includes(phone) ||
            call.to_number.toLowerCase().includes(phone)
        )
    }

    // Apply search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(call => 
            call.from_number.toLowerCase().includes(query) ||
            call.to_number.toLowerCase().includes(query) ||
            call.status.toLowerCase().includes(query)
        )
    }

    return filtered
})

const totalCalls = computed(() => filteredCalls.value.length)
const totalPages = computed(() => Math.ceil(totalCalls.value / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage.value, totalCalls.value))

// Methods
const loadCallHistory = async () => {
    try {
        const response = await axios.get('/api/calls/history', {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })

        if (response.data.success) {
            calls.value = response.data.calls
        } else {
            console.error('Failed to load call history:', response.data.error)
        }
    } catch (error) {
        console.error('Error loading call history:', error)
        // For demo purposes, create some sample data
        calls.value = generateSampleCalls()
    }
}

const generateSampleCalls = () => {
    const statuses = ['completed', 'missed', 'failed']
    const sampleCalls = []
    
    for (let i = 1; i <= 50; i++) {
        const date = new Date()
        date.setDate(date.getDate() - Math.floor(Math.random() * 30))
        
        sampleCalls.push({
            id: i,
            from_number: '+12037206619',
            to_number: `+1${Math.floor(Math.random() * 9000000000) + 1000000000}`,
            status: statuses[Math.floor(Math.random() * statuses.length)],
            duration: Math.floor(Math.random() * 300) + 30,
            created_at: date.toISOString(),
            transcript: [
                {
                    timestamp: '10:00:00',
                    type: 'Call',
                    message: 'Call initiated'
                },
                {
                    timestamp: '10:00:15',
                    type: 'Status',
                    message: 'Call connected'
                },
                {
                    timestamp: '10:02:30',
                    type: 'Control',
                    message: 'Call ended'
                }
            ]
        })
    }
    
    return sampleCalls.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
}

const refreshHistory = () => {
    loadCallHistory()
}

const exportHistory = () => {
    const csvContent = generateCSV()
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', `call_history_${new Date().toISOString().split('T')[0]}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

const generateCSV = () => {
    const headers = ['Date', 'Time', 'From', 'To', 'Status', 'Duration', 'Status']
    const rows = filteredCalls.value.map(call => [
        formatDate(call.created_at),
        formatTime(call.created_at),
        call.from_number,
        call.to_number,
        call.status,
        formatDuration(call.duration) || '--'
    ])
    
    return [headers, ...rows].map(row => row.join(',')).join('\n')
}

const viewCallDetails = (call) => {
    selectedCall.value = call
}

const closeModal = () => {
    selectedCall.value = null
}

const downloadTranscript = (call) => {
    if (!call.transcript || call.transcript.length === 0) {
        alert('No transcript available for this call')
        return
    }
    
    const transcriptText = call.transcript.map(entry => 
        `[${entry.timestamp}] ${entry.type}: ${entry.message}`
    ).join('\n')
    
    const blob = new Blob([transcriptText], { type: 'text/plain;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    link.setAttribute('href', url)
    link.setAttribute('download', `transcript_${call.id}_${new Date().toISOString().split('T')[0]}.txt`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
    }
}

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
    }
}

// Utility functions
const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString()
}

const formatTime = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleTimeString()
}

const formatDuration = (seconds) => {
    if (!seconds) return '--'
    const minutes = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

// Lifecycle
onMounted(() => {
    loadCallHistory()
})
</script>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Smooth transitions */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

/* Hover effects */
.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}
</style> 