<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="w-80 bg-white border-r border-gray-200">
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between mb-2">
          <h1 class="text-xl font-semibold text-gray-900">SMS Messenger</h1>
          <button
            @click="loadConversations()"
            class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1"
            title="Refresh conversations"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </button>
        </div>
        <div class="mt-2 space-y-2">
          <button
            @click="showNewContactModal = true"
            class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            New Contact
          </button>
          <button
            @click="showNewConversationModal = true"
            class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            New Conversation
          </button>
        </div>
      </div>
      
      <!-- Contact List -->
      <div class="overflow-y-auto h-full">
        <div
          v-for="conversation in conversations"
          :key="conversation.id"
          @click="selectConversation(conversation)"
          :class="[
            'p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors',
            selectedConversation?.id === conversation.id ? 'bg-blue-50 border-blue-200' : ''
          ]"
        >
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-white font-medium text-sm">
                {{ conversation.contact?.initials || '?' }}
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ conversation.contact?.display_name || conversation.contact?.phone_e164 || 'Unknown' }}
              </p>
              <p class="text-sm text-gray-500 truncate">
                {{ conversation.last_message ? conversation.last_message.short_content : 'No messages yet' }}
              </p>
              <p class="text-xs text-gray-400 mt-1">
                {{ formatTime(conversation.last_message_at) }}
              </p>
            </div>
            <div v-if="conversation.unread_count > 0" class="ml-2 flex-shrink-0">
              <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                {{ conversation.unread_count }}
              </span>
            </div>
          </div>
        </div>
        
        <div v-if="conversations.length === 0" class="p-8 text-center text-gray-500">
          <p>No conversations yet</p>
          <p class="text-sm mt-2">Start by adding a contact and sending a message</p>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
      <div v-if="!hasPhoneNumbers" class="flex-1 flex items-center justify-center">
        <div class="text-center max-w-md mx-auto p-8">
          <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No Phone Numbers Available</h3>
          <p class="text-gray-500 mb-4">You need to purchase a phone number and assign it to a messaging profile before you can send SMS messages.</p>
          <div class="space-y-2">
            <a href="/phone-numbers/purchase" class="inline-block bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
              Purchase Phone Number
            </a>
            <br>
            <a href="/messaging-profiles" class="inline-block text-blue-600 hover:text-blue-800 text-sm">
              Manage Messaging Profiles
            </a>
          </div>
        </div>
      </div>
      <div v-else-if="selectedConversation" class="flex-1 flex flex-col">
        <ConversationView 
          :key="selectedConversation?.id"
          :conversation="selectedConversation"
          :userPhoneNumbers="userPhoneNumbers"
          @conversation-updated="refreshConversations"
        />
      </div>
      <div v-else class="flex-1 flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900">Select a conversation</h3>
          <p class="text-gray-500">Choose a contact to start messaging</p>
        </div>
      </div>
    </div>

    <!-- New Contact Modal -->
    <div v-if="showNewContactModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Contact</h3>
          <form @submit.prevent="createContact">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
              <input
                v-model="newContact.name"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Contact name"
                required
              />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input
                v-model="newContact.phone_e164"
                type="tel"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="+15551234567"
                required
              />
            </div>
            <div class="flex space-x-3">
              <button
                type="button"
                @click="showNewContactModal = false"
                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="flex-1 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Add Contact
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- New Conversation Modal -->
    <div v-if="showNewConversationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Start New Conversation</h3>
          <form @submit.prevent="startNewConversation">
            <!-- Contact Selection Mode Toggle -->
            <div class="mb-4">
              <div class="flex space-x-4 mb-2">
                <label class="flex items-center">
                  <input
                    type="radio"
                    value="existing"
                    v-model="newConversation.contactMode"
                    class="mr-2"
                  />
                  <span class="text-sm text-gray-700">Select existing contact</span>
                  <span class="ml-1 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    ✓ Safe
                  </span>
                </label>
                <label class="flex items-center">
                  <input
                    type="radio"
                    value="new"
                    v-model="newConversation.contactMode"
                    class="mr-2"
                  />
                  <span class="text-sm text-gray-700">Create new contact</span>
                </label>
              </div>
              <p class="text-xs text-gray-500">
                💡 Selecting existing contacts prevents duplicate entries and ensures safe messaging to verified contacts.
              </p>
            </div>

            <!-- Existing Contact Selection -->
            <div v-if="newConversation.contactMode === 'existing'" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Select Contact</label>
              <div class="relative">
                <input
                  v-model="contactSearchQuery"
                  @input="searchContacts"
                  @focus="showContactDropdown = true"
                  type="text"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Search contacts..."
                />
                
                <!-- Contact Dropdown -->
                <div v-if="showContactDropdown && filteredContacts.length > 0" 
                     class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                  <div
                    v-for="contact in filteredContacts"
                    :key="contact.id"
                    @click="selectExistingContact(contact)"
                    class="px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
                  >
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="font-medium text-gray-900">{{ contact.name || 'Unknown' }}</div>
                        <div class="text-sm text-gray-500">{{ contact.phone_e164 }}</div>
                      </div>
                      <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          ✓ Safe
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- No contacts found -->
                <div v-if="showContactDropdown && contactSearchQuery && filteredContacts.length === 0"
                     class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-3 text-sm text-gray-500 text-center">
                  No contacts found
                </div>
              </div>
              
              <!-- Selected Contact Display -->
              <div v-if="selectedExistingContact" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-medium text-blue-900">{{ selectedExistingContact.name || 'Unknown' }}</div>
                    <div class="text-sm text-blue-700">{{ selectedExistingContact.phone_e164 }}</div>
                  </div>
                  <button
                    @click="clearSelectedContact"
                    type="button"
                    class="text-blue-500 hover:text-blue-700"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- New Contact Fields -->
            <div v-if="newConversation.contactMode === 'new'">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                <input
                  v-model="newConversation.name"
                  type="text"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Contact name"
                  required
                />
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input
                  v-model="newConversation.phone_e164"
                  type="tel"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="+15551234567"
                  required
                />
              </div>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">First Message</label>
              <textarea
                v-model="newConversation.message"
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Type your first message..."
                required
              ></textarea>
            </div>
            <div v-if="userPhoneNumbers?.length > 1" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Send from:</label>
              <select
                v-model="newConversation.from_phone_number_id"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="">Select phone number</option>
                <option v-for="phoneNumber in userPhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
                  {{ phoneNumber.phone_number }}
                  <span v-if="phoneNumber.messaging_profile">({{ phoneNumber.messaging_profile.name }})</span>
                </option>
              </select>
            </div>
            <div class="flex space-x-3">
              <button
                type="button"
                @click="showNewConversationModal = false"
                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isCreatingConversation || !isFormValid"
                class="flex-1 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
              >
                <span v-if="isCreatingConversation">Starting...</span>
                <span v-else>Start Conversation</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import ConversationView from './ConversationView.vue'

