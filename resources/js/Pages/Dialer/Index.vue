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

       

        <!-- Regular Dialer Interface -->
        <div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Beautiful Telnyx Dialer Interface -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Panel - Recent Calls -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-6">
                                <!-- Recent Calls Header -->
                                <div class="text-center">
                                    <h3 class="text-xl font-bold text-gray-900 mb-4">📞 Recent Calls</h3>
                                </div>

                                <!-- Recent Calls List -->
                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                    <!-- Empty state when no recent calls -->
                                    <div v-if="!recentCalls || recentCalls.length === 0" class="text-center py-8 text-gray-500">
                                        <span class="text-2xl mb-2 block">📞</span>
                                        <p>No recent calls</p>
                                    </div>

                                    <!-- Dynamic Recent Call Items -->
                                    <div v-for="call in recentCalls" :key="call.id" 
                                         @click="fillFromRecentCall(call)"
                                         class="bg-gray-50 rounded-lg p-3 border border-gray-200 hover:bg-gray-100 transition-colors cursor-pointer">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                     :class="{
                                                         'bg-green-100': call.direction === 'inbound' || call.direction === 'incoming',
                                                         'bg-blue-100': call.direction === 'outbound' || call.direction === 'outgoing',
                                                         'bg-red-100': call.status === 'failed' || call.status === 'missed',
                                                         'bg-gray-100': !call.direction
                                                     }">
                                                    <span class="text-sm"
                                                          :class="{
                                                              'text-green-600': call.direction === 'inbound' || call.direction === 'incoming',
                                                              'text-blue-600': call.direction === 'outbound' || call.direction === 'outgoing',
                                                              'text-red-600': call.status === 'failed' || call.status === 'missed',
                                                              'text-gray-600': !call.direction
                                                          }">📞</span>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        {{ call.direction === 'outbound' || call.direction === 'outgoing' ? call.to_number : call.from_number }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ call.time_ago }}</div>
                                                    <div v-if="call.duration" class="text-xs text-gray-400">Duration: {{ call.duration }}</div>
                                                </div>
                                            </div>
                                            <div class="text-xs font-medium"
                                                 :class="{
                                                     'text-green-600': call.direction === 'inbound' || call.direction === 'incoming',
                                                     'text-blue-600': call.direction === 'outbound' || call.direction === 'outgoing',
                                                     'text-red-600': call.status === 'failed' || call.status === 'missed',
                                                     'text-gray-600': !call.direction
                                                 }">
                                                {{ call.direction === 'inbound' || call.direction === 'incoming' ? 'Incoming' : 
                                                   call.direction === 'outbound' || call.direction === 'outgoing' ? 'Outgoing' : 
                                                   call.status === 'failed' ? 'Failed' :
                                                   call.status === 'missed' ? 'Missed' : 'Unknown' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Call History Button -->
                                <div class="pt-4 border-t border-gray-200">
                                    <a href="/dialer/history" 
                                       class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-center block">
                                        📝 View All History
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>                    <!-- Center Panel - Dialer -->
                    <div class="lg:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-6">
                                <!-- Call Status Display -->
                                <div class="text-center">
                                    <div class="w-24 h-24 mx-auto mb-4 rounded-full flex items-center justify-center text-4xl shadow-lg transform transition-all duration-300 hover:scale-105"
                                         :class="callStatusClass">
                                        {{ callStatusIcon }}
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ callStatusText }}</h3>
                                    <div v-if="callDirection" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-3"
                                         :class="callDirection === 'incoming' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                                        {{ callDirection === 'incoming' ? '📞 Incoming Call' : '📞 Outgoing Call' }}
                                    </div>
                                    
                                    <!-- Dialer Number Display -->
                                    <!-- <div v-if="fromNumber" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div class="text-sm text-blue-600 font-medium mb-1">Dialer Number</div>
                                        <div class="text-lg font-bold text-blue-800">{{ fromNumber }}</div>
                                    </div> -->
                                    
                    <!-- Incoming Call Number Display -->
                    <div v-if="isIncomingCall && toNumber" class="mb-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="text-sm text-green-600 font-medium mb-1">Incoming From</div>
                        <div class="text-lg font-bold text-green-800">{{ toNumber }}</div>
                        <div v-if="fromNumber" class="text-xs text-green-600 mt-1">To: {{ fromNumber }}</div>
                    </div>
                    
                    <!-- Outgoing Call Number Display -->
                    <div v-if="!isIncomingCall && toNumber && (isCallActive || isRinging || isConnecting)" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="text-sm text-blue-600 font-medium mb-1">Calling To</div>
                        <div class="text-lg font-bold text-blue-800">{{ toNumber }}</div>
                        <!-- <div v-if="fromNumber" class="text-xs text-blue-600 mt-1">From: {{ fromNumber }}</div> -->
                    </div>
                                    
                                    <p v-if="callDuration" class="text-2xl font-mono text-blue-600 font-bold tracking-wider">{{ callDuration }}</p>
                                </div>

                                <!-- Call Controls -->
                                <div class="space-y-3">
                                    <!-- Incoming Call Buttons: Answer and Decline for incoming calls -->
                                    <template v-if="isIncomingCall && !isCallActive">
                                        <!-- Answer Call Button -->
                                        <button @click="answerCall" 
                                                class="w-full py-4 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-xl">📞</span>
                                                <span>Answer Call</span>
                                            </div>
                                        </button>
                                        
                                        <!-- Decline Call Button -->
                                        <button @click="rejectCall" 
                                                class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-xl">❌</span>
                                                <span>Decline Call</span>
                                            </div>
                                        </button>
                                    </template>

                                    <!-- Outgoing Call Buttons: End call during ringing/trying -->
                                    <template v-if="isConnecting && !isCallActive">
                                        <!-- End Call Button for outgoing calls -->
                                        <button @click="endCall" 
                                                class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-xl">📴</span>
                                                <span>End Call</span>
                                            </div>
                                        </button>
                                    </template>

                                    <!-- Active Call Buttons: Show for all active calls (incoming and outgoing) -->
                                    <template v-if="isCallActive">
                                        <!-- Mute Button -->
                                        <button @click="toggleMute" 
                                                :class="`w-full py-3 rounded-lg text-white font-semibold transition-all duration-200 ${
                                                    isMuted ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-600 hover:bg-gray-700'
                                                }`">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg">{{ isMuted ? '🔇' : '🎤' }}</span>
                                                <span>{{ isMuted ? 'Unmute' : 'Mute' }}</span>
                                            </div>
                                        </button>

                                        <!-- Hold Button -->
                                        <button @click="toggleHold" 
                                                :class="`w-full py-3 rounded-lg text-white font-semibold transition-all duration-200 ${
                                                    isOnHold ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-blue-600 hover:bg-blue-700'
                                                }`">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg">{{ isOnHold ? '▶️' : '⏸️' }}</span>
                                                <span>{{ isOnHold ? 'Resume' : 'Hold' }}</span>
                                            </div>
                                        </button>

                                        <!-- Loudspeaker Button -->
                                        <button @click="toggleLoudspeaker" 
                                                :class="`w-full py-3 rounded-lg text-white font-semibold transition-all duration-200 ${
                                                    isLoudspeakerOn ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700'
                                                }`">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg">{{ isLoudspeakerOn ? '🔊' : '🔈' }}</span>
                                                <span>{{ isLoudspeakerOn ? 'Speaker Off' : 'Speaker On' }}</span>
                                            </div>
                                        </button>

                                        <!-- End Call Button -->
                                        <button @click="endCall" 
                                                class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg">📴</span>
                                                <span>End Call</span>
                                            </div>
                                        </button>

                                        <!-- Disconnect Button -->
                                        <button @click="disconnectCall" 
                                                class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg">🔌</span>
                                                <span>Disconnect</span>
                                            </div>
                                        </button>

                                        <!-- Transcription Button -->
                                        <button @click="toggleTranscription" 
                                                :class="transcriptionStatus === 'started' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'"
                                                class="w-full py-3 text-white rounded-lg font-semibold transition-all duration-200">
                                            <div class="flex items-center justify-center space-x-2">
                                                <span class="text-lg">{{ transcriptionStatus === 'started' ? '📝' : '🎤' }}</span>
                                                <span>{{ transcriptionStatus === 'started' ? 'Stop Transcript' : 'Start Transcript' }}</span>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <!-- Dialer Header -->
                                <!-- <div class="text-center">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Audio Dialer</h3>
                                    <p class="text-gray-600">Professional calling with WebRTC</p>
                                </div> -->

                                <!-- Connection Selection - Hide during incoming call -->
                                <div v-if="!isIncomingCall">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SIP Connection</label>
                                    <select v-model="selectedConnection" 
                                            @change="onConnectionChange"
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select SIP Connection</option>
                                        <option v-for="connection in connections" :key="connection.id" :value="connection.id">
                                            {{ connection.name }} ({{ connection.status }})
                                        </option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">
                                        SIP connections loaded from your database
                                    </p>
                                </div>





                                <!-- User Information -->
                                <!-- <div v-if="props.user" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
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
                                                    Available SIP Connections: {{ connections.length }}
                                                </p>
                                                <p class="text-xs text-blue-500 mt-1">
                                                    Last updated: {{ lastPhoneNumbersRefresh.toLocaleTimeString() }}
                                                </p>
                                                <p class="text-xs text-blue-500 mt-1">
                                                    Status: <span class="font-medium text-green-600">Active</span>
                                                </p>
                                            </div>
                                        </div>
                                        <button @click="loadConnections" 
                                                class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition-all duration-200"
                                                title="Refresh connections">
                                            🔄
                                        </button>
                                    </div>
                                </div> -->

                                <!-- Phone Numbers - Hide during incoming call -->
                                <div v-if="!isIncomingCall" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            From Number (SIP Connection Assignment)
                                        </label>
                                        <select v-model="fromNumber" 
                                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                                :disabled="!selectedConnection">
                                            <option value="" disabled>
                                                {{ selectedConnection ? 'Select phone number from connection' : 'Select connection first' }}
                                            </option>
                                            <option v-for="phone in connectionPhoneNumbers" 
                                                    :key="phone.id" 
                                                    :value="phone.phone_number"
                                                    class="py-2">
                                                {{ phone.phone_number }} 
                                                <span v-if="phone.assignment_type" class="text-gray-500">({{ phone.assignment_type }})</span>
                                            </option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Phone numbers assigned to the selected SIP connection
                                        </p>
                                        <div v-if="fromNumber" class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700">
                                            ✅ Active: {{ fromNumber }}
                                        </div>
                                        <div v-if="!selectedConnection" class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-700">
                                            ⚠️ Please select a connection first
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">To Number</label>
                                        <div class="relative">
                                            <input v-model="toNumber" 
                                                   type="tel" 
                                                   placeholder="+18004377950" 
                                                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <button @click="clearToNumber" 
                                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Beautiful Telnyx Dialpad - Hide during incoming call -->
                                <div v-if="!isIncomingCall" class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 shadow-inner">
                                    <h4 class="text-lg font-bold text-gray-800 mb-4 text-center">
                                        {{ isCallActive ? '📱 DTMF Keypad' : '📞 Dialer' }}
                                    </h4>
                                    <div class="grid grid-cols-3 gap-3">
                                        <!-- Row 1: 1, 2, 3 -->
                                        <button @click="addDigit('1')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            1
                                        </button>
                                        <button @click="addDigit('2')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            2
                                        </button>
                                        <button @click="addDigit('3')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            3
                                        </button>
                                        
                                        <!-- Row 2: 4, 5, 6 -->
                                        <button @click="addDigit('4')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            4
                                        </button>
                                        <button @click="addDigit('5')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            5
                                        </button>
                                        <button @click="addDigit('6')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            6
                                        </button>
                                        
                                        <!-- Row 3: 7, 8, 9 -->
                                        <button @click="addDigit('7')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            7
                                        </button>
                                        <button @click="addDigit('8')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            8
                                        </button>
                                        <button @click="addDigit('9')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            9
                                        </button>
                                        
                                        <!-- Row 4: *, 0, # -->
                                        <button @click="addDigit('*')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            *
                                        </button>
                                        <button @click="addDigit('0')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
                                            0
                                        </button>
                                        <button @click="addDigit('#')" 
                                                class="h-16 bg-white hover:bg-blue-50 active:bg-blue-100 border border-gray-300 rounded-xl shadow-sm hover:shadow-md active:scale-95 transition-all duration-200 text-2xl font-bold text-gray-800 hover:text-blue-600">
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

                                <!-- Beautiful Call Button - Hide during incoming call -->
                                <button v-if="!isIncomingCall"
                                        @click="startCall" 
                                        :disabled="!canMakeCall || isConnecting"
                                        class="w-full py-6 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl disabled:opacity-50 disabled:cursor-not-allowed text-xl font-bold shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95 transition-all duration-200">
                                    <div class="flex items-center justify-center space-x-3">
                                        <span v-if="isConnecting" class="animate-spin text-2xl">🔄</span>
                                        <span v-else class="text-2xl">📞</span>
                                        <span>{{ isConnecting ? 'Connecting...' : 'Start Call' }}</span>
                                    </div>
                                </button>

                                <!-- Connection Status - Hide during incoming call -->
                                <div v-if="!isIncomingCall" class="bg-gray-50 rounded-lg p-4">
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
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Connections:</span>
                                            <span class="text-gray-900 font-medium">
                                                {{ connections.length }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Error Display -->
                                <div v-if="lastError" class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-start space-x-2">
                                        <span class="text-red-600 text-lg">⚠️</span>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-semibold text-red-900 mb-1">Error</h4>
                                            <p class="text-xs text-red-700 break-words">{{ lastError }}</p>
                                            <button @click="lastError = ''" 
                                                    class="mt-2 text-xs text-red-600 hover:text-red-800 underline">
                                                Dismiss
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="space-y-3">
                                    <!-- <button @click="loadConnections" 
                                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        🔄 Refresh Connections
                                    </button> -->
                                    
                                    <!-- Debug: Test Incoming Call -->
                                    <!-- <button @click="simulateIncomingCall" 
                                            class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                        🧪 Test Incoming Call
                                    </button> -->
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

                                <!-- Real-time Transcription Display -->
                                <div v-if="realtimeTranscript || realtimeTranscriptionStatus !== 'idle'" 
                                     class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-blue-900 text-sm">
                                            🎤 Real-time Transcription
                                        </h4>
                                        <div class="flex items-center space-x-2">
                                            <span :class="`inline-flex px-2 py-1 rounded-full text-xs font-medium ${
                                                realtimeTranscriptionStatus === 'processing' ? 'bg-yellow-100 text-yellow-800' :
                                                realtimeTranscriptionStatus === 'completed' ? 'bg-green-100 text-green-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`">
                                                {{ realtimeTranscriptionStatus === 'processing' ? '🔄 Processing' :
                                                   realtimeTranscriptionStatus === 'completed' ? '✅ Completed' : 
                                                   '⏸️ Idle' }}
                                            </span>
                                            <span v-if="transcriptionConfidence" 
                                                  class="text-xs text-blue-600">
                                                {{ Math.round(transcriptionConfidence * 100) }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm text-blue-800 bg-white p-3 rounded border">
                                        <p v-if="realtimeTranscript" class="italic">
                                            "{{ realtimeTranscript }}"
                                        </p>
                                        <p v-else class="text-gray-500">
                                            Waiting for speech...
                                        </p>
                                    </div>
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
    recentCalls: Array,
})

