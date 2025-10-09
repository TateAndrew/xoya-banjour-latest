<template>
  <div class="flex flex-col h-full min-h-0 bg-gradient-to-b from-gray-50 to-white">
    <!-- Professional Header -->
    <div class="bg-white border-b border-gray-200 shadow-sm flex-shrink-0">
      <div class="px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4 flex-1 min-w-0">
            <!-- Avatar with online indicator -->
            <div class="relative flex-shrink-0">
              <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md ring-2 ring-white">
                <span class="text-white font-semibold text-lg">
                  {{ conversation.contact?.initials || '?' }}
                </span>
              </div>
              <div class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-400 border-2 border-white rounded-full"></div>
            </div>
            
            <!-- Contact Info -->
            <div class="flex-1 min-w-0">
              <h2 class="text-lg font-semibold text-gray-900 truncate">
                {{ conversation.contact?.name || conversation.contact?.display_name || 'Unknown Contact' }}
              </h2>
              <div class="flex items-center space-x-2 text-sm text-gray-500">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                <span class="truncate">{{ conversation.contact?.phone_e164 || 'No phone number' }}</span>
              </div>
            </div>
          </div>
          
          <!-- Header Actions -->
          <div class="flex items-center space-x-2 ml-4">
            <button
              @click="markAsRead"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span class="hidden sm:inline">Mark Read</span>
            </button>
            
            <!-- More Options -->
            <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages Area -->
    <div 
      ref="messagesContainer"
      class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4 bg-gray-50 custom-scrollbar"
      style="background-image: radial-gradient(circle at 1px 1px, rgba(0,0,0,0.03) 1px, transparent 0); background-size: 20px 20px; min-height: 0;"
      @scroll="handleScroll"
    >
      <!-- Load More Button -->
      <div v-if="hasMoreMessages && !isLoadingMessages" class="flex justify-center pb-4">
        <button
          @click="loadMoreMessages"
          :disabled="isLoadingMore"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 border border-blue-300 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="!isLoadingMore" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
          </svg>
          <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isLoadingMore ? 'Loading...' : 'Load More Messages' }}
        </button>
      </div>
      <!-- Loading State -->
      <div v-if="isLoadingMessages" class="flex justify-center items-center py-12">
        <div class="text-center">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-4">
            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-700">Loading messages...</p>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="messages.length === 0" class="flex justify-center items-center py-12">
        <div class="text-center max-w-sm">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 mb-4">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No messages yet</h3>
          <p class="text-sm text-gray-500">Start the conversation by sending a message below</p>
        </div>
      </div>
      
      <!-- Messages -->
      <div
        v-for="(message, index) in messages"
        :key="message.id"
        :class="[
          'flex',
          message.direction === 'outbound' ? 'justify-end' : 'justify-start',
          'animate-fadeIn'
        ]"
      >
        <div
          :class="[
            'max-w-[85%] sm:max-w-md lg:max-w-lg xl:max-w-xl',
            'transform transition-all duration-200 hover:scale-[1.02]'
          ]"
        >
          <!-- Message Bubble -->
          <div
            :class="[
              'relative px-4 py-3 rounded-2xl shadow-sm',
              message.direction === 'outbound'
                ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-br-sm'
                : 'bg-white text-gray-900 border border-gray-200 rounded-bl-sm'
            ]"
          >
            <!-- Message Content -->
            <p class="text-sm sm:text-base leading-relaxed whitespace-pre-wrap break-words">
              {{ message.content }}
            </p>
            
            <!-- Message Footer -->
            <div class="flex items-center justify-between mt-2 space-x-3">
              <span :class="[
                'text-xs font-medium',
                message.direction === 'outbound' ? 'text-blue-100' : 'text-gray-500'
              ]">
                {{ formatTime(message.created_at) }}
              </span>
              
              <!-- Status Indicators for Outbound -->
              <div v-if="message.direction === 'outbound'" class="flex items-center">
                <span v-if="message.status === 'queued'" class="text-xs" title="Queued">
                  <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </span>
                <span v-else-if="message.status === 'sending'" class="text-xs" title="Sending">
                  <svg class="w-4 h-4 text-blue-200 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                  </svg>
                </span>
                <span v-else-if="message.status === 'sent'" class="text-xs" title="Sent">
                  <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                </span>
                <span v-else-if="message.status === 'delivered'" class="text-xs" title="Delivered">
                  <svg class="w-4 h-4 text-blue-200" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    <path d="M19.59 7L12 14.59 10.41 13 9 14.41l3 3 9-9L19.59 7z"/>
                  </svg>
                </span>
                <span v-else-if="message.status === 'failed'" class="text-xs" title="Failed">
                  <svg class="w-4 h-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </span>
              </div>
            </div>
            
            <!-- Message Tail -->
            <div 
              :class="[
                'absolute bottom-0 w-0 h-0',
                message.direction === 'outbound'
                  ? 'right-0 border-l-8 border-l-transparent border-t-8 border-t-blue-600'
                  : 'left-0 border-r-8 border-r-transparent border-t-8 border-t-white'
              ]"
              style="bottom: 0px;"
            ></div>
          </div>
        </div>
      </div>
      
      <!-- Typing Indicator -->
      <div v-if="isTyping" class="flex justify-start animate-fadeIn">
        <div class="bg-white text-gray-900 border border-gray-200 px-5 py-3 rounded-2xl rounded-bl-sm shadow-sm">
          <div class="flex space-x-1.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Professional Input Area -->
    <div class="bg-white border-t border-gray-200 shadow-lg flex-shrink-0">
      <div class="px-4 sm:px-6 lg:px-8 py-4">
        <!-- Phone Number Selector -->
        <div v-if="userPhoneNumbers?.length > 1" class="mb-3">
          <div class="relative">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5 uppercase tracking-wide">
              Send From
            </label>
            <div class="relative">
              <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
              </svg>
              <select
                v-model="selectedPhoneNumber"
                class="block w-full pl-10 pr-10 py-2.5 text-sm border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
              >
                <option v-for="phoneNumber in userPhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
                  {{ phoneNumber.phone_number }} 
                  <span v-if="phoneNumber.messaging_profile">• {{ phoneNumber.messaging_profile.name }}</span>
                </option>
              </select>
            </div>
          </div>
        </div>
        
        <!-- Message Input Form -->
        <form @submit.prevent="sendMessage" class="flex flex-col sm:flex-row gap-3">
          <div class="flex-1 relative">
            <div class="relative">
              <textarea
                v-model="newMessage"
                @input="handleTyping"
                @keydown.ctrl.enter="sendMessage"
                ref="messageInput"
                rows="1"
                placeholder="Type your message... (Ctrl+Enter to send)"
                class="w-full border-2 border-gray-300 rounded-xl px-4 py-3 pr-16 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all duration-200 placeholder-gray-400"
                style="min-height: 48px; max-height: 120px;"
              ></textarea>
              
              <!-- Character Counter -->
              <div class="absolute bottom-2 right-2 flex items-center space-x-2">
                <span 
                  :class="[
                    'text-xs font-medium transition-colors duration-200',
                    newMessage.length > 1500 ? 'text-red-500' : 'text-gray-400'
                  ]"
                >
                  {{ newMessage.length }}/1600
                </span>
              </div>
            </div>
          </div>
          
          <!-- Send Button -->
          <button
            type="submit"
            :disabled="!newMessage.trim() || isSending || !selectedPhoneNumber"
            class="inline-flex items-center justify-center px-6 py-3 sm:py-0 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-medium rounded-xl hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-blue-500 disabled:hover:to-blue-600 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 active:scale-95"
          >
            <svg 
              v-if="!isSending" 
              class="w-5 h-5 sm:mr-2" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
            <svg 
              v-else 
              class="animate-spin w-5 h-5 sm:mr-2" 
              fill="none" 
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="hidden sm:inline">{{ isSending ? 'Sending...' : 'Send' }}</span>
          </button>
        </form>
        
        <!-- Helper Text -->
        <p class="mt-2 text-xs text-gray-500 flex items-center">
          <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Press <kbd class="px-1.5 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">Enter</kbd> to send
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'

