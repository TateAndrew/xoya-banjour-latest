<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import Label from '@/Components/ui/Label.vue'
import Separator from '@/Components/ui/Separator.vue'
import Tabs from '@/Components/ui/Tabs.vue'
import TabsList from '@/Components/ui/TabsList.vue'
import TabsTrigger from '@/Components/ui/TabsTrigger.vue'
import TabsContent from '@/Components/ui/TabsContent.vue'
import {
  Phone,
  PhoneCall,
  PhoneOff,
  PhoneIncoming,
  PhoneOutgoing,
  Mic,
  MicOff,
  Volume2,
  VolumeX,
  Pause,
  Play,
  Hash,
  Delete,
  Clock,
  Settings,
  History,
  User,
  FileText,
  Loader2,
  CheckCheck
} from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  phoneNumbers: Array,
  user: Object,
  recentCalls: Array,
})

// Tab state
const activeTab = ref('dialer')

// Reactive data
const connections = ref([])
const selectedConnection = ref('')
const selectedConnectionData = ref(null)
const connectionPhoneNumbers = ref([])
const fromNumber = ref('')
const toNumber = ref('')
const lastPhoneNumbersRefresh = ref(new Date())
const isCallActive = ref(false)
const isConnecting = ref(false)
const callStatus = ref('')
const backendStatus = ref('disconnected')
const webrtcStatus = ref('disconnected')

// Real-time transcription data
const currentCallControlId = ref(null)
const realtimeTranscript = ref('')
const transcriptionConfidence = ref(null)
const realtimeTranscriptionStatus = ref('idle')
const lastError = ref('')

const callDuration = ref('00:00')
const isRinging = ref(false)
const isConnected = ref(false)
const isMuted = ref(false)
const isOnHold = ref(false)
const isSpeakerOn = ref(false)
const isIncomingCall = ref(false)
const transcriptionStatus = ref('')
const callDirection = ref('')
const transcript = ref([])
const participants = ref([])
const callId = ref('')

let callTimer = null
let webrtcClient = null
let currentCall = null
let localStream = null
let ringingAudio = null

// Computed properties
const canMakeCall = computed(() => {
  return selectedConnection.value && fromNumber.value && toNumber.value &&
    fromNumber.value.length >= 10 && toNumber.value.length >= 10 &&
    webrtcStatus.value === 'ready'
})

const callStatusClass = computed(() => {
  if (callStatus.value === 'active') return 'bg-green-100 dark:bg-green-900 text-green-600'
  if (callStatus.value === 'ringing' || isRinging.value) return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600'
  if (callStatus.value === 'failed') return 'bg-red-100 dark:bg-red-900 text-red-600'
  return 'bg-secondary'
})

const callStatusText = computed(() => {
  const direction = callDirection.value
  const directionPrefix = direction === 'incoming' ? 'Incoming' : direction === 'outgoing' ? 'Outgoing' : ''
  
  if (isRinging.value && direction === 'incoming') return 'Incoming Call - Ringing'
  if (isRinging.value && direction === 'outgoing') return 'Outgoing Call - Ringing'
  if (isConnected.value && isOnHold.value) return `${directionPrefix} Call On Hold`
  if (isConnected.value && callStatus.value === 'active') return `${directionPrefix} Call Active`
  if (callStatus.value === 'failed') return `${directionPrefix} Call Failed`
  if (callStatus.value === 'busy') return 'Line Busy'
  return 'Ready to Call'
})

// Dialpad numbers
const dialPadNumbers = [
  [{ num: '1', letters: '' }, { num: '2', letters: 'ABC' }, { num: '3', letters: 'DEF' }],
  [{ num: '4', letters: 'GHI' }, { num: '5', letters: 'JKL' }, { num: '6', letters: 'MNO' }],
  [{ num: '7', letters: 'PQRS' }, { num: '8', letters: 'TUV' }, { num: '9', letters: 'WXYZ' }],
  [{ num: '*', letters: '' }, { num: '0', letters: '+' }, { num: '#', letters: '' }]
]

