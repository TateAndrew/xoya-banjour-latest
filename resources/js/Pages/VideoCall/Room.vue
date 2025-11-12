<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const props = defineProps({
    videoCall: Object,
    roomName: String,
    userName: String,
    userEmail: String,
    jitsiDomain: String,
    jitsiConfig: Object,    
});

const jitsiApi = ref(null);
const jitsiContainer = ref(null);
const isLoading = ref(true);
const callEnded = ref(false);
const participants = ref([]);
const isMuted = ref(false);
const isVideoOff = ref(false);

// Initialize Jitsi Meet
const initJitsi = () => {
    if (!window.JitsiMeetExternalAPI) {
        console.error('Jitsi Meet API not loaded');
        return;
    }

    const domain = props.jitsiDomain || 'meet.jit.si';
    const options = {
        roomName: props.roomName,
        width: '100%',
        height: '100%',
        parentNode: jitsiContainer.value,
        configOverwrite: {
            startWithAudioMuted: false,
            startWithVideoMuted: false,
            enableWelcomePage: false,
            prejoinPageEnabled: false,
            disableDeepLinking: true,
            ...props.jitsiConfig,
        },
        interfaceConfigOverwrite: {
            SHOW_JITSI_WATERMARK: false,
            SHOW_WATERMARK_FOR_GUESTS: false,
            SHOW_BRAND_WATERMARK: false,
            DISPLAY_WELCOME_PAGE_CONTENT: false,
            MOBILE_APP_PROMO: false,
            HIDE_INVITE_MORE_HEADER: false,
            APP_NAME: 'Video Conference',
            NATIVE_APP_NAME: 'Video Conference',
            PROVIDER_NAME: 'Video Conference',
        },
        userInfo: {
            displayName: props.userName,
            email: props.userEmail,
        },
    };

    jitsiApi.value = new window.JitsiMeetExternalAPI(domain, options);

    // Event listeners
    jitsiApi.value.addEventListener('videoConferenceJoined', handleConferenceJoined);
    jitsiApi.value.addEventListener('videoConferenceLeft', handleConferenceLeft);
    jitsiApi.value.addEventListener('participantJoined', handleParticipantJoined);
    jitsiApi.value.addEventListener('participantLeft', handleParticipantLeft);
    jitsiApi.value.addEventListener('audioMuteStatusChanged', handleAudioMuteChange);
    jitsiApi.value.addEventListener('videoMuteStatusChanged', handleVideoMuteChange);
    jitsiApi.value.addEventListener('readyToClose', handleReadyToClose);

    isLoading.value = false;
};

// Load Jitsi External API script
const loadJitsiScript = () => {
    return new Promise((resolve, reject) => {
        if (window.JitsiMeetExternalAPI) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = `https://${props.jitsiDomain || 'meet.jit.si'}/external_api.js`;
        script.async = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
};

// Event Handlers
const handleConferenceJoined = (event) => {
    console.log('Conference joined:', event);
    
    // Update call status to active
    axios.post(`/api/video-calls/${props.videoCall.id}/status`, {
        action: 'join',
        participant_data: {
            id: props.userEmail,
            name: props.userName,
            email: props.userEmail,
        },
    }).catch(error => {
        console.error('Error updating call status:', error);
    });
};

const handleConferenceLeft = () => {
    console.log('Conference left');
    callEnded.value = true;
    
    // Update call status
    axios.post(`/api/video-calls/${props.videoCall.id}/status`, {
        action: 'leave',
        participant_data: {
            id: props.userEmail,
        },
    }).catch(error => {
        console.error('Error updating call status:', error);
    });
};

const handleParticipantJoined = (event) => {
    console.log('Participant joined:', event);
    participants.value.push(event);
};

const handleParticipantLeft = (event) => {
    console.log('Participant left:', event);
    participants.value = participants.value.filter(p => p.id !== event.id);
};

const handleAudioMuteChange = (event) => {
    isMuted.value = event.muted;
};

const handleVideoMuteChange = (event) => {
    isVideoOff.value = event.muted;
};

const handleReadyToClose = () => {
    console.log('Ready to close');
    hangup();
};

// Controls
const toggleAudio = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleAudio');
    }
};