const props = defineProps({
  conversation: Object,
  userPhoneNumbers: Array
})

const emit = defineEmits(['conversation-updated'])

const messages = ref([])
const newMessage = ref('')
const isSending = ref(false)
const isTyping = ref(false)
const isLoadingMessages = ref(false)
const isLoadingMore = ref(false)
const messagesContainer = ref(null)
const messageInput = ref(null)
const typingTimeout = ref(null)
const selectedPhoneNumber = ref(null)
const currentPage = ref(1)
const hasMoreMessages = ref(false)
const perPage = 20
let echoChannel = null
let isInitialLoad = true

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value || !selectedPhoneNumber.value) return

  const content = newMessage.value.trim()
  isSending.value = true

  try {
    const response = await fetch('/messenger/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        contact_id: props.conversation.contact.id,
        content: content,
        from_phone_number_id: selectedPhoneNumber.value
      })
    })

    if (response.ok) {
      newMessage.value = ''
      await loadMessages(true)
      emit('conversation-updated')
      // Reset textarea height
      if (messageInput.value) {
        messageInput.value.style.height = 'auto'
      }
      // Scroll to bottom after sending
      await nextTick()
      scrollToBottom('smooth')
    } else {
      console.error('Failed to send message')
    }
  } catch (error) {
    console.error('Error sending message:', error)
  } finally {
    isSending.value = false
  }
}

