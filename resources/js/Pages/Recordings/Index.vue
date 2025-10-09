<template>
    <Head title="Call Recordings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Call Recordings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">Call Recordings</h3>
                                <p class="text-gray-600">Manage and listen to your call recordings</p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="/transcriptions" 
                                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    📝 View All Transcriptions
                                </a>
                                <button @click="refreshRecordings" 
                                        :disabled="loading"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-400">
                                    {{ loading ? '⏳ Loading...' : '🔄 Refresh from Telnyx' }}
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
                                    <option value="deleted">Deleted</option>
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
                                       placeholder="Search recordings..."
                                       class="w-full p-2 border border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="text-center py-12">
                            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                            <p class="mt-4 text-gray-600">Loading recordings...</p>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="!recordings || recordings.length === 0" 
                             class="text-center py-12 bg-gray-50 rounded-lg">
                            <div class="text-6xl mb-4">🎙️</div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">No Recordings Found</h3>
                            <p class="text-gray-600 mb-4">
                                {{ hasFilters ? 'Try adjusting your filters' : 'Make some calls with recording enabled to see recordings here' }}
                            </p>
                            <button v-if="!hasFilters" @click="refreshRecordings" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Refresh from Telnyx
                            </button>
                        </div>

                        <!-- Recordings Table -->
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
                                    <tr v-for="recording in filteredRecordings" :key="recording.id" 
                                        class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="font-medium text-gray-900">
                                                {{ formatDate(recording.created_at) }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ formatTime(recording.created_at) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div v-if="recording.from_number || recording.call?.from_number" class="space-y-1">
                                                <div class="font-medium text-gray-900">
                                                    {{ recording.from_number || recording.call?.from_number || 'Unknown' }} 
                                                    →
                                                    {{ recording.to_number || recording.call?.to_number || 'Unknown' }}
                                                </div>
                                                <div class="text-gray-500 text-xs">
                                                    {{ formatDirection(recording) }} • {{ recording.channels || 'dual' }}
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-500">
                                                <div class="font-medium">No call data</div>
                                                <div class="text-xs">Session: {{ recording.call_session_id?.substring(0, 12) }}...</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDuration(recording.duration_millis) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(recording.status)" 
                                                  class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ recording.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <button v-if="getDownloadUrl(recording, 'mp3')" 
                                                    @click="playRecording(recording)"
                                                    class="text-blue-600 hover:text-blue-900" 
                                                    title="Play recording">
                                                ▶️
                                            </button>
                                            <button @click="viewTranscription(recording)"
                                                    class="text-purple-600 hover:text-purple-900" 
                                                    title="View transcription">
                                                📝
                                            </button>
                                            <button v-if="getDownloadUrl(recording, 'mp3')" 
                                                    @click="downloadRecording(recording, 'mp3')"
                                                    class="text-green-600 hover:text-green-900" 
                                                    title="Download MP3">
                                                ⬇️ MP3
                                            </button>
                                            <button v-if="getDownloadUrl(recording, 'wav')" 
                                                    @click="downloadRecording(recording, 'wav')"
                                                    class="text-green-600 hover:text-green-900" 
                                                    title="Download WAV">
                                                ⬇️ WAV
                                            </button>
                                            <button @click="deleteRecording(recording)"
                                                    class="text-red-600 hover:text-red-900" 
                                                    title="Delete recording">
                                                🗑️
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="recordings && recordings.length > 0" 
                             class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ recordings.length }} recordings
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio Player Modal -->
        <div v-if="playingRecording" 
             @click="closePlayer"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.stop class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Playing Recording</h3>
                    <button @click="closePlayer" class="text-gray-500 hover:text-gray-700">
                        ✕
                    </button>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>From:</strong> {{ playingRecording.from_number || playingRecording.call?.from_number || 'Unknown' }}</p>
                        <p><strong>To:</strong> {{ playingRecording.to_number || playingRecording.call?.to_number || 'Unknown' }}</p>
                        <p><strong>Direction:</strong> {{ formatDirection(playingRecording) }}</p>
                        <p><strong>Date:</strong> {{ formatDate(playingRecording.created_at) }}</p>
                        <p><strong>Duration:</strong> {{ formatDuration(playingRecording.duration_millis) }}</p>
                    </div>
                </div>
                <audio ref="audioPlayer" 
                       controls 
                       class="w-full"
                       :src="getDownloadUrl(playingRecording, 'mp3')"
                       autoplay>
                    Your browser does not support the audio element.
                </audio>
            </div>
        </div>

        <!-- Transcription Modal -->
        <div v-if="viewingTranscription" 
             @click="closeTranscription"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div @click.stop class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Recording Transcription</h3>
                    <button @click="closeTranscription" class="text-gray-500 hover:text-gray-700">
                        ✕
                    </button>
                </div>
                
                <!-- Loading State -->
                <div v-if="transcriptionLoading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600">Loading transcription...</p>
                </div>

                <!-- Error State -->
                <div v-else-if="transcriptionError" class="text-center py-8">
                    <div class="text-4xl mb-2">⚠️</div>
                    <p class="text-gray-600">{{ transcriptionError }}</p>
                </div>

                <!-- Transcription Content -->
                <div v-else-if="currentTranscription">
                    <div class="mb-4">
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            <p><strong>From:</strong> {{ viewingTranscription.from_number || viewingTranscription.call?.from_number || 'Unknown' }}</p>
                            <p><strong>To:</strong> {{ viewingTranscription.to_number || viewingTranscription.call?.to_number || 'Unknown' }}</p>
                            <p><strong>Date:</strong> {{ formatDate(viewingTranscription.created_at) }}</p>
                            <p><strong>Duration:</strong> {{ formatDuration(currentTranscription.duration_millis) }}</p>
                            <p>
                                <strong>Status:</strong> 
                                <span :class="getStatusClass(currentTranscription.status)" 
                                      class="ml-2 px-2 py-1 text-xs rounded-full">
                                    {{ currentTranscription.status }}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">Transcript:</h4>
                        <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">
                            {{ currentTranscription.transcription_text || 'No transcription text available' }}
                        </p>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        <p>Transcription ID: {{ currentTranscription.id }}</p>
                        <p>Last updated: {{ formatDate(currentTranscription.updated_at) }}</p>
                    </div>
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
    recordings: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const loading = ref(false);