const emit = defineEmits(['refreshPhoneNumbers'])

// Reactive data
const connections = ref([])
const selectedConnection = ref('')
const selectedConnectionData = ref(null) // To store details of the selected connection
const connectionPhoneNumbers = ref([]) // Phone numbers from selected connection
const fromNumber = ref('+12037206619')
const toNumber = ref('+18004377950')
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
const realtimeTranscriptionStatus = ref('idle') // idle, processing, completed
const lastError = ref('')

const callDuration = ref('00:00')
const isRinging = ref(false)
const isConnected = ref(false)
const isMuted = ref(false)
const isOnHold = ref(false)
const isLoudspeakerOn = ref(false) // Track loudspeaker state
const isIncomingCall = ref(false) // Track if current call is incoming
const transcriptionStatus = ref('') // Track transcription status: '', 'started', 'stopped'
const callDirection = ref('') // 'incoming' or 'outgoing'
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
    const direction = callDirection.value
    const directionPrefix = direction === 'incoming' ? 'Incoming' : direction === 'outgoing' ? 'Outgoing' : ''
    
    if (isRinging.value && callStatus.value === 'trying') return `${directionPrefix} Call - Attempting...`
    if (isRinging.value && callStatus.value === 'requesting') return `${directionPrefix} Call - Sending Request...`
    if (isRinging.value && callStatus.value === 'answering') return `${directionPrefix} Call - Answering...`
    if (isRinging.value && callStatus.value === 'early') return `${directionPrefix} Call - Early Media...`
    if (isRinging.value && direction === 'incoming') return 'Incoming Call - Ringing'
    if (isRinging.value && direction === 'outgoing') return 'Outgoing Call - Ringing'
    if (isRinging.value) return 'Call Ringing...'
    if (callStatus.value === 'recovering') return 'Recovering Call...'
    if (isConnected.value && isOnHold.value) return `${directionPrefix} Call On Hold`
    if (isConnected.value && callStatus.value === 'active') return `${directionPrefix} Call Active`
    if (callStatus.value === 'failed') return `${directionPrefix} Call Failed`
    if (callStatus.value === 'busy') return 'Line Busy'
    if (callStatus.value === 'destroy') return 'Call Destroyed'
    if (callStatus.value === 'purge') return 'Call Purged'
    if (callStatus.value === 'new') return `${directionPrefix} Call Created`
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
    if (callStatus.value === 'destroy') return 'Call has been destroyed'
    if (callStatus.value === 'purge') return 'Call has been purged'
    if (callStatus.value === 'failed') return 'Call failed to connect'
    if (callStatus.value === 'new') return 'Call has been created'
    if (callStatus.value === 'trying') return 'Call is trying to connect'
    if (callStatus.value === 'requesting') return 'Call is requesting to connect'
    if (callStatus.value === 'recovering') return 'Call is recovering'
    if (callStatus.value === 'ringing') return 'Call is ringing'
    if (callStatus.value === 'answering') return 'Call is answering'
    if (callStatus.value === 'early') return 'Call is early'
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
    if (isCallActive.value && currentCall) {
        // Send DTMF during active call using official Telnyx method
        try {
            currentCall.dtmf(digit)
            addTranscriptEntry('DTMF', `📱 Sent DTMF tone: ${digit}`)
            showCallNotification('📱 DTMF', `Sent tone: ${digit}`, 'info')
        } catch (error) {
            addTranscriptEntry('Error', `Failed to send DTMF ${digit}: ${error.message}`)
        }
    } else {
        // Add to dialer number when not in call
        toNumber.value += digit
    }
}