const toggleVideo = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleVideo');
    }
};

const toggleScreenShare = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleShareScreen');
    }
};

const hangup = async () => {
    if (jitsiApi.value) {
        jitsiApi.value.dispose();
        jitsiApi.value = null;
    }
    
    callEnded.value = true;
    
    // Redirect back to video calls page after a delay
    setTimeout(() => {
        router.visit('/video-calls');
    }, 2000);
};

const endCall = async () => {
    try {
        await axios.post(`/api/video-calls/${props.videoCall.id}/end`);
    } catch (error) {
        console.error('Error ending call:', error);
    }
    
    hangup();
};

// Lifecycle
onMounted(async () => {
    try {
        await loadJitsiScript();
        initJitsi();
    } catch (error) {
        console.error('Error loading Jitsi:', error);
        isLoading.value = false;
    }
});

onBeforeUnmount(() => {
    if (jitsiApi.value) {
        jitsiApi.value.dispose();
    }
});

const participantCount = computed(() => {
    return participants.value.length + 1; // +1 for current user
});
</script>

<template>
    <Head :title="`Video Call - ${roomName}`" />

    <div class="min-h-screen bg-gray-900">
        <!-- Header -->
        <div class="fixed top-0 left-0 right-0 z-50 bg-gray-800 bg-opacity-90 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h1 class="text-lg font-semibold text-white">{{ roomName }}</h1>
                        <span class="rounded-full bg-green-500 px-3 py-1 text-xs font-medium text-white">
                            {{ participantCount }} {{ participantCount === 1 ? 'participant' : 'participants' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <SecondaryButton
                            v-if="videoCall.host_user_id === $page.props.auth.user?.id"
                            @click="endCall"
                            class="bg-red-600 text-black hover:bg-red-700 hover:text-white"
                        >
                            End Call for All
                        </SecondaryButton>
                        <SecondaryButton @click="hangup" class="bg-gray-700 text-black hover:bg-gray-600 hover:text-white">
                            Leave Call
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Container -->
        <div class="relative h-screen w-full pt-16">
            <!-- Loading State -->
            <div
                v-if="isLoading"
                class="flex h-full items-center justify-center"
            >
                <div class="text-center">
                    <svg
                        class="mx-auto h-12 w-12 animate-spin text-white"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <p class="mt-4 text-white">Connecting to video call...</p>
                </div>
            </div>

            <!-- Call Ended State -->
            <div
                v-if="callEnded"
                class="flex h-full items-center justify-center"
            >
                <div class="text-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="mx-auto h-16 w-16 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                        />
                    </svg>
                    <h2 class="mt-4 text-xl font-semibold text-white">Call Ended</h2>
                    <p class="mt-2 text-gray-400">You have left the video call</p>
                    <PrimaryButton class="mt-6" @click="router.visit('/video-calls')">
                        Return to Video Calls
                    </PrimaryButton>
                </div>
            </div>

            <!-- Jitsi Container -->
            <div
                ref="jitsiContainer"
                v-show="!isLoading && !callEnded"
                class="h-full w-full"
            ></div>
        </div>

        <!-- Call Info Overlay (Optional) -->
        <div
            v-if="!isLoading && !callEnded"
            class="fixed bottom-6 left-6 rounded-lg bg-gray-800 bg-opacity-80 px-4 py-3 text-white backdrop-blur-sm"
        >
            <div class="space-y-1 text-sm">
                <div>
                    <span class="font-medium">Room:</span>
                    <span class="ml-2 text-gray-300">{{ roomName }}</span>
                </div>
                <div v-if="videoCall.type">
                    <span class="font-medium">Type:</span>
                    <span class="ml-2 capitalize text-gray-300">{{ videoCall.type.replace('_', ' ') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Ensure Jitsi iframe takes full space */
:deep(iframe) {
    border: none;
    height: 100%;
    width: 100%;
}
</style>