const recordings = ref(props.recordings?.data || []);
const searchQuery = ref('');
const filters = ref({
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
});
const playingRecording = ref(null);
const viewingTranscription = ref(null);
const currentTranscription = ref(null);
const transcriptionLoading = ref(false);
const transcriptionError = ref(null);

const hasFilters = computed(() => {
    return filters.value.status || filters.value.date_from || filters.value.date_to || searchQuery.value;
});

const filteredRecordings = computed(() => {
    if (!recordings.value) return [];
    
    return recordings.value.filter(recording => {
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const searchableText = [
                recording.call?.from_number,
                recording.call?.to_number,
                recording.call_session_id,
                recording.status
            ].filter(Boolean).join(' ').toLowerCase();
            
            if (!searchableText.includes(query)) {
                return false;
            }
        }
        return true;
    });
});

onMounted(() => {
    refreshRecordings();
});

async function refreshRecordings() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.date_from) params.append('date_from', filters.value.date_from);
        if (filters.value.date_to) params.append('date_to', filters.value.date_to);
        
        const response = await axios.get(`/api/recordings?${params.toString()}`);
        if (response.data.success) {
            recordings.value = response.data.recordings.data || response.data.recordings;
        }
    } catch (error) {
        console.error('Error fetching recordings:', error);
        alert('Failed to load recordings');
    } finally {
        loading.value = false;
    }
}

async function syncRecordings() {
    // Just refresh - we're always fetching from Telnyx API now
    await refreshRecordings();
}

function applyFilters() {
    refreshRecordings();
}

function playRecording(recording) {
    playingRecording.value = recording;
}

function closePlayer() {
    playingRecording.value = null;
}

async function viewTranscription(recording) {
    viewingTranscription.value = recording;
    currentTranscription.value = null;
    transcriptionLoading.value = true;
    transcriptionError.value = null;

    try {
        const response = await axios.get(`/api/recordings/${recording.id}/transcription`);
        if (response.data.success) {
            currentTranscription.value = response.data.transcription;
        } else {
            transcriptionError.value = response.data.error || 'Transcription not available';
        }
    } catch (error) {
        console.error('Error fetching transcription:', error);
        if (error.response?.status === 404) {
            transcriptionError.value = 'Transcription not found. It may not have been generated yet.';
        } else {
            transcriptionError.value = 'Failed to load transcription';
        }
    } finally {
        transcriptionLoading.value = false;
    }
}

function closeTranscription() {
    viewingTranscription.value = null;
    currentTranscription.value = null;
    transcriptionError.value = null;
}

async function downloadRecording(recording, format) {
    try {
        // Get download URL from Telnyx data structure
        const url = getDownloadUrl(recording, format);
        if (!url) {
            alert('Download URL not available');
            return;
        }
        
        // Open download URL in new tab
        window.open(url, '_blank');
    } catch (error) {
        console.error('Error downloading recording:', error);
        alert('Failed to download recording');
    }
}

async function deleteRecording(recording) {
    if (!confirm('Are you sure you want to delete this recording? This action cannot be undone.')) {
        return;
    }
    
    try {
        // Use Telnyx recording ID for deletion
        const telnyxId = recording.id;
        const response = await axios.delete(`/api/recordings/${telnyxId}`);
        if (response.data.success) {
            // Remove from local array
            recordings.value = recordings.value.filter(r => r.id !== telnyxId);
            alert('Recording deleted successfully');
        } else {
            alert('Failed to delete recording');
        }
    } catch (error) {
        console.error('Error deleting recording:', error);
        alert('Failed to delete recording');
    }
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
        case 'deleted':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getDownloadUrl(recording, format) {
    // Handle both old database format and new Telnyx API format
    if (recording.download_urls) {
        return format === 'wav' ? recording.download_urls.wav : recording.download_urls.mp3;
    }
    return format === 'wav' ? recording.download_url_wav : recording.download_url_mp3;
}

function formatDirection(recording) {
    const direction = recording.direction || recording.call?.direction;
    if (!direction || direction === 'unknown') return 'Unknown';
    return direction.charAt(0).toUpperCase() + direction.slice(1);
}
</script>