const addPlus = () => {
    if (!toNumber.value.startsWith('+')) {
        toNumber.value = '+' + toNumber.value
    }
}

const backspace = () => {
    if (toNumber.value.length > 0) {
        toNumber.value = toNumber.value.slice(0, -1)
    }
}

const clearToNumber = () => {
    toNumber.value = ''
}

// Fill dialer from recent call
const fillFromRecentCall = (call) => {
    // Determine which number to call based on the direction
    if (call.direction === 'outbound' || call.direction === 'outgoing') {
        // For outgoing calls, use the number we called
        toNumber.value = call.to_number
    } else if (call.direction === 'inbound' || call.direction === 'incoming') {
        // For incoming calls, use the number that called us
        toNumber.value = call.from_number
    } else {
        // Fallback to to_number
        toNumber.value = call.to_number || call.from_number
    }
    
    addTranscriptEntry('System', `Number loaded from recent call: ${toNumber.value}`)
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
        audio.play().catch(e => {})
    } catch (error) {
        // Audio notification error handled silently
    }
}

// Play continuous ringing sound for incoming calls
const playRingingSound = () => {
    try {
        // Stop any existing ringing
        stopRingingSound()
        
        // Create audio element for ringing
        ringingAudio = new Audio()
        
        // Use local ringtone file
        ringingAudio.src = '/ringtone/phone_ringing.mp3'
        ringingAudio.loop = true
        ringingAudio.volume = 0.5
        
        // Play the ringing sound
        ringingAudio.play().catch(error => {
            console.error('Failed to play ringing sound:', error)
            addTranscriptEntry('System', 'Ringing sound failed to play - browser may require user interaction first')
        })
        
        addTranscriptEntry('System', '🔔 Ringing sound started')
    } catch (error) {
        console.error('Ringing sound error:', error)
    }
}