const loadMessages = async (scrollToBottomAfter = true) => {
  if (!props.conversation?.id) return
  
  try {
    isLoadingMessages.value = true
    const response = await fetch(`/messenger/conversation/${props.conversation.id}/messages?page=1&per_page=${perPage}`)
    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages || []
      currentPage.value = 1
      hasMoreMessages.value = data.has_more || false
      await nextTick()
      if (scrollToBottomAfter) {
        scrollToBottom()
      }
    }
  } catch (error) {
    console.error('Error loading messages:', error)
  } finally {
    isLoadingMessages.value = false
  }
}

const loadMoreMessages = async () => {
  if (!props.conversation?.id || isLoadingMore.value) return
  
  try {
    isLoadingMore.value = true
    const nextPage = currentPage.value + 1
    
    // Store current scroll position
    const container = messagesContainer.value
    const scrollHeightBefore = container.scrollHeight
    const scrollTopBefore = container.scrollTop
    
    const response = await fetch(`/messenger/conversation/${props.conversation.id}/messages?page=${nextPage}&per_page=${perPage}`)
    if (response.ok) {
      const data = await response.json()
      const olderMessages = data.messages || []
      
      // Prepend older messages to the beginning
      messages.value = [...olderMessages, ...messages.value]
      currentPage.value = nextPage
      hasMoreMessages.value = data.has_more || false
      
      await nextTick()
      
      // Restore scroll position (maintain user's view)
      const scrollHeightAfter = container.scrollHeight
      container.scrollTop = scrollTopBefore + (scrollHeightAfter - scrollHeightBefore)
    }
  } catch (error) {
    console.error('Error loading more messages:', error)
  } finally {
    isLoadingMore.value = false
  }
}

const markAsRead = async () => {
  try {
    await fetch(`/messenger/conversation/${props.conversation.id}/read`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    emit('conversation-updated')
  } catch (error) {
    console.error('Error marking as read:', error)
  }
}

const handleTyping = () => {
  autoResize()
  isTyping.value = true
  clearTimeout(typingTimeout.value)
  
  typingTimeout.value = setTimeout(() => {
    isTyping.value = false
  }, 1000)
}

const scrollToBottom = (behavior = 'smooth') => {
  if (messagesContainer.value) {
    requestAnimationFrame(() => {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: behavior
      })
    })
  }
}

