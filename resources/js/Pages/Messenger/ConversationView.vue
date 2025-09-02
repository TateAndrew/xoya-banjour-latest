<template>
  <div class="flex-1 flex flex-col">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
            <span class="text-white font-medium">
              {{ conversation.contact?.initials || '?' }}
            </span>
          </div>
          <div>
            <h2 class="text-lg font-medium text-gray-900">
              {{ conversation.contact?.display_name || conversation.contact?.phone_e164 }}
            </h2>
            <p class="text-sm text-gray-500">{{ conversation.contact?.phone_e164 }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="markAsRead"
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            Mark as Read
          </button>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <div 
      ref="messagesContainer"
      class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50"
    >
      <div v-if="isLoadingMessages" class="text-center text-gray-500 py-8">
        <div class="inline-flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading messages...
        </div>
      </div>
      <div v-else-if="messages.length === 0" class="text-center text-gray-500 py-8">
        <p>No messages yet</p>
        <p class="text-sm mt-2">Start the conversation by sending a message</p>
      </div>
      
      <div
        v-for="message in messages"
        :key="message.id"
        :class="[
          'flex',
          message.direction === 'outbound' ? 'justify-end' : 'justify-start'
        ]"
      >
        <div
          :class="[
            'max-w-xs lg:max-w-md px-4 py-2 rounded-lg shadow-sm',
            message.direction === 'outbound'
              ? 'bg-blue-500 text-white'
              : 'bg-white text-gray-900 border border-gray-200'
          ]"
        >
          <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
          <div class="flex items-center justify-between mt-2">
            <p class="text-xs opacity-75">
              {{ formatTime(message.created_at) }}
            </p>
            <div v-if="message.direction === 'outbound'" class="flex items-center space-x-1">
              <span v-if="message.status === 'queued'" class="text-xs opacity-75">⏳</span>
              <span v-else-if="message.status === 'sending'" class="text-xs opacity-75">📤</span>
              <span v-else-if="message.status === 'sent'" class="text-xs opacity-75">✓</span>
              <span v-else-if="message.status === 'delivered'" class="text-xs opacity-75">✓✓</span>
              <span v-else-if="message.status === 'failed'" class="text-xs opacity-75 text-red-300">✗</span>
            </div>
          </div>
        </div>
      </div>
      
      <div v-if="isTyping" class="flex justify-start">
        <div class="bg-white text-gray-900 border border-gray-200 px-4 py-2 rounded-lg shadow-sm">
          <div class="flex space-x-1">
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Message Input -->
    <div class="bg-white border-t border-gray-200 p-4">
      <!-- Phone Number Selector -->
      <div v-if="userPhoneNumbers?.length > 1" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Send from:</label>
        <select
          v-model="selectedPhoneNumber"
          class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option v-for="phoneNumber in userPhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
            {{ phoneNumber.phone_number }} 
            <span v-if="phoneNumber.messaging_profile">({{ phoneNumber.messaging_profile.name }})</span>
          </option>
        </select>
      </div>
      
      <form @submit.prevent="sendMessage" class="flex space-x-4">
        <div class="flex-1 relative">
          <textarea
            v-model="newMessage"
            @input="handleTyping"
            @keydown.ctrl.enter="sendMessage"
            rows="1"
            placeholder="Type your message... (Ctrl+Enter to send)"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
            style="min-height: 44px; max-height: 120px;"
          ></textarea>
          <div class="absolute bottom-2 right-2 text-xs text-gray-400">
            {{ newMessage.length }}/1600
          </div>
        </div>
        <button
          type="submit"
          :disabled="!newMessage.trim() || isSending || !selectedPhoneNumber"
          class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isSending">Sending...</span>
          <span v-else>Send</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'

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
const messagesContainer = ref(null)
const typingTimeout = ref(null)
const selectedPhoneNumber = ref(null)

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
      await loadMessages()
      emit('conversation-updated')
    } else {
      console.error('Failed to send message')
    }
  } catch (error) {
    console.error('Error sending message:', error)
  } finally {
    isSending.value = false
  }
}

const loadMessages = async () => {
  if (!props.conversation?.id) return
  
  try {
    isLoadingMessages.value = true
    const response = await fetch(`/messenger/conversation/${props.conversation.id}/messages`)
    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages || []
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Error loading messages:', error)
  } finally {
    isLoadingMessages.value = false
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
  isTyping.value = true
  clearTimeout(typingTimeout.value)
  
  typingTimeout.value = setTimeout(() => {
    isTyping.value = false
  }, 1000)
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  return new Date(timestamp).toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

// Auto-resize textarea
const autoResize = () => {
  const textarea = document.querySelector('textarea')
  if (textarea) {
    textarea.style.height = 'auto'
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px'
  }
}

watch(newMessage, autoResize)

// Watch for conversation changes
watch(() => props.conversation, (newConversation, oldConversation) => {
  if (newConversation && newConversation.id !== oldConversation?.id) {
    // Clear messages immediately for faster UI feedback
    messages.value = []
    // Load new messages
    loadMessages()
  }
}, { immediate: true })

onMounted(() => {
  // Initialize with first available phone number
  if (props.userPhoneNumbers?.length > 0) {
    selectedPhoneNumber.value = props.userPhoneNumbers[0].id
  }
})
</script>