// Dialpad functions
const addDigit = (digit) => {
  if (isCallActive.value && currentCall) {
    try {
      currentCall.dtmf(digit)
      addTranscriptEntry('DTMF', `📱 Sent DTMF tone: ${digit}`)
    } catch (error) {
      addTranscriptEntry('Error', `Failed to send DTMF ${digit}: ${error.message}`)
    }
  } else {
    toNumber.value += digit
  }
}

const deleteDigit = () => {
  if (toNumber.value.length > 0) {
    toNumber.value = toNumber.value.slice(0, -1)
  }
}

const clearNumber = () => {
  toNumber.value = ''
}

// Fill from recent call
const fillFromRecentCall = (call) => {
  if (call.direction === 'outbound' || call.direction === 'outgoing') {
    toNumber.value = call.to_number
  } else if (call.direction === 'inbound' || call.direction === 'incoming') {
    toNumber.value = call.from_number
  } else {
    toNumber.value = call.to_number || call.from_number
  }
}

// Logging
const addTranscriptEntry = (type, message) => {
  const timestamp = new Date().toLocaleTimeString()
  const entry = { type, message, timestamp }
  transcript.value.push(entry)
  console.log('📝 Transcript:', entry)
}

// Load connections
const loadConnections = async () => {
  try {
    const response = await axios.get('/api/telnyx/connections', {
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.data.success) {
      connections.value = response.data.connections
      backendStatus.value = 'connected'
      
      selectedConnection.value = ''
      selectedConnectionData.value = null
      connectionPhoneNumbers.value = []
      fromNumber.value = ''
      
      if (connections.value.length > 0) {
        selectedConnection.value = connections.value[0].id
        await onConnectionChange()
      }
    } else {
      throw new Error(response.data.error || 'Failed to load connections')
    }
  } catch (error) {
    lastError.value = error.message
    backendStatus.value = 'error'
  }
}

// Connection change
const onConnectionChange = async () => {
  if (selectedConnection.value) {
    selectedConnectionData.value = connections.value.find(conn => conn.id == selectedConnection.value)
    
    if (selectedConnectionData.value) {
      connectionPhoneNumbers.value = selectedConnectionData.value.phone_numbers || []
      
      if (connectionPhoneNumbers.value.length > 0) {
        fromNumber.value = connectionPhoneNumbers.value[0].phone_number
      } else {
        fromNumber.value = ''
      }
      
      webrtcStatus.value = 'connecting'
      await initializeWebRTC()
    }
  } else {
    selectedConnectionData.value = null
    connectionPhoneNumbers.value = []
    fromNumber.value = ''
    webrtcStatus.value = 'disconnected'
  }
}

// Get credentials
const getCurrentConnectionCredentials = () => {
  if (!selectedConnectionData.value) return null
  
  const credentials = selectedConnectionData.value.credentials
  
  if (credentials && credentials.user_name && credentials.password) {
    return {
      login: credentials.user_name,
      password: credentials.password
    }
  }
  
  if (selectedConnectionData.value.user_name && selectedConnectionData.value.password) {
    return {
      login: selectedConnectionData.value.user_name,
      password: selectedConnectionData.value.password
    }
  }
  
  return null
}

// Initialize WebRTC
const initializeWebRTC = async () => {
  try {
    if (webrtcClient) {
      webrtcClient.disconnect()
      webrtcClient = null
    }

    if (!selectedConnection.value) {
      throw new Error('Please select a SIP connection first')
    }

    if ('Notification' in window && Notification.permission === 'default') {
      await Notification.requestPermission()
    }

    try {
      localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false })
      const localAudio = document.getElementById('localMedia')
      if (localAudio) {
        localAudio.srcObject = localStream
      }
    } catch (mediaError) {}

    const telnyxModule = await import('@telnyx/webrtc')
    let TelnyxRTC
    
    if (telnyxModule.default && typeof telnyxModule.default === 'function') {
      TelnyxRTC = telnyxModule.default
    } else if (telnyxModule.TelnyxRTC && typeof telnyxModule.TelnyxRTC === 'function') {
      TelnyxRTC = telnyxModule.TelnyxRTC
    } else if (typeof telnyxModule === 'function') {
      TelnyxRTC = telnyxModule
    }
    
    if (typeof TelnyxRTC !== 'function') {
      throw new Error('TelnyxRTC constructor not found')
    }

    const credentials = getCurrentConnectionCredentials()
    if (!credentials) {
      throw new Error('No valid credentials found')
    }
    
    webrtcClient = new TelnyxRTC({
      login: credentials.login,    
      password: credentials.password,
      audio: true,
      video: false,
      debug: false
    })
    
    addTranscriptEntry('System', `WebRTC client created with login: ${credentials.login}`)

    webrtcClient.remoteElement = 'remoteMedia'

    webrtcClient.on('telnyx.ready', () => {
      webrtcStatus.value = 'ready'
      addTranscriptEntry('System', '✅ WebRTC client ready for calls')
    })

    webrtcClient.on('telnyx.error', (error) => {
      lastError.value = error.message
      webrtcStatus.value = 'error'
      addTranscriptEntry('Error', `WebRTC error: ${error.message}`)
    })

    webrtcClient.on('telnyx.notification', (notification) => {
      if (notification.type === 'callUpdate' && notification.call) {
        handleCallUpdate(notification.call)
      }
      
      if (notification.type === 'callUpdate' && notification.call.state === 'ringing') {
        const callerNumber = notification.call.options?.remoteCallerNumber || 'Unknown'
        const destinationNumber = notification.call.options?.destinationNumber || 'Unknown'
        
        addTranscriptEntry('System', `📞 Incoming call from ${callerNumber} to ${destinationNumber}`)
        handleIncomingCall(notification.call)
      }
    })

    await webrtcClient.connect()

  } catch (error) {
    lastError.value = error.message
    webrtcStatus.value = 'error'
    addTranscriptEntry('Error', `WebRTC initialization failed: ${error.message}`)
  }
}