const handleScroll = () => {
  // Optional: Auto-load more when scrolling to top
  if (messagesContainer.value && hasMoreMessages.value && !isLoadingMore.value) {
    const { scrollTop } = messagesContainer.value
    if (scrollTop < 100) {
      // Uncomment to enable auto-load on scroll to top
      // loadMoreMessages()
    }
  }
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()
  
  if (isToday) {
    return date.toLocaleTimeString([], { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  } else {
    return date.toLocaleDateString([], { 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit', 
      minute: '2-digit' 
    })
  }
}

// Auto-resize textarea
const autoResize = () => {
  if (messageInput.value) {
    messageInput.value.style.height = 'auto'
    messageInput.value.style.height = Math.min(messageInput.value.scrollHeight, 120) + 'px'
  }
}

watch(newMessage, autoResize)

// Setup real-time messaging for current conversation
const setupConversationListener = (conversationId) => {
  if (!window.Echo || !conversationId) return
  
  // Leave previous channel if exists
  if (echoChannel) {
    window.Echo.leave(echoChannel)
  }
  
  const channelName = `messenger.${conversationId}`
  echoChannel = channelName
  
  console.log('Listening on conversation channel:', channelName)
  
  // Listen for new messages on this conversation
  window.Echo.channel(channelName)
    .listen('.message.sent', async (event) => {
      console.log('Message sent in conversation:', event)
      // Check if this message belongs to current conversation
      if (event.conversation?.id === conversationId) {
        // Reload messages to show the new one
        await loadMessages(false)
        emit('conversation-updated')
        // Scroll to bottom to show new message
        await nextTick()
        scrollToBottom('smooth')
      }
    })
}

// Also listen to user channel for incoming messages
const setupUserListener = () => {
  if (!window.Echo) return
  
  const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content')
  if (!userId) return
  
  console.log('ConversationView listening on user channel:', `user.${userId}`)
  
  window.Echo.channel(`user.${userId}`)
    .listen('.message.received', async (event) => {
      console.log('Message received for user:', event)
      // Check if this message is for the current conversation
      if (event.conversation?.id === props.conversation?.id) {
        // Reload messages to show the new one
        await loadMessages(false)
        emit('conversation-updated')
        // Scroll to bottom to show new message
        await nextTick()
        scrollToBottom('smooth')
      }
    })
}

// Cleanup Echo listener
const cleanupConversationListener = () => {
  if (echoChannel && window.Echo) {
    window.Echo.leave(echoChannel)
    echoChannel = null
  }
}

// Watch for conversation changes
watch(() => props.conversation, (newConversation, oldConversation) => {
  if (newConversation && newConversation.id !== oldConversation?.id) {
    // Clear messages immediately for faster UI feedback
    messages.value = []
    currentPage.value = 1
    hasMoreMessages.value = false
    // Load new messages and scroll to bottom
    loadMessages(true)
    // Setup listener for this conversation
    setupConversationListener(newConversation.id)
  }
}, { immediate: true })

onMounted(() => {
  // Initialize with first available phone number
  if (props.userPhoneNumbers?.length > 0) {
    selectedPhoneNumber.value = props.userPhoneNumbers[0].id
  }
  
  // Setup listener for current conversation
  if (props.conversation?.id) {
    setupConversationListener(props.conversation.id)
  }
  
  // Setup user channel listener for incoming messages
  setupUserListener()
})

onUnmounted(() => {
  cleanupConversationListener()
})
</script>

<style scoped>
/* Custom Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 10px;
  transition: background 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Firefox scrollbar */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 #f1f5f9;
  scroll-behavior: smooth;
}

/* Smooth transitions for all interactive elements */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Message bubble hover effect */
.hover\:scale-\[1\.02\]:hover {
  transform: scale(1.02);
}
</style>

