<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { format } from 'date-fns';
import axios from 'axios';

const props = defineProps({
    recentCalls: Array,
    users: Array,
    contacts: Array,
    jitsiDomain: String,
});

const showCreateModal = ref(false);
const showQuickCallModal = ref(false);
const showConferenceModal = ref(false);
const processing = ref(false);

const createForm = ref({
    type: 'one_to_one',
    participant_user_id: null,
    contact_id: null,
});

const quickCallForm = ref({
    participant_user_id: null,
    contact_id: null,
});

const conferenceForm = ref({
    name: '',
    participants: [],
});

const errors = ref({});

// Start a quick call
const startQuickCall = async () => {
    if (!quickCallForm.value.participant_user_id && !quickCallForm.value.contact_id) {
        errors.value = { participant: 'Please select a participant or contact' };
        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/video-calls/quick-call', quickCallForm.value);
        if (response.data.success) {
            window.location.href = `/video-calls/join/${response.data.roomName}`;
        }
    } catch (error) {
        console.error('Error starting quick call:', error);
        errors.value = error.response?.data?.errors || { general: 'Failed to start call' };
    } finally {
        processing.value = false;
    }
};

// Create a scheduled call
const createRoom = async () => {
    if (!createForm.value.participant_user_id && !createForm.value.contact_id) {
        errors.value = { participant: 'Please select a participant or contact' };
        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/video-calls/create-room', createForm.value);
        if (response.data.success) {
            showCreateModal.value = false;
            router.reload();
        }
    } catch (error) {
        console.error('Error creating room:', error);
        errors.value = error.response?.data?.errors || { general: 'Failed to create room' };
    } finally {
        processing.value = false;
    }
};

// Create a conference
const createConference = async () => {
    if (!conferenceForm.value.name) {
        errors.value = { name: 'Conference name is required' };
        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/video-calls/create-conference', conferenceForm.value);
        if (response.data.success) {
            showConferenceModal.value = false;
            router.reload();
        }
    } catch (error) {
        console.error('Error creating conference:', error);
        errors.value = error.response?.data?.errors || { general: 'Failed to create conference' };
    } finally {
        processing.value = false;
    }
};

// Join a call
const joinCall = (roomName) => {
    window.location.href = `/video-calls/join/${roomName}`;
};

// Format date
const formatDate = (date) => {
    return format(new Date(date), 'MMM d, yyyy h:mm a');
};

// Get call status badge color
const getStatusColor = (status) => {
    const colors = {
        scheduled: 'bg-blue-100 text-blue-800',
        active: 'bg-green-100 text-green-800',
        ended: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

// Format duration
const formatDuration = (seconds) => {
    if (!seconds) return 'N/A';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    } else {
        return `${secs}s`;
    }
};
</script>

<template>
    <Head title="Video Calls" />

    <DashboardLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Video Calls
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Action Buttons -->
                <div class="mb-6 flex gap-4">
                    <PrimaryButton @click="showQuickCallModal = true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                        </svg>
                        Quick Call
                    </PrimaryButton>
                    
                    <SecondaryButton @click="showCreateModal = true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                        Schedule Call
                    </SecondaryButton>
                    
                    <SecondaryButton @click="showConferenceModal = true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                        Create Conference
                    </SecondaryButton>
                </div>

                <!-- Recent Calls List -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Recent Video Calls</h3>
                        
                        <div v-if="recentCalls.length === 0" class="text-center py-8 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-2">No video calls yet</p>
                            <p class="text-sm">Start your first video call by clicking the button above</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Room
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Type
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Participants
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Duration
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Date
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="call in recentCalls" :key="call.id">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {{ call.room_name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            <span class="capitalize">{{ call.type.replace('_', ' ') }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div class="flex flex-col gap-1">
                                                <span>Host: {{ call.host?.name }}</span>
                                                <span v-if="call.participant">{{ call.participant.name }}</span>
                                                <span v-if="call.contact">{{ call.contact.name || call.contact.phone_e164 }}</span>
                                                <span v-if="call.participants && call.participants.length > 0" class="text-xs text-gray-400">
                                                    +{{ call.participants.length }} participants
                                                </span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <span :class="getStatusColor(call.status)" class="inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                                {{ call.status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ formatDuration(call.duration) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ formatDate(call.created_at) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                            <button
                                                v-if="call.status === 'active' || call.status === 'scheduled'"
                                                @click="joinCall(call.room_name)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Join
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Call Modal -->
        <Modal :show="showQuickCallModal" @close="showQuickCallModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Start Quick Call</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Instantly start a video call with a user or contact
                </p>

                <div class="mt-6">
                    <InputLabel for="quick_participant" value="Select Participant" />
                    
                    <div class="mt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Users</label>
                        <select
                            v-model="quickCallForm.participant_user_id"
                            @change="quickCallForm.contact_id = null"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="null">Select a user...</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Or Contact</label>
                        <select
                            v-model="quickCallForm.contact_id"
                            @change="quickCallForm.participant_user_id = null"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="null">Select a contact...</option>
                            <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                                {{ contact.name || contact.phone_e164 }}
                            </option>
                        </select>
                    </div>

                    <InputError class="mt-2" :message="errors.participant" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showQuickCallModal = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="startQuickCall" :disabled="processing">
                        {{ processing ? 'Starting...' : 'Start Call' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Create Room Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Schedule Video Call</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Create a scheduled video call room
                </p>

                <div class="mt-6 space-y-4">
                    <div>
                        <InputLabel for="type" value="Call Type" />
                        <select
                            v-model="createForm.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="one_to_one">One to One</option>
                            <option value="group">Group</option>
                            <option value="conference">Conference</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Select Participant" />
                        
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Users</label>
                            <select
                                v-model="createForm.participant_user_id"
                                @change="createForm.contact_id = null"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="null">Select a user...</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Or Contact</label>
                            <select
                                v-model="createForm.contact_id"
                                @change="createForm.participant_user_id = null"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="null">Select a contact...</option>
                                <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                                    {{ contact.name || contact.phone_e164 }}
                                </option>
                            </select>
                        </div>

                        <InputError class="mt-2" :message="errors.participant" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showCreateModal = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="createRoom" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create Room' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Conference Modal -->
        <Modal :show="showConferenceModal" @close="showConferenceModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Create Conference</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Create a conference room for multiple participants
                </p>

                <div class="mt-6 space-y-4">
                    <div>
                        <InputLabel for="conference_name" value="Conference Name" />
                        <TextInput
                            id="conference_name"
                            v-model="conferenceForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., Team Meeting"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div>
                        <InputLabel value="Invite Participants (Optional)" />
                        <select
                            v-model="conferenceForm.participants"
                            multiple
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            size="5"
                        >
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple users</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showConferenceModal = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="createConference" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create Conference' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </DashboardLayout>
</template>