// Handle incoming call
const handleIncomingCall = (call) => {
  try {
    currentCall = call
    callStatus.value = 'ringing'
    isRinging.value = true
    isConnected.value = false
    isIncomingCall.value = true
    callDirection.value = 'incoming'
    
    playRingingSound()
    
    if (call && call.options) {
      const incomingCallerNumber = call.options.remoteCallerNumber || call.options.callerNumber
      const ourNumber = call.options.destinationNumber || call.options.callerNumber
      
      if (incomingCallerNumber) {
        toNumber.value = incomingCallerNumber
      }
      
      if (ourNumber) {
        fromNumber.value = ourNumber
      }
    }
    
    addTranscriptEntry('Call', `Incoming call from ${toNumber.value || 'Unknown'}`)
    
    if (currentCall && currentCall.on) {
      currentCall.on('stateChange', (state) => {
        handleCallUpdate(currentCall)
      })
    }
  } catch (error) {
    addTranscriptEntry('Error', `Failed to handle incoming call: ${error.message}`)
  }
}

// Handle call update
const handleCallUpdate = (call) => {
  callStatus.value = call.state

  switch (call.state) {
    case 'trying':
      isConnecting.value = true
      isConnected.value = false
      addTranscriptEntry('Status', 'Attempting to call...')
      break
      
    case 'ringing':
      isRinging.value = true
      isConnected.value = false
      addTranscriptEntry('Status', 'Call is ringing...')
      break
      
    case 'active':
      isCallActive.value = true
      isRinging.value = false
      isConnected.value = true
      stopRingingSound()
      startCallTimer()
      addTranscriptEntry('Status', 'Call is now active')
      break
      
    case 'held':
      isOnHold.value = true
      addTranscriptEntry('Status', 'Call on hold')
      break
      
    case 'hangup':
    case 'destroy':
    case 'failed':
    case 'busy':
      isRinging.value = false
      isConnected.value = false
      stopRingingSound()
      endCall(true)
      break
  }
}