// Stop the ringing sound
const stopRingingSound = () => {
    try {
        if (ringingAudio) {
            ringingAudio.pause()
            ringingAudio.currentTime = 0
            ringingAudio = null
            addTranscriptEntry('System', '🔕 Ringing sound stopped')
        }
    } catch (error) {
        console.error('Error stopping ringing sound:', error)
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
    
    // Notification logged to transcript instead of console
    
    // Play sound
    playNotificationSound(type === 'success' ? 'connected' : type === 'error' ? 'hangup' : 'ringing')
}

// Logging and transcript functions

const addTranscriptEntry = (type, message) => {
    const timestamp = new Date().toLocaleTimeString()
    transcript.value.push({ type, message, timestamp })
}

const clearTranscript = () => {
    transcript.value = []
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
            
            // Reset selection
            selectedConnection.value = ''
            selectedConnectionData.value = null
            connectionPhoneNumbers.value = []
            fromNumber.value = ''
            
            // Auto-select the first connection by default
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



// Handle connection selection change
const onConnectionChange = async () => {
    if (selectedConnection.value) {
        // Find the selected connection data
        selectedConnectionData.value = connections.value.find(conn => conn.id == selectedConnection.value)
        
        if (selectedConnectionData.value) {
            // Database connections have phone numbers
            connectionPhoneNumbers.value = selectedConnectionData.value.phone_numbers || []
            
            // Auto-select the first phone number if available
            if (connectionPhoneNumbers.value.length > 0) {
                fromNumber.value = connectionPhoneNumbers.value[0].phone_number
                //addTranscriptEntry('System', `SIP connection changed to: ${selectedConnectionData.value.name}`)
            } else {
                fromNumber.value = ''
                //addTranscriptEntry('System', `SIP connection changed to: ${selectedConnectionData.value.name} - no phone numbers assigned`)
            }
            
            // Automatically initialize WebRTC with the selected connection
            // addTranscriptEntry('System', 'Initializing WebRTC with selected connection...')
            webrtcStatus.value = 'connecting'
            await initializeWebRTC()
        }
    } else {
        // Reset when no connection is selected
        selectedConnectionData.value = null
        connectionPhoneNumbers.value = []
        fromNumber.value = ''
        webrtcStatus.value = 'disconnected'
        addTranscriptEntry('System', 'No connection selected')
    }
}

// Get current connection credentials from database
const getCurrentConnectionCredentials = () => {
    if (!selectedConnectionData.value) {
        return null
    }
    
    const credentials = selectedConnectionData.value.credentials
    
    if (credentials && credentials.user_name && credentials.password) {
        return {
            login: credentials.user_name,
            password: credentials.password
        }
    }
    
    // Check if credentials are stored directly on the connection object
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
    console.log('🚀 initializeWebRTC called')
    console.log('🚀 Selected connection:', selectedConnection.value)
    console.log('🚀 Selected connection data:', selectedConnectionData.value)
    
    try {
        // Disconnect existing client if any
        if (webrtcClient) {
            console.log('🔌 Disconnecting existing WebRTC client...')
            webrtcClient.disconnect()
            webrtcClient = null
        }

        // Check if a connection is selected
        if (!selectedConnection.value) {
            throw new Error('Please select a SIP connection first')
        }
        console.log('✅ Connection selected, proceeding...')

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
        } catch (mediaError) {
        }

        // Import Telnyx WebRTC module
        console.log('📦 Importing Telnyx WebRTC module...')
        const telnyxModule = await import('@telnyx/webrtc')
        console.log('📦 Telnyx module imported:', telnyxModule)
        console.log('📦 Module keys:', Object.keys(telnyxModule))
        console.log('📦 Module.default:', telnyxModule.default)
        console.log('📦 Module.TelnyxRTC:', telnyxModule.TelnyxRTC)
        
        // Handle different module export formats (same as Pusher fix)
        let TelnyxRTC
        
        // Check if it's a module namespace object (Vite production build)
        if (telnyxModule.default && typeof telnyxModule.default === 'function') {
            // ESM default export
            TelnyxRTC = telnyxModule.default
            console.log('📦 Using telnyxModule.default')
        } else if (telnyxModule.TelnyxRTC && typeof telnyxModule.TelnyxRTC === 'function') {
            // Named export
            TelnyxRTC = telnyxModule.TelnyxRTC
            console.log('📦 Using telnyxModule.TelnyxRTC')
        } else if (typeof telnyxModule === 'function') {
            // CommonJS or direct function export
            TelnyxRTC = telnyxModule
            console.log('📦 Using telnyxModule directly')
        } else {
            // Try to find the constructor in the module
            const constructorKey = Object.keys(telnyxModule).find(key => 
                typeof telnyxModule[key] === 'function' && key.includes('Telnyx')
            )
            if (constructorKey) {
                TelnyxRTC = telnyxModule[constructorKey]
                console.log(`📦 Using telnyxModule.${constructorKey}`)
            }
        }
        
        console.log('📦 TelnyxRTC constructor:', TelnyxRTC)
        console.log('📦 TelnyxRTC type:', typeof TelnyxRTC)
        
        if (typeof TelnyxRTC !== 'function') {
            console.error('❌ TelnyxRTC is not a function!')
            console.error('Module structure:', JSON.stringify(Object.keys(telnyxModule)))
            console.error('All module properties:', telnyxModule)
            throw new Error(`TelnyxRTC is not a constructor. Type: ${typeof TelnyxRTC}. Available keys: ${Object.keys(telnyxModule).join(', ')}`)
        }

        // Get credentials from selected connection
        console.log('🔐 Getting credentials...')
        const credentials = getCurrentConnectionCredentials()
        console.log('🔐 Credentials found:', credentials ? 'Yes' : 'No')
        console.log('🔐 Login:', credentials?.login)
        
        if (!credentials) {
            throw new Error('No valid credentials found for selected connection. Please ensure the SIP connection has login and password configured in the database.')
        }
        
        
        // Initialize WebRTC client according to Telnyx documentation
        console.log('🔧 Creating TelnyxRTC instance...')
        console.log('🔧 Config:', { login: credentials.login, audio: true, video: false, debug: false })
        
        try {
            webrtcClient = new TelnyxRTC({
                login: credentials.login,    
                password: credentials.password,
                audio: true,
                video: false,
                debug: true // Disable debug mode for production
            })
            console.log('✅ TelnyxRTC instance created successfully:', webrtcClient)
        } catch (constructorError) {
            console.error('❌ Failed to create TelnyxRTC instance:', constructorError)
            console.error('Constructor error details:', constructorError.message)
            throw new Error(`Failed to create TelnyxRTC: ${constructorError.message}`)
        }
        
        addTranscriptEntry('System', `WebRTC client created with login: ${credentials.login}`)

        // Set remote audio element
        console.log('🔊 Setting remote audio element...')
        webrtcClient.remoteElement = 'remoteMedia'
        console.log('🔊 Remote element set to: remoteMedia')

        // Event listeners
        console.log('🎧 Setting up event listeners...')
        
        webrtcClient.on('telnyx.ready', () => {
            console.log('✅ telnyx.ready event fired')
            webrtcStatus.value = 'ready'
            addTranscriptEntry('System', '✅ WebRTC client ready for calls')
        })

        webrtcClient.on('telnyx.error', (error) => {
            console.error('❌ telnyx.error event:', error)
            console.error('Error details:', error.message, error.code, error)
            lastError.value = error.message
            webrtcStatus.value = 'error'
            addTranscriptEntry('Error', `WebRTC error: ${error.message}`)
        })

        webrtcClient.on('telnyx.notification', (notification) => {
            console.log('📢 telnyx.notification:', notification)
            
            // Handle call updates according to official Telnyx documentation
            if (notification.type === 'callUpdate' && notification.call) {
                handleCallUpdate(notification.call)
            }
            
            // Handle incoming calls - official way according to docs
            if (notification.type === 'callUpdate' && notification.call.state === 'ringing') {
                // IMPORTANT: For incoming calls, remoteCallerNumber is the EXTERNAL caller
                const callerNumber = notification.call.options?.remoteCallerNumber || 
                                   notification.call.options?.callerNumber || 
                                   notification.call.params?.caller_id_number || 
                                   'Unknown'
                const destinationNumber = notification.call.options?.destinationNumber || 
                                        notification.call.params?.destination_number || 
                                        'Unknown'
                
                addTranscriptEntry('System', `📞 Incoming call from ${callerNumber} to ${destinationNumber}`)
                addTranscriptEntry('Debug', `Raw notification - Type: ${notification.type}, State: ${notification.call.state}`)
                addTranscriptEntry('Debug', `Remote Caller: ${notification.call.options?.remoteCallerNumber}`)
                addTranscriptEntry('Debug', `Caller: ${notification.call.options?.callerNumber}`)
                addTranscriptEntry('Debug', `Destination: ${notification.call.options?.destinationNumber}`)
                
                showCallNotification('📞 Incoming Call', `Call from ${callerNumber}`, 'info')
                handleIncomingCall(notification.call)
            }
            
            // Handle call recovery after page refresh
            if (notification.type === 'callRecovery' && notification.call) {
                addTranscriptEntry('System', '🔄 Recovering call after page refresh...')
                handleCallUpdate(notification.call)
            }
        })

        console.log('🔌 Attempting to connect to Telnyx...')
        await webrtcClient.connect()
        console.log('✅ WebRTC connect() call completed')

    } catch (error) {
        console.error('❌ WebRTC Initialization Error:', error)
        console.error('Error stack:', error.stack)
        console.error('Error message:', error.message)
        
        lastError.value = error.message
        webrtcStatus.value = 'error'
        
        addTranscriptEntry('Error', `WebRTC initialization failed: ${error.message}`)
        addTranscriptEntry('Error', `Full error: ${error.stack || error.toString()}`)
    }
}

// Handle incoming calls
const handleIncomingCall = (call) => {
    try {
        currentCall = call
        callStatus.value = 'ringing'
        isRinging.value = false
        isConnected.value = false
        isIncomingCall.value = true // Mark as incoming call
        callDirection.value = 'incoming'
        
        // Start playing ringing sound
        playRingingSound()
        
        // Extract caller information from the call.options object
        if (call && call.options) {
            // For incoming calls in Telnyx WebRTC:
            // - remoteCallerNumber: The EXTERNAL person calling you (THIS IS WHAT WE WANT!)
            // - callerNumber: Often your own number (confusing naming)
            // - destinationNumber: Your number that they called
            // - remoteCallerName: The caller's name if available
            
            // IMPORTANT: For incoming calls, prioritize remoteCallerNumber!
            const incomingCallerNumber = call.options.remoteCallerNumber || call.options.callerNumber
            const ourNumber = call.options.destinationNumber || call.options.callerNumber
            
            addTranscriptEntry('Debug', `📞 Incoming call - Remote caller: ${call.options.remoteCallerNumber}, Caller: ${call.options.callerNumber}, Destination: ${call.options.destinationNumber}`)
            
            // Set toNumber to the REMOTE caller (person calling us from outside)
            if (incomingCallerNumber) {
                toNumber.value = incomingCallerNumber
                addTranscriptEntry('Debug', `✅ Set incoming caller to: ${incomingCallerNumber}`)
            }
            
            // Set fromNumber to our number (the number they called)
            if (ourNumber) {
                fromNumber.value = ourNumber
                addTranscriptEntry('Debug', `✅ Set our number to: ${ourNumber}`)
            }
            
            // Log full call options for debugging
            addTranscriptEntry('Debug', `Full call options: ${JSON.stringify({
                callerNumber: call.options.callerNumber,
                destinationNumber: call.options.destinationNumber,
                remoteCallerNumber: call.options.remoteCallerNumber,
                remoteCallerName: call.options.remoteCallerName,
                telnyxSessionId: call.options.telnyxSessionId
            })}`)
        }
        
        // Fallback to call.params if options are not available
        if (call && call.params && !toNumber.value) {
            addTranscriptEntry('Debug', `Using fallback call.params - caller_id: ${call.params.caller_id_number}, dest: ${call.params.destination_number}`)
            
            if (call.params.caller_id_number) {
                toNumber.value = call.params.caller_id_number
            }
            if (call.params.destination_number && !fromNumber.value) {
                fromNumber.value = call.params.destination_number
            }
        }
        
        addTranscriptEntry('Call', `Incoming call received from ${toNumber.value || 'Unknown'} to ${fromNumber.value || 'Unknown'}`)
        addTranscriptEntry('Debug', `Call flags: incoming=${isIncomingCall.value}, ringing=${isRinging.value}, active=${isCallActive.value}`)
        
        // Set up call event listeners for incoming call
        if (currentCall && currentCall.on) {
            currentCall.on('stateChange', (state) => {
                handleCallUpdate(currentCall)
            })
            
            currentCall.on('error', (error) => {
                addTranscriptEntry('Error', `Incoming call error: ${error.message}`)
            })
        }
    } catch (error) {
        addTranscriptEntry('Error', `Failed to handle incoming call: ${error.message}`)
    }
}

// Handle call updates
const handleCallUpdate = (call) => {
    callStatus.value = call.state

    switch (call.state) {
        case 'new':
            addTranscriptEntry('Status', 'New call created')
            showCallNotification('📱 New Call', 'Call has been created', 'info')
            break
            
        case 'trying':
            isConnecting.value = true
            isConnected.value = false
            addTranscriptEntry('Status', 'Attempting to call...')
            showCallNotification('📞 Attempting Call', `Trying to call ${toNumber.value}...`, 'info')
            break
            
        case 'requesting':
            isConnecting.value = true
            isRinging.value = false
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
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Attempting to answer call...')
            showCallNotification('📞 Answering Call', 'Attempting to answer inbound call', 'info')
            break
            
        case 'early':
            isRinging.value = false
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
            endCall(true) // Skip hangup since call is already terminated
            break
            
        case 'destroy':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call has been destroyed')
            showCallNotification('🗑️ Call Destroyed', 'Call has been destroyed', 'error')
            endCall(true) // Skip hangup since call is already terminated
            break
            
        case 'purge':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call has been purged')
            showCallNotification('🧹 Call Purged', 'Call has been purged from system', 'error')
            endCall(true) // Skip hangup since call is already terminated
            break
            
        case 'failed':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Call failed to connect')
            showCallNotification('❌ Call Failed', `Failed to connect to ${toNumber.value}`, 'error')
            endCall(true) // Skip hangup since call failed
            break
            
        case 'busy':
            isRinging.value = false
            isConnected.value = false
            addTranscriptEntry('Status', 'Line is busy')
            showCallNotification('🚫 Line Busy', `${toNumber.value} is busy`, 'warning')
            endCall(true) // Skip hangup since line is busy
            break
            
        default:    
            addTranscriptEntry('Status', `Call state: ${call.state}`)
            break
    }
}

// Validate connection selection
const validateConnectionSelection = () => {
    if (!selectedConnection.value) {
        throw new Error('Please select a SIP connection first')
    }

    if (!selectedConnectionData.value) {
        throw new Error('Selected connection data not found')
    }

    if (!connectionPhoneNumbers.value || connectionPhoneNumbers.value.length === 0) {
        throw new Error('Selected connection has no phone numbers assigned')
    }

    if (!fromNumber.value) {
        throw new Error('Please select a phone number from the connection')
    }

    if (!toNumber.value) {
        throw new Error('Please enter a destination number')
    }

    return true
}

// Start Call
const startCall = async () => {
    try {
        if (!webrtcClient || webrtcStatus.value !== 'ready') {
            throw new Error('WebRTC client not ready')
        }

        // Validate connection selection
        validateConnectionSelection()
        
        isConnecting.value = true
        isIncomingCall.value = false // Mark as outgoing call
        callDirection.value = 'outgoing'
        callStatus.value = 'Initializing call...'
        addTranscriptEntry('Call', `Initiating outgoing call from ${fromNumber.value} to ${toNumber.value}`)
        addTranscriptEntry('System', `Using SIP connection: ${selectedConnectionData.value.name}`)
        
        // Create call according to official Telnyx documentation
        const callParams = {
            destinationNumber: toNumber.value,
            callerNumber: fromNumber.value,
            audio: true,
            video: false
        }
        
        addTranscriptEntry('Debug', `📞 Creating call with params: ${JSON.stringify(callParams)}`)
        currentCall = webrtcClient.newCall(callParams)
        
        // Set up call event listeners
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
        showCallNotification('❌ Call Failed', error.message, 'error')
        isConnecting.value = false
    }
}

// Call control functions
const toggleMute = () => {
    try {
        if (currentCall) {
            // Use official Telnyx call methods
            if (isMuted.value) {
                currentCall.unmuteAudio()
                isMuted.value = false
                addTranscriptEntry('Control', '🎤 Microphone unmuted')
                showCallNotification('🎤 Unmuted', 'Audio unmuted using Telnyx method', 'success')
            } else {
                currentCall.muteAudio()
                isMuted.value = true
                addTranscriptEntry('Control', '🔇 Microphone muted')
                showCallNotification('🔇 Muted', 'Audio muted using Telnyx method', 'info')
            }
        } else {
            addTranscriptEntry('Warning', 'No active call to mute/unmute')
        }
    } catch (error) {
        addTranscriptEntry('Error', `Mute operation failed: ${error.message}`)
    }
}

const answerCall = () => {  
    try {
        // Stop ringing sound
        stopRingingSound()
        
        if (currentCall) {
            currentCall.answer()
            isConnecting.value = false
            isRinging.value = false
            isCallActive.value = true
            isConnected.value = true
            
            addTranscriptEntry('Control', 'Incoming call answered')
            showCallNotification('✅ Call Answered', 'Incoming call has been answered', 'success')
            callStatus.value = 'answering'
            addTranscriptEntry('Debug', 'Answer call initiated, waiting for state change...')
        } else {
            const errorMsg = !currentCall ? 'No current call object' : 'Answer method not available on call object'
            addTranscriptEntry('Warning', `Cannot answer call: ${errorMsg}`)
        }
    } catch (error) {
        addTranscriptEntry('Error', `Failed to answer call: ${error.message}`)
    }
}

const refreshPhoneNumbers = () => {
    try {
        lastPhoneNumbersRefresh.value = new Date()
        emit('refreshPhoneNumbers')
    } catch (error) {
    }
}

// Debug function to simulate incoming call
const simulateIncomingCall = () => {
    try {
        // Simulate incoming call data
        const mockCall = {
            params: {
                caller_id_number: '+1234567890',
                destination_number: fromNumber.value || '+12037206619'
            },
            state: 'ringing',
            direction: 'inbound'
        }
        
        addTranscriptEntry('Debug', 'Simulating incoming call for testing')
        handleIncomingCall(mockCall)
        
        // Also simulate the current call for testing
        currentCall = {
            answer: () => {
                addTranscriptEntry('Debug', 'Mock call answered')
                callStatus.value = 'active'
                isCallActive.value = true
                isRinging.value = false
            },
            reject: () => {
                addTranscriptEntry('Debug', 'Mock call rejected')
                resetCallStates()
            },
            hangup: () => {
                addTranscriptEntry('Debug', 'Mock call hung up')
                resetCallStates()
            }
        }
        
    } catch (error) {
        addTranscriptEntry('Error', `Failed to simulate incoming call: ${error.message}`)
    }
}

const rejectCall = () => {
    try {
        // Stop ringing sound
        stopRingingSound()
        
       if (currentCall && currentCall.gotAnswer == false) {
            // Check if call is still in a valid state for hangup
            const validStatesForHangup = ['new', 'trying', 'ringing', 'early']
            if (validStatesForHangup.includes(currentCall.state)) {
                addTranscriptEntry('Debug', `Rejecting call in state: ${currentCall.state}`)
                currentCall.hangup();
                addTranscriptEntry('Control', 'Incoming call rejected')
                showCallNotification('❌ Call Rejected', 'Incoming call has been rejected', 'info')
            } else {
                addTranscriptEntry('Debug', `Cannot reject call in state: ${currentCall.state}`)
                addTranscriptEntry('Control', 'Call cannot be rejected in current state')
            }
            resetCallStates()
        } else {
            addTranscriptEntry('Warning', 'Reject method not available')
        }
    } catch (error) {
        addTranscriptEntry('Error', `Failed to reject call: ${error.message}`)
        resetCallStates()
    }
}

const toggleHold = async () => {
    try {
        if (!currentCall) {
            addTranscriptEntry('Warning', 'No active call to hold/unhold')
            return
        }
        
        if (isOnHold.value) {
            // Resume call using official Telnyx method
            await currentCall.unhold()
            isOnHold.value = false
            addTranscriptEntry('Control', '▶️ Call resumed from hold')
            showCallNotification('▶️ Call Resumed', 'Call resumed using Telnyx method', 'success')
        } else {
            // Put call on hold using official Telnyx method
            await currentCall.hold()
            isOnHold.value = true
            addTranscriptEntry('Control', '⏸️ Call put on hold')
            showCallNotification('⏸️ Call On Hold', 'Call held using Telnyx method', 'info')
        }
    } catch (error) {
        addTranscriptEntry('Error', `Hold operation failed: ${error.message}`)
    }
}

const endCall = (skipHangup = false) => {
    try {
        // Stop ringing sound if playing
        stopRingingSound()
        
        if (currentCall ) {
            // Check if call is still in a valid state for hangup
            const validStatesForHangup = ['new', 'trying', 'ringing', 'early', 'active', 'held']
            if (validStatesForHangup.includes(currentCall.state)) {
                addTranscriptEntry('Debug', `Attempting to hangup call in state: ${currentCall.state}`)
                currentCall.hangup()
            } else {
                addTranscriptEntry('Debug', `Skipping hangup for call in state: ${currentCall.state}`)
            }
        } else if (skipHangup) {
            addTranscriptEntry('Debug', 'Skipping hangup as requested (call already terminated)')
        }
        
        // Clear call reference and timer
        currentCall = null
        clearInterval(callTimer)
    
        // Reset all call states
        isCallActive.value = false
        isRinging.value = false
        isConnected.value = false
        isOnHold.value = false
        isLoudspeakerOn.value = false
        isIncomingCall.value = false
        callDirection.value = ''
        isConnecting.value = false
        callStatus.value = ''
        callDuration.value = '00:00'
        transcriptionStatus.value = ''
        
        // Reset call states for next call
        addTranscriptEntry('System', 'Call session ended, ready for next call')
    } catch (error) {
        addTranscriptEntry('Error', `Error ending call: ${error.message}`)
        lastError.value = error.message
        
        // Still reset states even if hangup failed
        currentCall = null
        clearInterval(callTimer)
        isCallActive.value = false
        isRinging.value = false
        isConnected.value = false
        isOnHold.value = false
        isLoudspeakerOn.value = false
        isIncomingCall.value = false
        callDirection.value = ''
        isConnecting.value = false
        callStatus.value = ''
        callDuration.value = '00:00'
        transcriptionStatus.value = ''
    }
}

// Toggle transcription for the current call
const toggleTranscription = async () => {
    try {
        if (!currentCall || !currentCall.options.telnyxSessionId) {
            addTranscriptEntry('System', 'No active call to transcribe')
            return
        }
        // Fetch call details from database to get call_control_id
        const callResponse = await fetch(`/api/calls/${currentCall.options.telnyxSessionId}`)
        
        const callData = await callResponse.json()
        
        if (!callData.success || !callData.data.call_control_id) {
            addTranscriptEntry('System', 'Call control ID not found for this call')
            return
        }

        const callControlId = callData.data.call_control_id

        if (transcriptionStatus.value === 'started') {
            // Stop transcription
            const response = await fetch('/api/transcription/stop', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    call_control_id: callControlId
                })
            })

            const result = await response.json()
            
            if (result.success) {
                transcriptionStatus.value = 'stopped'
                addTranscriptEntry('System', 'Transcription stopped')
            } else {
                addTranscriptEntry('System', 'Failed to stop transcription: ' + result.message)
            }
        } else {
            // Start transcription
            const response = await fetch('/api/transcription/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    call_control_id: callControlId,
                    call_id: currentCall.options.telnyxSessionId,
                    language: 'en',
                    transcription_engine: 'B'
                })
            })

            const result = await response.json()
            
            if (result.success) {
                transcriptionStatus.value = 'started'
                addTranscriptEntry('System', 'Transcription started')
            } else {
                addTranscriptEntry('System', 'Failed to start transcription: ' + result.message)
            }
        }
    } catch (error) {
        addTranscriptEntry('System', 'Transcription error: ' + error.message)
    }
}

