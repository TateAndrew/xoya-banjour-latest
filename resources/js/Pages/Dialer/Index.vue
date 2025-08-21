<template>
    <Head title="Professional Audio Dialer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Professional Audio Dialer
                </h2>
                <a href="/dialer/history" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    📝 Call History
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Main Dialer Interface -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Panel - Call Controls -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-6">
                                <!-- Call Status -->
                                <div class="text-center">
                                    <div class="w-24 h-24 mx-auto mb-4 rounded-full flex items-center justify-center text-4xl"
                                         :class="callStatusClass">
                                        {{ callStatusIcon }}
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ callStatusText }}</h3>
                                    <p v-if="callDuration" class="text-2xl font-mono text-blue-600">{{ callDuration }}</p>
                                </div>

                                                                    <!-- Call Controls -->
                                    <div v-if="isCallActive || isRinging" class="space-y-4">
                                        <!-- Answer Call Button (for incoming calls) -->
                                        <button v-if="isRinging && !isCallActive" 
                                                @click="answerCall" 
                                                class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-xl">📞</span>
                                                <span>Answer Call</span>
                                            </div>
                                        </button>
                                        
                                        <!-- Reject Call Button (for incoming calls) -->
                                        <button v-if="isRinging && !isCallActive" 
                                                @click="rejectCall" 
                                                class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-xl">❌</span>
                                                <span>Reject Call</span>
                                            </div>
                                        </button>
                                    <!-- Mute Button -->
                                    <button @click="toggleMute" 
                                            :class="`w-full py-4 rounded-lg text-white font-semibold transition-all duration-200 ${
                                                isMuted ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700'
                                            }`">
                                        <div class="flex items-center justify-center space-x-2">
                                            <span class="text-xl">{{ isMuted ? '🔇' : '🎤' }}</span>
                                            <span>{{ isMuted ? 'Unmute' : 'Mute' }}</span>
                                        </div>
                                    </button>

                                    <!-- Hold Button -->
                                    <button @click="toggleHold" 
                                            :class="`w-full py-4 rounded-lg text-white font-semibold transition-all duration-200 ${
                                                isOnHold ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-blue-600 hover:bg-blue-700'
                                            }`">
                                        <div class="flex items-center justify-center space-x-2">
                                            <span class="text-xl">{{ isOnHold ? '⏸️' : '⏸️' }}</span>
                                            <span>{{ isOnHold ? 'Resume' : 'Hold' }}</span>
                                        </div>
                                    </button>

                                    <!-- Hangup Button -->
                                    <button @click="endCall" 
                                            class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all duration-200">
                                        <div class="flex items-center justify-center space-x-2">
                                            <span class="text-xl">📴</span>
                                            <span>End Call</span>
                                        </div>
                                    </button>

                                    <!-- Disconnect Button -->
                                    <button @click="disconnectCall" 
                                            class="w-full py-4 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-all duration-200">
                                        <div class="flex items-center justify-center space-x-2">
                                            <span class="text-xl">🔌</span>
                                            <span>Disconnect</span>
                                        </div>
                                    </button>
                                </div>

                                <!-- Connection Status -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">Connection Status</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">WebRTC:</span>
                                            <span :class="webrtcStatus === 'ready' ? 'text-green-600' : 'text-red-600'">
                                                {{ webrtcStatus }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Backend:</span>
                                            <span :class="backendStatus === 'connected' ? 'text-green-600' : 'text-red-600'">
                                                {{ backendStatus }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="space-y-3">
                                    <button @click="loadConnections" 
                                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        🔄 Refresh Connections
                                    </button>
                                    <button @click="initializeWebRTC" 
                                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                        📞 Initialize WebRTC
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Center Panel - Dialer -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-6">
                                <!-- Dialer Header -->
                                <div class="text-center">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Audio Dialer</h3>
                                    <p class="text-gray-600">Professional calling with WebRTC</p>
                                </div>

                                <!-- Connection Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SIP Connection</label>
                                    <select v-model="selectedConnection" 
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select SIP Connection</option>
                                        <option v-for="connection in connections" :key="connection.id" :value="connection.id">
                                            {{ connection.name }} ({{ connection.status }})
                                        </option>
                                    </select>
                                </div>



                                <!-- User Information -->
                                <div v-if="props.user" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white font-semibold text-lg">
                                                    {{ props.user.name ? props.user.name.charAt(0).toUpperCase() : 'U' }}
                                                </span>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-blue-900">
                                                    {{ props.user.name || 'User' }}
                                                </h3>
                                                <p class="text-sm text-blue-700">
                                                    {{ props.user.email || 'user@example.com' }}
                                                </p>
                                                <p class="text-xs text-blue-600 mt-1">
                                                    Available Phone Numbers: {{ props.phoneNumbers ? props.phoneNumbers.length : 0 }}
                                                </p>
                                                <p class="text-xs text-blue-500 mt-1">
                                                    Last updated: {{ lastPhoneNumbersRefresh.toLocaleTimeString() }}
                                                </p>
                                                <p class="text-xs text-blue-500 mt-1">
                                                    Status: <span class="font-medium text-green-600">Active</span>
                                                </p>
                                            </div>
                                        </div>
                                        <button @click="refreshPhoneNumbers" 
                                                class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition-all duration-200"
                                                title="Refresh phone numbers">
                                            🔄
                                        </button>
                                    </div>
                                </div>

                                <!-- Phone Numbers -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">From Number (User Assignment)</label>
                                        <select v-model="fromNumber" 
                                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="" disabled>Select your phone number</option>
                                            <option v-for="phone in props.phoneNumbers" 
                                                    :key="phone.id" 
                                                    :value="phone.phone_number"
                                                    class="py-2">
                                                {{ phone.phone_number }} 
                                                <span v-if="phone.label" class="text-gray-500">({{ phone.label }})</span>
                                            </option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Select the phone number you want to use for outgoing calls
                                        </p>
                                        <div v-if="fromNumber" class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700">
                                            ✅ Active: {{ fromNumber }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">To Number</label>
                                        <div class="relative">
                                            <input v-model="toNumber" 
                                                   type="tel" 
                                                   placeholder="+18004377950" 
                                                   @keydown="handleKeydown"
                                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <button @click="clearToNumber" 
                                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dialpad -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 text-center">Dialpad</h4>
                                    <div class="grid grid-cols-3 gap-2">
                                        <!-- Row 1: 1, 2, 3 -->
                                        <button @click="addDigit('1')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 active:bg-blue-100 active:scale-95 transition-all duration-150 text-xl font-semibold text-gray-700">
                                            1
                                        </button>
                                        <button @click="addDigit('2')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 active:bg-blue-100 active:scale-95 transition-all duration-150 text-xl font-semibold text-gray-700">
                                            2
                                        </button>
                                        <button @click="addDigit('3')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 active:bg-blue-100 active:scale-95 transition-all duration-150 text-xl font-semibold text-gray-700">
                                            3
                                        </button>
                                        
                                        <!-- Row 2: 4, 5, 6 -->
                                        <button @click="addDigit('4')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xl font-semibold text-gray-700">
                                            4
                                        </button>
                                        <button @click="addDigit('5')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xl font-semibold text-gray-700">
                                            5
                                        </button>
                                        <button @click="addDigit('6')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xl font-semibold text-gray-700">
                                            6
                                        </button>
                                        
                                        <!-- Row 3: 7, 8, 9 -->
                                        <button @click="addDigit('7')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xl font-semibold text-gray-700">
                                            7
                                        </button>
                                        <button @click="addDigit('8')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xl font-semibold text-gray-700">
                                            8
                                        </button>
                                        <button @click="addDigit('9')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xl font-semibold text-gray-700">
                                            9
                                        </button>
                                        
                                        <!-- Row 4: *, 0, # -->
                                        <button @click="addDigit('*')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 active:bg-blue-100 active:scale-95 transition-all duration-150 text-xl font-semibold text-gray-700">
                                            *
                                        </button>
                                        <button @click="addDigit('0')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 active:bg-blue-100 active:scale-95 transition-all duration-150 text-xl font-semibold text-gray-700">
                                            0
                                        </button>
                                        <button @click="addDigit('#')" 
                                                class="p-4 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 active:bg-blue-100 active:scale-95 transition-all duration-150 text-xl font-semibold text-gray-700">
                                            #
                                        </button>
                                    </div>
                                    
                                    <!-- Special Buttons -->
                                    <div class="grid grid-cols-2 gap-2 mt-3">
                                        <button @click="addPlus" 
                                                class="p-3 bg-blue-100 border border-blue-200 rounded-lg hover:bg-blue-200 active:bg-blue-300 active:scale-95 transition-all duration-150 text-sm font-medium text-blue-700">
                                            + (Plus)
                                        </button>
                                        <button @click="backspace" 
                                                class="p-3 bg-red-100 border border-red-200 rounded-lg hover:bg-red-200 active:bg-red-300 active:scale-95 transition-all duration-150 text-sm font-medium text-red-700">
                                            ← Backspace
                                        </button>
                                    </div>
                                </div>

                                <!-- Call Button -->
                                <button @click="startCall" 
                                        :disabled="!canMakeCall || isConnecting"
                                        class="w-full py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-lg font-semibold transition-all duration-200">
                                    <div class="flex items-center justify-center space-x-2">
                                        <span v-if="isConnecting" class="animate-spin">🔄</span>
                                        <span v-else>📞</span>
                                        <span>{{ isConnecting ? 'Connecting...' : 'Start Call' }}</span>
                                    </div>
                                </button>

                                <!-- Call Status Display -->
                                <div v-if="callStatus" 
                                     :class="`p-4 rounded-lg border-2 text-center ${
                                         callStatus === 'new' ? 'bg-blue-50 border-blue-300 text-blue-800' :
                                         callStatus === 'trying' ? 'bg-yellow-50 border-yellow-300 text-yellow-800' :
                                         callStatus === 'requesting' ? 'bg-yellow-50 border-yellow-300 text-yellow-800' :
                                         callStatus === 'recovering' ? 'bg-orange-50 border-orange-300 text-orange-800' :
                                         callStatus === 'ringing' ? 'bg-yellow-50 border-yellow-300 text-yellow-800' :
                                         callStatus === 'answering' ? 'bg-yellow-50 border-yellow-300 text-yellow-800' :
                                         callStatus === 'early' ? 'bg-yellow-50 border-yellow-300 text-yellow-800' :
                                         callStatus === 'active' ? 'bg-green-50 border-green-300 text-green-800' :
                                         callStatus === 'held' ? 'bg-yellow-50 border-yellow-300 text-yellow-800' :
                                         callStatus === 'hangup' ? 'bg-red-50 border-red-300 text-red-800' :
                                         callStatus === 'destroy' ? 'bg-red-50 border-red-300 text-red-800' :
                                         callStatus === 'purge' ? 'bg-red-50 border-red-300 text-red-800' :
                                         callStatus === 'failed' ? 'bg-red-50 border-red-300 text-red-800' :
                                         callStatus === 'busy' ? 'bg-orange-50 border-orange-300 text-orange-800' :
                                         'bg-blue-50 border-blue-300 text-blue-800'
                                     }`">
                                    <div class="flex items-center justify-center space-x-2">
                                        <span class="text-xl">{{ callStatusIcon }}</span>
                                        <span class="font-semibold">{{ callStatusText }}</span>
                                    </div>
                                    
                                    <!-- Call Quality Indicator (for active calls) -->
                                    <div v-if="callStatus === 'active' && callDuration !== '00:00'" 
                                         class="mt-3 pt-3 border-t border-gray-200">
                                        <div class="flex items-center justify-center space-x-2 text-sm">
                                            <span class="text-green-600">📶</span>
                                            <span class="text-gray-600">Call Quality: Good</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Transcript & Logs -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-6">
                                <!-- Transcript Header -->
                                <div class="border-b border-gray-200 pb-4">
                                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                                        📝 Call Transcript
                                        <button @click="clearTranscript" 
                                                class="ml-auto text-sm text-gray-500 hover:text-gray-700">
                                            Clear
                                        </button>
                                    </h3>
                                </div>

                                <!-- Transcript Content -->
                                <div class="bg-gray-50 rounded-lg p-4 h-64 overflow-y-auto">
                                    <div v-if="transcript.length === 0" class="text-center text-gray-500 py-8">
                                        <span class="text-2xl mb-2 block">📞</span>
                                        <p>Start a call to see transcript</p>
                                    </div>
                                    <div v-else class="space-y-3">
                                        <div v-for="(entry, index) in transcript" :key="index" 
                                             class="text-sm">
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
                                </div>

                                <!-- Debug Logs -->
                                <div class="border-t border-gray-200 pt-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-gray-900">Debug Logs</h4>
                                        <div class="flex space-x-2">
                                            <button @click="clearDebugLogs" 
                                                    class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-200">
                                                Clear
                                            </button>
                                            <span class="text-xs text-gray-400">
                                                {{ debugLogs.length }} logs
                                            </span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg border border-gray-200 max-h-48 overflow-y-auto debug-logs">
                                        <div v-if="debugLogs.length === 0" class="text-center text-gray-500 py-8">
                                            <span class="text-lg block mb-2">📋</span>
                                            <p class="text-sm">No debug logs yet</p>
                                            <p class="text-xs text-gray-400">Start using the dialer to see logs</p>
                                        </div>
                                        <div v-else class="divide-y divide-gray-200">
                                            <div v-for="log in debugLogs.slice(-8)" :key="log.id" 
                                                 class="p-3 hover:bg-gray-100 transition-colors debug-log-entry">
                                                <div class="flex items-start space-x-3">
                                                    <!-- Status Icon -->
                                                    <div class="flex-shrink-0 mt-0.5">
                                                        <div v-if="log.type === 'success'" 
                                                             class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                        <div v-else-if="log.type === 'error'" 
                                                             class="w-2 h-2 bg-red-500 rounded-full"></div>
                                                        <div v-else-if="log.type === 'warning'" 
                                                             class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                                        <div v-else 
                                                             class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                    </div>
                                                    
                                                    <!-- Log Content -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-medium text-gray-900 capitalize">
                                                                {{ log.type }}
                                                            </span>
                                                            <span class="text-xs text-gray-500 font-mono">
                                                                {{ log.timestamp }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-700 mt-1 break-words">
                                                            {{ log.message }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden Audio Elements -->
        <audio id="remoteMedia" autoplay="true" />
        <audio id="localMedia" autoplay="true" muted="true" />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const props = defineProps({
    phoneNumbers: Array,
    user: Object,
})

const emit = defineEmits(['refreshPhoneNumbers'])

// Reactive data
const connections = ref([])
const selectedConnection = ref('')
const fromNumber = ref('+12037206619')
const toNumber = ref('+18004377950')
const lastPhoneNumbersRefresh = ref(new Date())
const isCallActive = ref(false)
const isConnecting = ref(false)
const callStatus = ref('')
const backendStatus = ref('disconnected')
const webrtcStatus = ref('disconnected')
const lastError = ref('')
const debugLogs = ref([])
const callDuration = ref('00:00')
const isRinging = ref(false)
const isConnected = ref(false)
const isMuted = ref(false)
const isOnHold = ref(false)
const transcript = ref([])
let callTimer = null
let webrtcClient = null
let currentCall = null
let localStream = null

// Computed properties
const canMakeCall = computed(() => {
    return selectedConnection.value && fromNumber.value && toNumber.value &&
        fromNumber.value.length >= 10 && toNumber.value.length >= 10 &&
        webrtcStatus.value === 'ready'
})

const callStatusClass = computed(() => {
    if (callStatus.value === 'new') return 'bg-blue-100 text-blue-600 border-2 border-blue-300'
    if (callStatus.value === 'trying') return 'bg-yellow-100 text-yellow-600 border-2 border-yellow-300'
    if (callStatus.value === 'requesting') return 'bg-yellow-100 text-yellow-600 border-2 border-yellow-300'
    if (callStatus.value === 'recovering') return 'bg-orange-100 text-orange-600 border-2 border-orange-300'
    if (callStatus.value === 'ringing') return 'bg-yellow-100 text-yellow-600 border-2 border-yellow-300'
    if (callStatus.value === 'answering') return 'bg-yellow-100 text-yellow-600 border-2 border-yellow-300'
    if (callStatus.value === 'early') return 'bg-yellow-100 text-yellow-600 border-2 border-yellow-300'
    if (callStatus.value === 'active') return 'bg-green-100 text-green-600 border-2 border-green-300'
    if (callStatus.value === 'held') return 'bg-yellow-100 text-yellow-600 border-2 border-yellow-300'
    if (callStatus.value === 'hangup') return 'bg-red-100 text-red-600 border-2 border-red-300'
    if (callStatus.value === 'destroy') return 'bg-red-100 text-red-600 border-2 border-red-300'
    if (callStatus.value === 'purge') return 'bg-red-100 text-red-600 border-2 border-red-300'
    if (callStatus.value === 'failed') return 'bg-red-100 text-red-600 border-2 border-red-300'
    if (callStatus.value === 'busy') return 'bg-orange-100 text-orange-600 border-2 border-orange-300'
    return 'bg-gray-100 text-gray-600 border-2 border-gray-300'
})

const callStatusIcon = computed(() => {
    if (callStatus.value === 'new') return '📱'
    if (callStatus.value === 'trying') return '📞'
    if (callStatus.value === 'requesting') return '📡'
    if (callStatus.value === 'recovering') return '🔄'
    if (callStatus.value === 'ringing') return '🔔'
    if (callStatus.value === 'answering') return '📞'
    if (callStatus.value === 'early') return '🎵'
    if (callStatus.value === 'active') return '📞'
    if (callStatus.value === 'held') return '⏸️'
    if (callStatus.value === 'hangup') return '📴'
    if (callStatus.value === 'destroy') return '🗑️'
    if (callStatus.value === 'purge') return '🧹'
    if (callStatus.value === 'failed') return '❌'
    if (callStatus.value === 'busy') return '🚫'
    return '📱'
})

const callStatusText = computed(() => {
    if (isRinging.value && callStatus.value === 'trying') return 'Attempting Call...'
    if (isRinging.value && callStatus.value === 'requesting') return 'Sending Request...'
    if (isRinging.value && callStatus.value === 'answering') return 'Answering Call...'
    if (isRinging.value && callStatus.value === 'early') return 'Early Media...'
    if (isRinging.value) return 'Call Ringing...'
    if (callStatus.value === 'recovering') return 'Recovering Call...'
    if (isConnected.value && isOnHold.value) return 'Call On Hold'
    if (isConnected.value && callStatus.value === 'active') return 'Call Active'
    if (callStatus.value === 'failed') return 'Call Failed'
    if (callStatus.value === 'busy') return 'Line Busy'
    if (callStatus.value === 'destroy') return 'Call Destroyed'
    if (callStatus.value === 'purge') return 'Call Purged'
    if (callStatus.value === 'new') return 'New Call Created'
    return 'Ready to Call'
})

const callStatusEmoji = computed(() => {
    if (isRinging.value) return '🔔'
    if (isConnected.value) return '✅'
    if (callStatus.value === 'failed') return '❌'
    if (callStatus.value === 'busy') return '🚫'
    return '📞'
})

const callStatusMessage = computed(() => {
    if (isRinging.value) return 'Call is ringing...'
    if (isConnected.value) return 'Call connected successfully!'
    if (callStatus.value === 'failed') return 'Call failed to connect'
    if (callStatus.value === 'busy') return 'Line is busy'
    return callStatus.value
})

// Utility functions
const safeStringify = (obj) => {
    try {
        const seen = new WeakSet()
        const result = JSON.stringify(obj, (key, value) => {
            if (typeof value === "object" && value !== null) {
                if (seen.has(value)) return "[Circular Reference]"
                seen.add(value)
            }
            return value
        }, 2)
        
        // Truncate very long messages for better readability
        if (result && result.length > 200) {
            return result.substring(0, 200) + '... [truncated]'
        }
        return result
    } catch (error) {
        return `[Error serializing object: ${error.message}]`
    }
}

// Dialpad functions
const addDigit = (digit) => {
    toNumber.value += digit
    addDebugLog(`Added digit: ${digit}`, 'info')
}

const addPlus = () => {
    if (!toNumber.value.startsWith('+')) {
        toNumber.value = '+' + toNumber.value
    }
    addDebugLog('Added plus sign', 'info')
}

const backspace = () => {
    if (toNumber.value.length > 0) {
        toNumber.value = toNumber.value.slice(0, -1)
        addDebugLog('Removed last digit', 'info')
    }
}

const clearToNumber = () => {
    toNumber.value = ''
    addDebugLog('Cleared to number', 'info')
}

const handleKeydown = (event) => {
    // Allow: backspace, delete, tab, escape, enter, and navigation keys
    if ([8, 9, 27, 13, 46, 37, 38, 39, 40].includes(event.keyCode)) {
        return
    }
    
    // Allow: numbers, plus, asterisk, hash, and common phone characters
    if (/^[0-9+\-*#()\s]/.test(event.key)) {
        return
    }
    
    // Prevent any other keys
    event.preventDefault()
}

// Notification functions
const playNotificationSound = (type) => {
    try {
        const audio = new Audio()
        switch (type) {
            case 'ringing':
                audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'
                break
            case 'connected':
                audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'
                break
            case 'hangup':
                audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT'
                break
        }
        audio.play().catch(e => console.log('Audio notification failed:', e))
    } catch (error) {
        console.log('Notification sound error:', error)
    }
}

const showCallNotification = (title, message, type = 'info') => {
    // Browser notification
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: message,
            icon: '/favicon.ico',
            tag: 'call-notification'
        })
    }
    
    // Console notification
    const emoji = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : '📞'
    console.log(`${emoji} ${title}: ${message}`)
    
    // Play sound
    playNotificationSound(type === 'success' ? 'connected' : type === 'error' ? 'hangup' : 'ringing')
}

// Logging and transcript functions
const addDebugLog = (message, type = 'info') => {
    const timestamp = new Date().toLocaleTimeString()
    const logEntry = { 
        message, 
        type, 
        timestamp,
        id: Date.now() + Math.random() // Unique ID for each log
    }
    debugLogs.value.push(logEntry)
    
    // Console logging with colors
    const colors = {
        info: 'color: #3B82F6',
        success: 'color: #10B981', 
        error: 'color: #EF4444',
        warning: 'color: #F59E0B'
    }
    console.log(`%c[${timestamp}] ${type.toUpperCase()}: ${message}`, colors[type] || colors.info)
}

const addTranscriptEntry = (type, message) => {
    const timestamp = new Date().toLocaleTimeString()
    transcript.value.push({ type, message, timestamp })
}

const clearTranscript = () => {
    transcript.value = []
    addDebugLog('Transcript cleared', 'info')
}

const clearDebugLogs = () => {
    debugLogs.value = []
    addDebugLog('Debug logs cleared', 'info')
}

// Load connections
const loadConnections = async () => {
    try {
        addDebugLog('Loading connections from backend...', 'info')

        const response = await axios.get('/api/telnyx/connections', {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })

        if (response.data.success) {
            connections.value = response.data.connections
            backendStatus.value = 'connected'
            addDebugLog(`Loaded ${connections.value.length} connections`, 'success')
        } else {
            throw new Error(response.data.error || 'Failed to load connections')
        }
    } catch (error) {
        addDebugLog(`Failed to load connections: ${error.message}`, 'error')
        lastError.value = error.message
        backendStatus.value = 'error'
    }
}

// Initialize WebRTC
const initializeWebRTC = async () => {
    try {
        addDebugLog('Initializing WebRTC client...', 'info')

        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            await Notification.requestPermission()
        }

        // Get user media for local audio
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            const localAudio = document.getElementById('localMedia')
            if (localAudio) {
                localAudio.srcObject = localStream
            }
            addDebugLog('Local audio stream obtained', 'success')
        } catch (mediaError) {
            addDebugLog(`Failed to get local audio: ${mediaError.message}`, 'warning')
        }

        const telnyxModule = await import('@telnyx/webrtc')
        const TelnyxRTC = telnyxModule.default || telnyxModule.TelnyxRTC || telnyxModule

        addDebugLog('TelnyxRTC loaded successfully', 'success')

        webrtcClient = new TelnyxRTC({
            login: 'TateAndrew1122',    
            password: 'Toxic22211',
            audio: true,
            video: false,
            logLevel: 'debug'
        })

        // Set remote audio element
        webrtcClient.remoteElement = 'remoteMedia'

        // Event listeners
        webrtcClient.on('telnyx.ready', () => {
            webrtcStatus.value = 'ready'
            addDebugLog('WebRTC client ready', 'success')
            addTranscriptEntry('System', 'WebRTC client initialized and ready')
        })

        webrtcClient.on('telnyx.error', (error) => {
            addDebugLog(`WebRTC error: ${error.message}`, 'error')
            lastError.value = error.message
            webrtcStatus.value = 'error'
            addTranscriptEntry('Error', `WebRTC error: ${error.message}`)
        })

        webrtcClient.on('telnyx.notification', (notification) => {
            console.log("Notification OBJECT HERE " , notification);
            // addDebugLog(`WebRTC notification: ${safeStringify(notification)}`, 'info')
            
            if (notification.type === 'callUpdate') {
                handleCallUpdate(notification.call)
            }
            
            // Handle call recovery
            if (notification.type === 'callRecovery' && notification.call) {
                addDebugLog('Call recovery detected', 'warning')
                addTranscriptEntry('System', 'Call recovery in progress...')
                handleCallUpdate(notification.call)
            }
            
            // Handle incoming calls
            if (notification.type === 'incomingCall') {
                deb
                addDebugLog('Incoming call detected', 'info')
                addTranscriptEntry('System', 'Incoming call received')
                showCallNotification('📞 Incoming Call', 'You have an incoming call', 'info')
                handleIncomingCall(notification.call)
            }
        })

        await webrtcClient.connect()
        addDebugLog('WebRTC client started', 'success')

    } catch (error) {
        addDebugLog(`Failed to initialize WebRTC: ${error.message}`, 'error')
        lastError.value = error.message
        webrtcStatus.value = 'error'
    }
}

// Handle incoming calls
const handleIncomingCall = (call) => {
    try {
        currentCall = call
        callStatus.value = 'ringing'
        isRinging.value = true
        isConnected.value = false
        
        addDebugLog('Incoming call handled', 'info')
        addTranscriptEntry('Call', 'Incoming call received and handled')
        
        // Set up call event listeners for incoming call
        if (currentCall) {
            currentCall.on('stateChange', (state) => {
                addDebugLog(`Incoming call state changed to: ${state}`, 'info')
                callStatus.value = state
            })
            
            currentCall.on('error', (error) => {
                addDebugLog(`Incoming call error: ${error.message}`, 'error')
                addTranscriptEntry('Error', `Incoming call error: ${error.message}`)
            })
        }
    } catch (error) {
        addDebugLog(`Failed to handle incoming call: ${error.message}`, 'error')
        addTranscriptEntry('Error', `Failed to handle incoming call: ${error.message}`)
    }
}

// Handle call updates
const handleCallUpdate = (call) => {
    addDebugLog(`Call state updated: ${call.state}`, 'info')
    callStatus.value = call.state
    
    switch (call.state) {
        case 'new':
            addTranscriptEntry('Status', 'New call created')
            showCallNotification('📱 New Call', 'Call has been created', 'info')
            break
            
        case 'trying':
            isRinging.value = true
            isConnected.value = false
            addTranscriptEntry('Status', 'Attempting to call...')
            showCallNotification('📞 Attempting Call', `Trying to call ${toNumber.value}...`, 'info')
            break
            
        case 'requesting':
            isRinging.value = true
            isConnected.value = false
            addTranscriptEntry('Status', 'Sending call request to server...')
            showCallNotification('📡 Sending Request', 'Call request being sent to server', 'info')
            break
            
        case 'recovering':
            addTranscriptEntry('Status', 'Recovering previous call...')
            showCallNotification('🔄 Recovering Call', 'Recovering previous call after page refresh', 'warning')
            break
            
        case 'ringing':
            isRinging.value = true
            isConnected.value = false
            addTranscriptEntry('Status', 'Call is ringing...')
            showCallNotification('🔔 Call Ringing', `Calling ${toNumber.value}...`, 'info')
            break
            
        case 'answering':
            isRinging.value = true
            isConnected.value = false
            addTranscriptEntry('Status', 'Attempting to answer call...')
            showCallNotification('📞 Answering Call', 'Attempting to answer inbound call', 'info')
            break
            
        case 'early':
            isRinging.value = true
            isConnected.value = false
            addTranscriptEntry('Status', 'Receiving early media...')
            showCallNotification('🎵 Early Media', 'Receiving media before call answered', 'info')
            break
            
        case 'active':
            isCallActive.value = true
            isRinging.value = false
            isConnected.value = true
            startCallTimer()
            addTranscriptEntry('Status', 'Call is now active')
            showCallNotification('✅ Call Active', `Connected to ${toNumber.value}`, 'success')
            break
            
        case 'held':
            isOnHold.value = true
            addTranscriptEntry('Status', 'Call has been put on hold')
            showCallNotification('⏸️ Call On Hold', 'Call has been put on hold', 'warning')
            break
            
        case 'hangup':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call ended')
            showCallNotification('📴 Call Ended', `Call with ${toNumber.value} ended`, 'error')
            endCall()
            break
            
        case 'destroy':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call has been destroyed')
            showCallNotification('🗑️ Call Destroyed', 'Call has been destroyed', 'error')
            endCall()
            break
            
        case 'purge':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call has been purged')
            showCallNotification('🧹 Call Purged', 'Call has been purged from system', 'error')
            endCall()
            break
            
        case 'failed':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call failed to connect')
            showCallNotification('❌ Call Failed', `Failed to connect to ${toNumber.value}`, 'error')
            break
            
        case 'busy':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Line is busy')
            showCallNotification('🚫 Line Busy', `${toNumber.value} is busy`, 'warning')
            break
            
        default:
            addTranscriptEntry('Status', `Call state: ${call.state}`)
            addDebugLog(`Unhandled call state: ${call.state}`, 'warning')
            break
    }
}

// Start Call
const startCall = async () => {
    try {
        if (!webrtcClient || webrtcStatus.value !== 'ready') {
            throw new Error('WebRTC client not ready')
        }

        if (!selectedConnection.value) {
            throw new Error('Please select a SIP connection first')
        }

        isConnecting.value = true
        callStatus.value = 'Initializing call...'
        addDebugLog(`Starting call from ${fromNumber.value} to ${toNumber.value}`, 'info')
        addTranscriptEntry('Call', `Initiating call from ${fromNumber.value} to ${toNumber.value}`)
        
        const callDetail = {
            callerNumber: fromNumber.value,
            destinationNumber: toNumber.value,
            connectionId: selectedConnection.value,
        }

        currentCall = webrtcClient.newCall(callDetail)
        
        // Set up call event listeners
        if (currentCall) {
            currentCall.on('stateChange', (state) => {
                addDebugLog(`Call state changed to: ${state}`, 'info')
                callStatus.value = state
            })
            
            currentCall.on('error', (error) => {
                addDebugLog(`Call error: ${error.message}`, 'error')
                addTranscriptEntry('Error', `Call error: ${error.message}`)
            })
        }
        
        addDebugLog('Call initiated successfully', 'success')

    } catch (error) {
        addDebugLog(`Call failed: ${error.message}`, 'error')
        callStatus.value = 'Error: ' + error.message
        lastError.value = error.message
        addTranscriptEntry('Error', `Call failed: ${error.message}`)
    } finally {
        isConnecting.value = false
    }
}

// Call control functions
const toggleMute = () => {
    try {
        if (localStream) {
            const audioTrack = localStream.getAudioTracks()[0]
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled
                isMuted.value = !audioTrack.enabled
                
                if (isMuted.value) {
                    addTranscriptEntry('Control', 'Microphone muted')
                    showCallNotification('🔇 Muted', 'Microphone has been muted', 'info')
                } else {
                    addTranscriptEntry('Control', 'Microphone unmuted')
                    showCallNotification('🎤 Unmuted', 'Microphone has been unmuted', 'info')
                }
            }
        }
    } catch (error) {
        addDebugLog(`Mute toggle failed: ${error.message}`, 'error')
    }
}

const answerCall = () => {
    try {
        if (currentCall && currentCall.answer) {
            currentCall.answer()
            addTranscriptEntry('Control', 'Incoming call answered')
            showCallNotification('✅ Call Answered', 'Incoming call has been answered', 'success')
            addDebugLog('Incoming call answered', 'success')
        } else {
            addDebugLog('Answer method not available on current call', 'warning')
            addTranscriptEntry('Warning', 'Answer method not available')
        }
    } catch (error) {
        addDebugLog(`Failed to answer call: ${error.message}`, 'error')
        addTranscriptEntry('Error', `Failed to answer call: ${error.message}`)
    }
}

const refreshPhoneNumbers = () => {
    try {
        addDebugLog('Refreshing phone numbers...', 'info')
        lastPhoneNumbersRefresh.value = new Date()
        emit('refreshPhoneNumbers')
        addDebugLog('Phone numbers refresh requested', 'success')
    } catch (error) {
        addDebugLog(`Failed to refresh phone numbers: ${error.message}`, 'error')
    }
}

const rejectCall = () => {
    try {
        if (currentCall && currentCall.reject) {
            currentCall.reject()
            addTranscriptEntry('Control', 'Incoming call rejected')
            showCallNotification('❌ Call Rejected', 'Incoming call has been rejected', 'info')
            addDebugLog('Incoming call rejected', 'info')
            resetCallStates()
        } else {
            addDebugLog('Reject method not available on current call', 'warning')
            addTranscriptEntry('Warning', 'Reject method not available')
        }
    } catch (error) {
        addDebugLog(`Failed to reject call: ${error.message}`, 'error')
        addTranscriptEntry('Error', `Failed to reject call: ${error.message}`)
    }
}

const toggleHold = () => {
    try {
        isOnHold.value = !isOnHold.value
        
        if (isOnHold.value) {
            // Put call on hold
            if (currentCall && currentCall.hold) {
                currentCall.hold()
                addTranscriptEntry('Control', 'Call put on hold')
                showCallNotification('⏸️ Call On Hold', 'Call has been put on hold', 'info')
            } else {
                // Fallback: mute audio to simulate hold
                if (localStream) {
                    const audioTrack = localStream.getAudioTracks()[0]
                    if (audioTrack) {
                        audioTrack.enabled = false
                    }
                }
                addTranscriptEntry('Control', 'Call put on hold (audio muted)')
                showCallNotification('⏸️ Call On Hold', 'Call has been put on hold', 'info')
            }
        } else {
            // Resume call from hold
            if (currentCall && currentCall.unhold) {
                currentCall.unhold()
                addTranscriptEntry('Control', 'Call resumed from hold')
                showCallNotification('▶️ Call Resumed', 'Call has been resumed from hold', 'success')
            } else {
                // Fallback: unmute audio to simulate resume
                if (localStream) {
                    const audioTrack = localStream.getAudioTracks()[0]
                    if (audioTrack) {
                        audioTrack.enabled = true
                    }
                }
                addTranscriptEntry('Control', 'Call resumed from hold (audio restored)')
                showCallNotification('▶️ Call Resumed', 'Call has been resumed from hold', 'success')
            }
        }
        
        addDebugLog(`Call ${isOnHold.value ? 'put on hold' : 'resumed from hold'}`, 'info')
    } catch (error) {
        addDebugLog(`Hold toggle failed: ${error.message}`, 'error')
        addTranscriptEntry('Error', `Hold operation failed: ${error.message}`)
    }
}

const endCall = () => {
    try {
        addDebugLog('Ending call...', 'info')
        addTranscriptEntry('Control', 'Call ended by user')
        showCallNotification('📴 Call Ended', 'Call terminated by user', 'info')

        if (currentCall) {
            currentCall.hangup()
            currentCall = null
        }

        if (callTimer) {
            clearInterval(callTimer)
            callTimer = null
        }

        isCallActive.value = false
        isRinging.value = false
        isConnected.value = false
        isOnHold.value = false
        callStatus.value = ''
        callDuration.value = '00:00'
        addDebugLog('Call ended', 'success')
        
        // Reset call states for next call
        addTranscriptEntry('System', 'Call session ended, ready for next call')
    } catch (error) {
        addDebugLog(`Call end error: ${error.message}`, 'error')
        lastError.value = error.message
    }
}

// Reset all call states
const resetCallStates = () => {
    isCallActive.value = false
    isRinging.value = false
    isConnected.value = false
    isOnHold.value = false
    callStatus.value = ''
    callDuration.value = '00:00'
    addDebugLog('All call states reset', 'info')
}

const disconnectCall = () => {
    try {
        addDebugLog('Disconnecting call...', 'info')
        addTranscriptEntry('Control', 'Call disconnected by user')
        showCallNotification('🔌 Call Disconnected', 'Call has been disconnected', 'info')

        if (webrtcClient) {
            webrtcClient.disconnect()
            webrtcStatus.value = 'disconnected'
        }

        endCall()
        addDebugLog('Call disconnected', 'success')

    } catch (error) {
        addDebugLog(`Call disconnect error: ${error.message}`, 'error')
        lastError.value = error.message
    }
}

// Timer
const startCallTimer = () => {
    let seconds = 0
    callTimer = setInterval(() => {
        seconds++
        const minutes = Math.floor(seconds / 60)
        const secs = seconds % 60
        callDuration.value = `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    }, 1000)
}

// Lifecycle
onMounted(() => {
    addDebugLog('Component mounted', 'info')
    if (props.phoneNumbers && props.phoneNumbers.length > 0) {
        fromNumber.value = props.phoneNumbers[0].phone_number
        addDebugLog(`Default from number set: ${fromNumber.value}`, 'info')
    }
    loadConnections()
})

onUnmounted(() => {
    if (callTimer) {
        clearInterval(callTimer)
    }
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop())
    }
    if (webrtcClient) {
        webrtcClient.disconnect()
    }
})
</script>

<style scoped>
/* Custom scrollbar for transcript and logs */
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
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Call status animations */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Debug log animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.debug-log-entry {
  animation: fadeIn 0.3s ease-out;
}

/* Custom scrollbar for debug logs */
.debug-logs::-webkit-scrollbar {
  width: 8px;
}

.debug-logs::-webkit-scrollbar-track {
  background: #f9fafb;
  border-radius: 4px;
}

.debug-logs::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.debug-logs::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style> 