// Accept props from Inertia
const props = defineProps({
  conversations: Array,
  userPhoneNumbers: Array,
  hasPhoneNumbers: Boolean
})

const conversations = ref(props.conversations || [])
const selectedConversation = ref(null)
const showNewContactModal = ref(false)
const showNewConversationModal = ref(false)
const isCreatingConversation = ref(false)
const newContact = ref({
  name: '',
  phone_e164: ''
})
const newConversation = ref({
  name: '',
  phone_e164: '',
  message: '',
  from_phone_number_id: null,
  contactMode: 'existing' // 'existing' or 'new'
})

// Contact selection variables
const availableContacts = ref([])
const filteredContacts = ref([])
const contactSearchQuery = ref('')
const showContactDropdown = ref(false)
const selectedExistingContact = ref(null)

const selectConversation = (conversation) => {
  selectedConversation.value = conversation
}

// Form validation
const isFormValid = computed(() => {
  const hasMessage = newConversation.value.message?.trim()
  const hasPhoneNumber = newConversation.value.from_phone_number_id
  
  if (newConversation.value.contactMode === 'existing') {
    return hasMessage && hasPhoneNumber && selectedExistingContact.value
  } else {
    return hasMessage && hasPhoneNumber && newConversation.value.name?.trim() && newConversation.value.phone_e164?.trim()
  }
})

const refreshConversations = async () => {
  await loadConversations()
}

const loadConversations = async () => {
  try {
    const response = await fetch('/api/conversations')
    if (response.ok) {
      const data = await response.json()
      
      // Check for new conversations
      const oldConversationIds = new Set(conversations.value.map(c => c.id))
      const newConversations = (data || []).filter(c => !oldConversationIds.has(c.id))
      
      // Show notification for new conversations
      if (newConversations.length > 0 && conversations.value.length > 0) {
        showNewConversationNotification(newConversations)
      }
      
      conversations.value = data || []
      return data
    }
  } catch (error) {
    console.error('Error loading conversations:', error)
  }
  return null
}

const showNewConversationNotification = (newConversations) => {
  // Browser notification
  if ('Notification' in window && Notification.permission === 'granted') {
    newConversations.forEach(conversation => {
      new Notification('New SMS Message', {
        body: `New message from ${conversation.contact?.name || conversation.contact?.phone_e164}`,
        icon: '/favicon.ico',
        tag: `conversation-${conversation.id}`
      })
    })
  }
  
  // In-app notification (you can customize this)
  console.log('New conversations received:', newConversations)
}