// Reset all call states
const resetCallStates = () => {
    isCallActive.value = false
    isRinging.value = false
    isConnected.value = false
    isOnHold.value = false
    isIncomingCall.value = false
    callDirection.value = ''
    callStatus.value = ''
    callDuration.value = '00:00'
    transcriptionStatus.value = ''
}

const disconnectCall = () => {
    try {
        // Stop ringing sound
        stopRingingSound()
        
        addTranscriptEntry('Control', 'Call disconnected by user')
        showCallNotification('🔌 Call Disconnected', 'Call has been disconnected', 'info')

        if (webrtcClient) {
            webrtcClient.disconnect()
            webrtcStatus.value = 'disconnected'
        }

        endCall()

    } catch (error) {
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
    loadConnections()
    
    // Listen for real-time transcription updates
    if (window.Echo) {
        
        window.Echo.channel('call-transcription')
            .listen('.transcription.updated', (e) => {
                console.log('Received transcription update:', e)
                
                // Update transcription data
                realtimeTranscript.value = e.transcript_text || ''
                transcriptionConfidence.value = e.confidence
                realtimeTranscriptionStatus.value = e.status
                currentCallControlId.value = e.call_control_id
                
                // Add to transcript entries
                if (e.latest_transcript) {
                    const confidenceText = e.confidence ? ` (${Math.round(e.confidence * 100)}%)` : ''
                    const finalText = e.is_final ? ' [FINAL]' : ' [INTERIM]'
                    addTranscriptEntry('Transcription', `${e.latest_transcript}${confidenceText}`)
                }
            })

        // Listen for call status updates
        window.Echo.channel('call-status')
            .listen('.call.status.updated', (e) => {
                console.log('Received call status update:', e)
                
                // Update call status in UI
                if (currentCall.value && currentCall.value.options.telnyxSessionId === e.call_session_id) {
                    // Update current call status
                    currentCall.value.status = e.status
                    
                    // Add status update to transcript
                    addTranscriptEntry('System', `Call status: ${e.status} (${e.event_type})`)
                    
                    // Handle specific status changes
                    if (e.status === 'ended' || e.status === 'failed') {
                        // Call ended - cleanup
                        setTimeout(() => {
                            endCall()
                        }, 1000)
                    }
                }
            })
    } else {
        console.warn('Laravel Echo not available for real-time updates')
    }
    
    // Initialize sample participants for demo
    participants.value = [
        {
            id: 1,
            name: 'John Doe',
            phone: '+1 (555) 123-4567',
            status: 'Connected',
            isMuted: false,
            isSpeaking: false,
            isVideoOff: false,
            connectionQuality: 3,
            avatar: null
        }
    ]
    
    // Generate a sample call ID
    callId.value = 'CALL-' + Math.random().toString(36).substr(2, 9).toUpperCase()
})

// Professional Call Interface Methods
const handleParticipantMute = (participant) => {
    addTranscriptEntry('System', `Muted participant: ${participant.name}`)
}

const handleParticipantRemove = (participant) => {
    participants.value = participants.value.filter(p => p.id !== participant.id)
    addTranscriptEntry('System', `Removed participant: ${participant.name}`)
}

const toggleVideo = () => {
    addTranscriptEntry('System', 'Video toggle requested')
}

const toggleShareScreen = () => {
    addTranscriptEntry('System', 'Screen share toggle requested')
}

const toggleParticipants = () => {
    addTranscriptEntry('System', 'Participants panel toggle requested')
}

const toggleChat = () => {
    addTranscriptEntry('System', 'Chat panel toggle requested')
}

const toggleMoreOptions = () => {
    addTranscriptEntry('System', 'More options menu requested')
}

onUnmounted(() => {
    // Stop ringing sound on component unmount
    stopRingingSound()
    
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


</style> 