// Start call
const makeCall = async () => {
  try {
    if (!webrtcClient || webrtcStatus.value !== 'ready') {
      throw new Error('WebRTC client not ready')
    }

    if (!selectedConnection.value || !fromNumber.value || !toNumber.value) {
      throw new Error('Please fill in all fields')
    }
    
    isConnecting.value = true
    isIncomingCall.value = false
    callDirection.value = 'outgoing'
    callStatus.value = 'Initializing call...'
    addTranscriptEntry('Call', `Calling ${toNumber.value} from ${fromNumber.value}`)
    
    const callParams = {
      destinationNumber: toNumber.value,
      callerNumber: fromNumber.value,
      audio: true,
      video: false
    }
    
    currentCall = webrtcClient.newCall(callParams)
    
    if (currentCall) {
      currentCall.on('stateChange', (state) => {
        handleCallUpdate(currentCall)
      })
      
      currentCall.on('error', (error) => {
        addTranscriptEntry('Error', `Call error: ${error.message}`)
      })
    }

  } catch (error) {
    callStatus.value = 'Error: ' + error.message
    addTranscriptEntry('Error', `Call failed: ${error.message}`)
    isConnecting.value = false
  }
}

// Call controls
const answerCall = () => {  
  try {
    stopRingingSound()
    
    if (currentCall) {
      currentCall.answer()
      isConnecting.value = false
      isRinging.value = false
      isCallActive.value = true
      isConnected.value = true
      
      addTranscriptEntry('Control', 'Incoming call answered')
      callStatus.value = 'answering'
    }
  } catch (error) {
    addTranscriptEntry('Error', `Failed to answer call: ${error.message}`)
  }
}

const rejectCall = () => {
  try {
    stopRingingSound()
    
    if (currentCall && currentCall.gotAnswer == false) {
      const validStatesForHangup = ['new', 'trying', 'ringing', 'early']
      if (validStatesForHangup.includes(currentCall.state)) {
        currentCall.hangup()
        addTranscriptEntry('Control', 'Incoming call rejected')
      }
      resetCallStates()
    }
  } catch (error) {
    addTranscriptEntry('Error', `Failed to reject call: ${error.message}`)
    resetCallStates()
  }
}

const toggleMute = () => {
  try {
    if (currentCall) {
      if (isMuted.value) {
        currentCall.unmuteAudio()
        isMuted.value = false
        addTranscriptEntry('Control', '🎤 Microphone unmuted')
      } else {
        currentCall.muteAudio()
        isMuted.value = true
        addTranscriptEntry('Control', '🔇 Microphone muted')
      }
    }
  } catch (error) {
    addTranscriptEntry('Error', `Mute operation failed: ${error.message}`)
  }
}

const toggleHold = async () => {
  try {
    if (!currentCall) return
    
    if (isOnHold.value) {
      await currentCall.unhold()
      isOnHold.value = false
      addTranscriptEntry('Control', '▶️ Call resumed')
    } else {
      await currentCall.hold()
      isOnHold.value = true
      addTranscriptEntry('Control', '⏸️ Call on hold')
    }
  } catch (error) {
    addTranscriptEntry('Error', `Hold operation failed: ${error.message}`)
  }
}

const toggleSpeaker = () => {
  isSpeakerOn.value = !isSpeakerOn.value
  addTranscriptEntry('Control', isSpeakerOn.value ? '🔊 Speaker on' : '🔈 Speaker off')
}

const endCall = (skipHangup = false) => {
  try {
    stopRingingSound()
    
    if (currentCall && !skipHangup) {
      const validStatesForHangup = ['new', 'trying', 'ringing', 'early', 'active', 'held']
      if (validStatesForHangup.includes(currentCall.state)) {
        currentCall.hangup()
      }
    }
    
    currentCall = null
    clearInterval(callTimer)
  
    resetCallStates()
    
    addTranscriptEntry('System', 'Call ended')
  } catch (error) {
    addTranscriptEntry('Error', `Error ending call: ${error.message}`)
    currentCall = null
    clearInterval(callTimer)
    resetCallStates()
  }
}

const resetCallStates = () => {
  isCallActive.value = false
  isRinging.value = false
  isConnected.value = false
  isOnHold.value = false
  isSpeakerOn.value = false
  isIncomingCall.value = false
  callDirection.value = ''
  callStatus.value = ''
  callDuration.value = '00:00'
  isConnecting.value = false
  transcriptionStatus.value = ''
}

// Ringing sound
const playRingingSound = () => {
  try {
    stopRingingSound()
    
    ringingAudio = new Audio()
    ringingAudio.src = '/ringtone/phone_ringing.mp3'
    ringingAudio.loop = true
    ringingAudio.volume = 0.5
    
    ringingAudio.play().catch(error => {
      console.error('Failed to play ringing sound:', error)
    })
  } catch (error) {
    console.error('Ringing sound error:', error)
  }
}