// Request notification permission
const requestNotificationPermission = () => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }
}

const createContact = async () => {
  try {
    const response = await fetch('/messenger/contacts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(newContact.value)
    })

    if (response.ok) {
      showNewContactModal.value = false
      newContact.value = { name: '', phone_e164: '' }
      await loadConversations()
      await loadAvailableContacts() // Refresh contacts list
    }
  } catch (error) {
    console.error('Error creating contact:', error)
  }
}

const loadAvailableContacts = async () => {
  try {
    const response = await fetch('/messenger/contacts')
    if (response.ok) {
      const contacts = await response.json()
      availableContacts.value = contacts
      filteredContacts.value = contacts
    }
  } catch (error) {
    console.error('Error loading contacts:', error)
  }
}

const searchContacts = () => {
  const query = contactSearchQuery.value.toLowerCase()
  if (!query) {
    filteredContacts.value = availableContacts.value
  } else {
    filteredContacts.value = availableContacts.value.filter(contact =>
      (contact.name && contact.name.toLowerCase().includes(query)) ||
      contact.phone_e164.includes(query)
    )
  }
  showContactDropdown.value = true
}

const selectExistingContact = (contact) => {
  selectedExistingContact.value = contact
  contactSearchQuery.value = contact.name || contact.phone_e164
  showContactDropdown.value = false
  
  // Set the contact details for the conversation
  newConversation.value.name = contact.name
  newConversation.value.phone_e164 = contact.phone_e164
  newConversation.value.contact_id = contact.id
}

const clearSelectedContact = () => {
  selectedExistingContact.value = null
  contactSearchQuery.value = ''
  newConversation.value.name = ''
  newConversation.value.phone_e164 = ''
  newConversation.value.contact_id = null
  showContactDropdown.value = false
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showContactDropdown.value = false
  }
}

// Add event listener for clicking outside
document.addEventListener('click', handleClickOutside)

const startNewConversation = async () => {
  try {
    isCreatingConversation.value = true
    
    // Set default phone number if only one is available
    if (!newConversation.value.from_phone_number_id && props.userPhoneNumbers?.length === 1) {
      newConversation.value.from_phone_number_id = props.userPhoneNumbers[0].id
    }
    
    let response
    
    if (newConversation.value.contactMode === 'existing' && selectedExistingContact.value) {
      // Send message to existing contact
      response = await fetch('/messenger/send', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          contact_id: selectedExistingContact.value.id,
          content: newConversation.value.message,
          from_phone_number_id: newConversation.value.from_phone_number_id
        })
      })
    } else {
      // Create new contact and send message
      response = await fetch('/messenger/start-conversation', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(newConversation.value)
      })
    }

    if (response.ok) {
      const data = await response.json()
      showNewConversationModal.value = false
      resetNewConversationForm()
      
      // Reload conversations
      await loadConversations()
      
      // Auto-select the new conversation
      if (data.conversation) {
        selectedConversation.value = data.conversation
      }
    } else {
      const errorData = await response.json()
      console.error('Error starting conversation:', errorData)
      alert('Error starting conversation: ' + (errorData.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Error starting conversation:', error)
    alert('Error starting conversation: ' + error.message)
  } finally {
    isCreatingConversation.value = false
  }
}

const resetNewConversationForm = () => {
  newConversation.value = { 
    name: '', 
    phone_e164: '', 
    message: '', 
    from_phone_number_id: null,
    contactMode: 'existing',
    contact_id: null
  }
  clearSelectedContact()
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  return new Date(timestamp).toLocaleDateString([], { 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit', 
    minute: '2-digit'
  })
}

// Polling for new conversations
let pollInterval = null

const startPolling = () => {
  // Poll every 10 seconds for new conversations
  pollInterval = setInterval(() => {
    if (document.visibilityState === 'visible') {
      loadConversations()
    }
  }, 10000) // 10 seconds
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

// Handle visibility changes to pause/resume polling
const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    loadConversations() // Immediate refresh when returning to tab
    startPolling()
  } else {
    stopPolling()
  }
}

onMounted(() => {
  // Only load conversations if not already provided via props
  if (!props.conversations || props.conversations.length === 0) {
    loadConversations()
  }
  loadAvailableContacts()
  
  // Initialize default phone number for new conversation
  if (props.userPhoneNumbers?.length === 1) {
    newConversation.value.from_phone_number_id = props.userPhoneNumbers[0].id
  }
  
  // Request notification permission
  requestNotificationPermission()
  
  // Start polling for new conversations
  startPolling()
  
  // Listen for visibility changes
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

// Cleanup on unmount
onUnmounted(() => {
  stopPolling()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})
</script>
