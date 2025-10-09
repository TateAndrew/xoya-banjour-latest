<template>
    <Head title="Call Transcriptions" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Call Transcriptions
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">Call Transcriptions</h3>
                                <p class="text-gray-600">View and search call transcripts</p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="/recordings" 
                                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                    🎙️ View Recordings
                                </a>
                                <button @click="refreshTranscriptions" 
                                        :disabled="loading"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-400">
                                    {{ loading ? '⏳ Loading...' : '🔄 Refresh' }}
                                </button>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select v-model="filters.status" @change="applyFilters" 
                                        class="w-full p-2 border border-gray-300 rounded-lg">
                                    <option value="">All Statuses</option>
                                    <option value="completed">Completed</option>
                                    <option value="processing">Processing</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                                <input v-model="filters.date_from" 
                                       @change="applyFilters"
                                       type="date" 
                                       class="w-full p-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                                <input v-model="filters.date_to" 
                                       @change="applyFilters"
                                       type="date" 
                                       class="w-full p-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                                <input v-model="searchQuery" 
                                       type="text" 
                                       placeholder="Search transcripts..."
                                       class="w-full p-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-12">
                            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                            <p class="mt-4 text-gray-600">Loading transcriptions...</p>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="!transcriptions || transcriptions.length === 0" 
                             class="text-center py-12 bg-gray-50 rounded-lg">
                            <div class="text-6xl mb-4">📝</div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Transcriptions Found</h3>
                            <p class="text-gray-600 mb-4">
                                {{ hasFilters ? 'Try adjusting your filters' : 'Call transcriptions will appear here when available' }}
                            </p>
                            <button v-if="!hasFilters" @click="refreshTranscriptions" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Refresh from Telnyx
                            </button>
                        </div>

                        <!-- Transcriptions Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date/Time
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Call Details
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Transcript Preview
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
                                    <tr v-for="transcription in filteredTranscriptions" :key="transcription.id" 
                                        class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="font-medium text-gray-900">
                                                {{ formatDate(transcription.created_at) }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ formatTime(transcription.created_at) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div v-if="transcription.from_number || transcription.call?.from_number" class="space-y-1">
                                                <div class="font-medium text-gray-900">
                                                    {{ transcription.from_number || transcription.call?.from_number || 'Unknown' }} 
                                                    →
                                                    {{ transcription.to_number || transcription.call?.to_number || 'Unknown' }}
                                                </div>
                                                <div class="text-gray-500 text-xs">
                                                    {{ formatDirection(transcription) }}
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-500">
                                                <div class="font-medium">Recording ID</div>
                                                <div class="text-xs">{{ transcription.recording_id?.substring(0, 12) }}...</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm max-w-md">
                                            <div class="text-gray-700 truncate">
                                                {{ transcription.transcription_text || 'No text available' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDuration(transcription.duration_millis) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(transcription.status)" 
                                                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ transcription.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button @click="viewTranscription(transcription)"
                                                    class="text-blue-600 hover:text-blue-900" 
                                                    title="View full transcript">
                                                👁️ View
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Info -->
                        <div v-if="transcriptions && transcriptions.length > 0" 
                             class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ transcriptions.length }} transcriptions
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transcription Detail Modal -->
        <div v-if="viewingTranscription" 
             @click="closeTranscription"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.stop class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-semibold">Full Transcription</h3>
                    <button @click="closeTranscription" class="text-gray-500 hover:text-gray-700 text-2xl">
                        ✕
                    </button>
                </div>
                
                <div class="mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                        <div>
                            <p class="font-semibold text-gray-900">Call Information</p>
                            <p><strong>From:</strong> {{ viewingTranscription.from_number || viewingTranscription.call?.from_number || 'Unknown' }}</p>
                            <p><strong>To:</strong> {{ viewingTranscription.to_number || viewingTranscription.call?.to_number || 'Unknown' }}</p>
                            <p><strong>Direction:</strong> {{ formatDirection(viewingTranscription) }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Transcription Details</p>
                            <p><strong>Date:</strong> {{ formatDate(viewingTranscription.created_at) }}</p>
                            <p><strong>Duration:</strong> {{ formatDuration(viewingTranscription.duration_millis) }}</p>
                            <p>
                                <strong>Status:</strong> 
                                <span :class="getStatusClass(viewingTranscription.status)" 
                                      class="ml-2 px-2 py-1 text-xs rounded-full">
                                    {{ viewingTranscription.status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="font-semibold text-lg mb-4">Full Transcript:</h4>
                    <div class="prose max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed text-base">
                            {{ viewingTranscription.transcription_text || 'No transcription text available' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200 text-xs text-gray-500 space-y-1">
                    <p><strong>Transcription ID:</strong> {{ viewingTranscription.id }}</p>
                    <p><strong>Recording ID:</strong> {{ viewingTranscription.recording_id }}</p>
                    <p><strong>Last updated:</strong> {{ formatDate(viewingTranscription.updated_at) }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    }
});

const loading = ref(false);
const transcriptions = ref([]);
const searchQuery = ref('');
const filters = ref({
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
});
const viewingTranscription = ref(null);

const hasFilters = computed(() => {
    return filters.value.status || filters.value.date_from || filters.value.date_to || searchQuery.value;
});

const filteredTranscriptions = computed(() => {
    if (!transcriptions.value) return [];
    
    return transcriptions.value.filter(transcription => {
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const searchableText = [
                transcription.from_number,
                transcription.to_number,
                transcription.call?.from_number,
                transcription.call?.to_number,
                transcription.transcription_text,
                transcription.recording_id,
                transcription.status
            ].filter(Boolean).join(' ').toLowerCase();
            
            if (!searchableText.includes(query)) {
                return false;
            }
        }
        return true;
    });
});

onMounted(() => {
    refreshTranscriptions();
});

async function refreshTranscriptions() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.date_from) params.append('date_from', filters.value.date_from);
        if (filters.value.date_to) params.append('date_to', filters.value.date_to);
        
        const response = await axios.get(`/api/transcriptions?${params.toString()}`);
        if (response.data.success) {
            transcriptions.value = response.data.transcriptions.data || response.data.transcriptions;
        }
    } catch (error) {
        console.error('Error fetching transcriptions:', error);
        alert('Failed to load transcriptions');
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    refreshTranscriptions();
}

function viewTranscription(transcription) {
    viewingTranscription.value = transcription;
}

function closeTranscription() {
    viewingTranscription.value = null;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDuration(millis) {
    if (!millis) return '0:00';
    const seconds = Math.floor(millis / 1000);
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

function getStatusClass(status) {
    switch (status?.toLowerCase()) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'processing':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function formatDirection(transcription) {
    const direction = transcription.direction || transcription.call?.direction;
    if (!direction || direction === 'unknown') return 'Unknown';
    return direction.charAt(0).toUpperCase() + direction.slice(1);
}
</script>