const stopRingingSound = () => {
  try {
    if (ringingAudio) {
      ringingAudio.pause()
      ringingAudio.currentTime = 0
      ringingAudio = null
    }
  } catch (error) {
    console.error('Error stopping ringing sound:', error)
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
  loadConnections()
})

onUnmounted(() => {
  if (webrtcClient) {
    webrtcClient.disconnect()
  }
  stopRingingSound()
  clearInterval(callTimer)
})
</script>

<template>
  <Head title="Professional Dialer" />

  <DashboardLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Professional Dialer</h1>
          <p class="text-muted-foreground">Make and manage calls with ease</p>
        </div>
        <div class="flex items-center gap-2">
          <Badge :variant="webrtcStatus === 'ready' ? 'default' : webrtcStatus === 'error' ? 'destructive' : 'secondary'">
            {{ webrtcStatus === 'ready' ? 'Connected' : webrtcStatus === 'connecting' ? 'Connecting...' : webrtcStatus === 'error' ? 'Error' : 'Disconnected' }}
          </Badge>
          <Link :href="route('dialer.history')">
            <Button variant="outline" class="gap-2">
              <History :size="16" />
              Call History
            </Button>
          </Link>
        </div>
      </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Calls Panel -->
      <Card class="lg:col-span-1">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Clock :size="20" />
            Recent Calls
          </CardTitle>
          <CardDescription>Your recent call activity</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3 max-h-[600px] overflow-y-auto">
            <div v-if="!recentCalls || recentCalls.length === 0" class="text-center py-8 text-muted-foreground">
              <Phone :size="48" class="mx-auto mb-4 opacity-50" />
              <p class="text-sm">No recent calls</p>
            </div>

            <div
              v-for="call in recentCalls"
              :key="call.id"
              @click="fillFromRecentCall(call)"
              class="flex items-center gap-3 p-3 rounded-lg border bg-card hover:bg-accent transition-colors cursor-pointer group"
            >
              <div :class="[
                'rounded-full p-2',
                call.direction === 'incoming' ? 'bg-green-100 text-green-600' : 
                call.direction === 'outgoing' ? 'bg-blue-100 text-blue-600' : 
                'bg-red-100 text-red-600'
              ]">
                <component 
                  :is="call.direction === 'incoming' ? PhoneIncoming : call.direction === 'outgoing' ? PhoneOutgoing : PhoneOff" 
                  :size="16" 
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">
                  {{ call.direction === 'outgoing' ? call.to_number : call.from_number }}
                </p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-xs text-muted-foreground">{{ call.time_ago }}</span>
                  <span v-if="call.duration" class="text-xs text-muted-foreground">• {{ call.duration }}</span>
                </div>
              </div>
              <Button
                variant="ghost"
                size="icon"
                @click.stop="fillFromRecentCall(call)"
                class="opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Phone :size="16" />
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Main Dialer Panel -->
      <Card class="lg:col-span-2">
        <CardContent class="pt-6">
          <Tabs v-model="activeTab">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="dialer">
                <Phone :size="16" class="mr-2" />
                Dialer
              </TabsTrigger>
              <TabsTrigger value="keypad">
                <Hash :size="16" class="mr-2" />
                Keypad
              </TabsTrigger>
              <TabsTrigger value="settings">
                <Settings :size="16" class="mr-2" />
                Settings
              </TabsTrigger>
            </TabsList>

            <!-- Dialer Tab -->
            <TabsContent value="dialer" class="space-y-6 mt-6">
              <!-- Call Status Display -->
              <div class="text-center space-y-4">
                <div
                  :class="[
                    'w-32 h-32 mx-auto rounded-full flex items-center justify-center transition-all duration-300',
                    callStatus === 'active' ? 'bg-green-100 dark:bg-green-900' :
                    isRinging.value || isConnecting.value ? 'bg-yellow-100 dark:bg-yellow-900 animate-pulse' :
                    callStatus === 'failed' ? 'bg-red-100 dark:bg-red-900' :
                    'bg-secondary'
                  ]"
                >
                  <PhoneCall
                    :size="48"
                    :class="[
                      callStatus === 'active' ? 'text-green-600' :
                      isRinging.value || isConnecting.value ? 'text-yellow-600' :
                      callStatus === 'failed' ? 'text-red-600' :
                      'text-muted-foreground'
                    ]"
                  />
                </div>

                <Badge
                  :variant="
                    callStatus === 'active' ? 'default' :
                    isRinging.value ? 'default' :
                    callStatus === 'failed' ? 'destructive' : 'secondary'
                  "
                  class="text-sm"
                >
                  {{ callStatusText }}
                </Badge>

                <!-- Incoming Call Display -->
                <div v-if="isIncomingCall && toNumber" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 rounded-lg">
                  <p class="text-sm text-green-600 font-medium">Incoming From</p>
                  <p class="text-2xl font-bold text-green-800 mt-1">{{ toNumber }}</p>
                </div>

                <!-- Outgoing Call Display -->
                <div v-else-if="!isIncomingCall && toNumber && (isCallActive || isRinging || isConnecting)" class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 rounded-lg">
                  <p class="text-sm text-blue-600 font-medium">Calling To</p>
                  <p class="text-2xl font-bold text-blue-800 mt-1">{{ toNumber }}</p>
                </div>

                <div v-if="callDuration !== '00:00'" class="text-3xl font-mono text-primary font-bold">
                  {{ callDuration }}
                </div>
              </div>

              <Separator />

              <!-- Phone Number Input -->
              <div v-if="!isCallActive && !isRinging && !isIncomingCall" class="space-y-4">
                <div>
                  <Label>Enter Phone Number</Label>
                  <div class="flex gap-2 mt-2">
                    <Input
                      v-model="toNumber"
                      type="tel"
                      placeholder="+1 (555) 123-4567"
                      class="text-lg"
                    />
                    <Button
                      v-if="toNumber"
                      variant="ghost"
                      size="icon"
                      @click="clearNumber"
                    >
                      <Delete :size="20" />
                    </Button>
                  </div>
                </div>
              </div>

              <!-- Call Controls -->
              <div class="grid gap-3">
                <!-- Incoming Call Buttons -->
                <template v-if="isIncomingCall && !isCallActive">
                  <Button
                    size="lg"
                    @click="answerCall"
                    class="w-full bg-green-600 hover:bg-green-700"
                  >
                    <Phone :size="20" class="mr-2" />
                    Answer Call
                  </Button>

                  <Button
                    variant="destructive"
                    size="lg"
                    @click="rejectCall"
                    class="w-full"
                  >
                    <PhoneOff :size="20" class="mr-2" />
                    Decline
                  </Button>
                </template>

                <!-- Make Call Button -->
                <Button
                  v-if="!isCallActive && !isRinging && !isIncomingCall"
                  size="lg"
                  @click="makeCall"
                  :disabled="!canMakeCall || isConnecting"
                  class="w-full"
                >
                  <Loader2 v-if="isConnecting" :size="20" class="mr-2 animate-spin" />
                  <Phone v-else :size="20" class="mr-2" />
                  {{ isConnecting ? 'Calling...' : 'Call' }}
                </Button>

                <!-- Active Call Controls -->
                <template v-if="isCallActive">
                  <div class="grid grid-cols-3 gap-3">
                    <Button
                      :variant="isMuted ? 'destructive' : 'outline'"
                      @click="toggleMute"
                    >
                      <component :is="isMuted ? MicOff : Mic" :size="20" class="mr-2" />
                      {{ isMuted ? 'Unmute' : 'Mute' }}
                    </Button>

                    <Button
                      :variant="isOnHold ? 'default' : 'outline'"
                      @click="toggleHold"
                    >
                      <component :is="isOnHold ? Play : Pause" :size="20" class="mr-2" />
                      {{ isOnHold ? 'Resume' : 'Hold' }}
                    </Button>

                    <Button
                      :variant="isSpeakerOn ? 'default' : 'outline'"
                      @click="toggleSpeaker"
                    >
                      <component :is="isSpeakerOn ? Volume2 : VolumeX" :size="20" class="mr-2" />
                      Speaker
                    </Button>
                  </div>

                  <Button
                    variant="destructive"
                    size="lg"
                    @click="endCall"
                    class="w-full"
                  >
                    <PhoneOff :size="20" class="mr-2" />
                    End Call
                  </Button>
                </template>
              </div>
            </TabsContent>

            <!-- Keypad Tab -->
            <TabsContent value="keypad" class="space-y-6 mt-6">
              <div class="text-center p-4 bg-secondary rounded-lg min-h-[60px] flex items-center justify-center">
                <p class="text-3xl font-mono font-bold">{{ toNumber || '•' }}</p>
              </div>

              <div class="grid gap-3">
                <div
                  v-for="(row, rowIndex) in dialPadNumbers"
                  :key="rowIndex"
                  class="grid grid-cols-3 gap-3"
                >
                  <Button
                    v-for="(btn, btnIndex) in row"
                    :key="btnIndex"
                    variant="outline"
                    size="lg"
                    @click="addDigit(btn.num)"
                    class="h-16 flex flex-col items-center justify-center hover:bg-primary hover:text-primary-foreground transition-colors"
                  >
                    <span class="text-2xl font-semibold">{{ btn.num }}</span>
                    <span v-if="btn.letters" class="text-[10px] text-muted-foreground">{{ btn.letters }}</span>
                  </Button>
                </div>

                <div class="grid grid-cols-3 gap-3 mt-2">
                  <Button
                    variant="outline"
                    size="lg"
                    @click="deleteDigit"
                    :disabled="!toNumber"
                  >
                    <Delete :size="20" />
                  </Button>

                  <Button
                    v-if="!isCallActive && !isRinging"
                    size="lg"
                    @click="makeCall"
                    :disabled="!canMakeCall"
                    class="bg-green-600 hover:bg-green-700"
                  >
                    <Phone :size="24" />
                  </Button>

                  <Button
                    v-else
                    variant="destructive"
                    size="lg"
                    @click="endCall"
                  >
                    <PhoneOff :size="24" />
                  </Button>

                  <Button
                    variant="outline"
                    size="lg"
                    @click="clearNumber"
                    :disabled="!toNumber"
                  >
                    Clear
                  </Button>
                </div>
              </div>
            </TabsContent>

            <!-- Settings Tab -->
            <TabsContent value="settings" class="space-y-6 mt-6">
              <div class="space-y-4">
                <div>
                  <Label>SIP Connection</Label>
                  <select 
                    v-model="selectedConnection"
                    @change="onConnectionChange"
                    class="w-full mt-2 px-3 py-2 border border-input bg-background rounded-md"
                  >
                    <option value="">Select SIP Connection</option>
                    <option v-for="conn in connections" :key="conn.id" :value="conn.id">
                      {{ conn.name }} ({{ conn.status || 'Unknown' }})
                    </option>
                  </select>
                </div>

                <div v-if="connectionPhoneNumbers.length > 0">
                  <Label>Outbound Number</Label>
                  <select 
                    v-model="fromNumber"
                    class="w-full mt-2 px-3 py-2 border border-input bg-background rounded-md"
                  >
                    <option value="">Select Phone Number</option>
                    <option v-for="phone in connectionPhoneNumbers" :key="phone.id" :value="phone.phone_number">
                      {{ phone.phone_number }}
                    </option>
                  </select>
                </div>

                <Separator />

                <div>
                  <h4 class="font-medium mb-3">Connection Status</h4>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">Backend:</span>
                      <Badge :variant="backendStatus === 'connected' ? 'default' : 'destructive'">
                        {{ backendStatus }}
                      </Badge>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-muted-foreground">WebRTC:</span>
                      <Badge :variant="webrtcStatus === 'ready' ? 'default' : webrtcStatus === 'error' ? 'destructive' : 'secondary'">
                        {{ webrtcStatus }}
                      </Badge>
                    </div>
                  </div>
                </div>

                <div v-if="lastError" class="p-3 bg-destructive/10 border border-destructive text-destructive text-sm rounded-lg">
                  {{ lastError }}
                </div>
              </div>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>

    <!-- Hidden audio elements -->
    <audio id="localMedia" autoplay muted style="display: none;"></audio>
    <audio id="remoteMedia" autoplay style="display: none;"></audio>
  </DashboardLayout>
</template>
