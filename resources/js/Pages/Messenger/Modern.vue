<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import Label from '@/Components/ui/Label.vue'
import Separator from '@/Components/ui/Separator.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import AvatarFallback from '@/Components/ui/AvatarFallback.vue'
import Dialog from '@/Components/ui/Dialog.vue'
import DialogContent from '@/Components/ui/DialogContent.vue'
import DialogHeader from '@/Components/ui/DialogHeader.vue'
import DialogTitle from '@/Components/ui/DialogTitle.vue'
import DialogDescription from '@/Components/ui/DialogDescription.vue'
import {
  MessageSquare,
  Send,
  Search,
  UserPlus,
  MoreVertical,
  Phone,
  Mail,
  Check,
  CheckCheck,
  Clock,
  AlertCircle,
  Loader2,
  Plus,
  X,
  RefreshCw,
  ArrowUp
} from 'lucide-vue-next'

const props = defineProps({
  conversations: Array,
  userPhoneNumbers: Array,
  hasPhoneNumbers: Boolean
})

// State
const conversations = ref(props.conversations || [])
const selectedConversation = ref(null)
const messages = ref([])
const newMessage = ref('')
const searchQuery = ref('')
const isSending = ref(false)
const isLoading = ref(false)
const isLoadingMore = ref(false)
const messagesContainer = ref(null)
const messageInput = ref(null)
const selectedPhoneNumber = ref(null)
const currentPage = ref(1)
const hasMoreMessages = ref(false)
const perPage = 20

// Modals
const showNewConversation = ref(false)
const showNewContact = ref(false)

// New conversation form
const newConversationForm = ref({
  contactMode: 'existing',
  contact_id: null,
  name: '',
  phone_e164: '',
  message: '',
  from_phone_number_id: null
})

// New contact form
const newContactForm = ref({
  name: '',
  phone_e164: ''
})

// Contact selection
const availableContacts = ref([])
const filteredContacts = ref([])
const contactSearchQuery = ref('')
const showContactDropdown = ref(false)
const selectedExistingContact = ref(null)
const isCreatingConversation = ref(false)

// Internal user selection
const internalUsers = ref([])
const filteredInternalUsers = ref([])
const internalUserSearchQuery = ref('')
const showInternalUserDropdown = ref(false)
const selectedInternalUser = ref(null)
const selectedInternalUserPhoneNumber = ref(null)
const isLoadingInternalUsers = ref(false)

// Echo channel
let echoChannel = null

// Filtered conversations
const filteredConversations = computed(() => {
  if (!searchQuery.value) return conversations.value

  const query = searchQuery.value.toLowerCase()
  return conversations.value.filter(conv => {
    const fields = [
      conv.contact?.name,
      conv.contact?.phone_e164,
      conv.contact?.email,
      conv.contact?.username,
      conv.contact?.user?.name,
      conv.contact?.user?.email,
      conv.last_message?.content
    ]
    return fields.some(field =>
      (field ?? '')
        .toString()
        .toLowerCase()
        .includes(query)
    )
  })
})

// Form validation
const isFormValid = computed(() => {
  const hasMessage = newConversationForm.value.message?.trim()
  const hasPhoneNumber = newConversationForm.value.from_phone_number_id
  
  if (newConversationForm.value.contactMode === 'existing') {
    return hasMessage && hasPhoneNumber && selectedExistingContact.value
  } else if (newConversationForm.value.contactMode === 'internal') {
    return hasMessage && hasPhoneNumber && selectedInternalUser.value && selectedInternalUserPhoneNumber.value
  } else {
    return hasMessage && hasPhoneNumber && newConversationForm.value.name?.trim() && newConversationForm.value.phone_e164?.trim()
  }
})

// Message status icon
const getStatusIcon = (status) => {
  switch(status) {
    case 'sent': return Check
    case 'delivered': return CheckCheck
    case 'failed': return AlertCircle
    default: return Clock
  }
}

// Get initials
const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Format time
const formatTime = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()
  
  if (isToday) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatInternalPhoneOption = (phone) => {
  if (!phone) return ''
  const parts = [phone.phone_number]
  if (phone.messaging_profile?.name) {
    parts.push(`• ${phone.messaging_profile.name}`)
  }
  return parts.filter(Boolean).join(' ')
}

// Select conversation
const selectConversation = async (conversation) => {
  selectedConversation.value = conversation
  await loadMessages(conversation.id)
}

// Load conversations
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

// Show notification
const showNewConversationNotification = (newConversations) => {
  if ('Notification' in window && Notification.permission === 'granted') {
    newConversations.forEach(conversation => {
      new Notification('New SMS Message', {
        body: `New message from ${conversation.contact?.name || conversation.contact?.phone_e164}`,
        icon: '/favicon.ico',
        tag: `conversation-${conversation.id}`
      })
    })
  }
}

// Request notification permission
const requestNotificationPermission = () => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }
}

// Load messages
const loadMessages = async (conversationId) => {
  isLoading.value = true
  try {
    const response = await fetch(`/messenger/conversation/${conversationId}/messages?page=1&per_page=${perPage}`)
    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages || []
      currentPage.value = 1
      hasMoreMessages.value = data.has_more || false
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Error loading messages:', error)
  } finally {
    isLoading.value = false
  }
}

// Load more messages
const loadMoreMessages = async () => {
  if (!selectedConversation.value?.id || isLoadingMore.value) return
  
  try {
    isLoadingMore.value = true
    const nextPage = currentPage.value + 1
    
    const container = messagesContainer.value
    const scrollHeightBefore = container.scrollHeight
    const scrollTopBefore = container.scrollTop
    
    const response = await fetch(`/messenger/conversation/${selectedConversation.value.id}/messages?page=${nextPage}&per_page=${perPage}`)
    if (response.ok) {
      const data = await response.json()
      const olderMessages = data.messages || []
      
      messages.value = [...olderMessages, ...messages.value]
      currentPage.value = nextPage
      hasMoreMessages.value = data.has_more || false
      
      await nextTick()
      
      const scrollHeightAfter = container.scrollHeight
      container.scrollTop = scrollTopBefore + (scrollHeightAfter - scrollHeightBefore)
    }
  } catch (error) {
    console.error('Error loading more messages:', error)
  } finally {
    isLoadingMore.value = false
  }
}

// Send message
const sendMessage = async () => {
  if (!newMessage.value.trim() || !selectedConversation.value || isSending.value || !selectedPhoneNumber.value) return
  
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
        contact_id: selectedConversation.value.contact.id,
        content: content,
        from_phone_number_id: selectedPhoneNumber.value
      })
    })
    
    if (response.ok) {
      newMessage.value = ''
      await loadMessages(selectedConversation.value.id)
      await loadConversations()
      if (messageInput.value) {
        messageInput.value.style.height = 'auto'
      }
    }
  } catch (error) {
    console.error('Error sending message:', error)
  } finally {
    isSending.value = false
  }
}

// Mark as read
const markAsRead = async () => {
  if (!selectedConversation.value) return
  
  try {
    await fetch(`/messenger/conversation/${selectedConversation.value.id}/read`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    await loadConversations()
  } catch (error) {
    console.error('Error marking as read:', error)
  }
}

// Scroll to bottom
const scrollToBottom = (behavior = 'auto') => {
  if (messagesContainer.value) {
    requestAnimationFrame(() => {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: behavior
      })
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

// Load contacts
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

// Search contacts
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

// Select existing contact
const selectExistingContact = (contact) => {
  selectedExistingContact.value = contact
  contactSearchQuery.value = contact.name || contact.phone_e164
  showContactDropdown.value = false
  
  newConversationForm.value.name = contact.name
  newConversationForm.value.phone_e164 = contact.phone_e164
  newConversationForm.value.contact_id = contact.id
}

// Clear selected contact
const clearSelectedContact = () => {
  selectedExistingContact.value = null
  contactSearchQuery.value = ''
  newConversationForm.value.name = ''
  newConversationForm.value.phone_e164 = ''
  newConversationForm.value.contact_id = null
  showContactDropdown.value = false
}

// Load internal users
const loadInternalUsers = async () => {
  try {
    isLoadingInternalUsers.value = true
    const response = await fetch('/messenger/internal-users')
    if (response.ok) {
      const users = await response.json()
      internalUsers.value = users || []
      filteredInternalUsers.value = users || []
    }
  } catch (error) {
    console.error('Error loading internal users:', error)
  } finally {
    isLoadingInternalUsers.value = false
  }
}

// Search internal users
const searchInternalUsers = () => {
  const query = internalUserSearchQuery.value.toLowerCase()
  if (!query) {
    filteredInternalUsers.value = internalUsers.value
  } else {
    filteredInternalUsers.value = internalUsers.value.filter(user => {
      const phoneMatches = (user.phone_numbers || []).some(phone =>
        (phone.phone_number || '').toLowerCase().includes(query) ||
        (phone.e164 || '').toLowerCase().includes(query)
      )

      return (
        (user.name && user.name.toLowerCase().includes(query)) ||
        (user.email && user.email.toLowerCase().includes(query)) ||
        phoneMatches
      )
    })
  }
  showInternalUserDropdown.value = true
}

// Select internal user
const selectInternalUser = (user) => {
  selectedInternalUser.value = user
  internalUserSearchQuery.value = user.name || user.email || ''
  showInternalUserDropdown.value = false

  if (user.phone_numbers?.length > 0) {
    const defaultPhone = user.phone_numbers.find(phone => !!phone.messaging_profile) || user.phone_numbers[0]
    selectedInternalUserPhoneNumber.value = defaultPhone?.id || null
  } else {
    selectedInternalUserPhoneNumber.value = null
  }
}

// Clear internal user
const clearSelectedInternalUser = () => {
  selectedInternalUser.value = null
  selectedInternalUserPhoneNumber.value = null
  internalUserSearchQuery.value = ''
  showInternalUserDropdown.value = false
}

// Create contact
const createContact = async () => {
  try {
    const response = await fetch('/messenger/contacts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(newContactForm.value)
    })

    if (response.ok) {
      showNewContact.value = false
      newContactForm.value = { name: '', phone_e164: '' }
      await loadConversations()
      await loadAvailableContacts()
    }
  } catch (error) {
    console.error('Error creating contact:', error)
  }
}

// Start new conversation
const startNewConversation = async () => {
  try {
    isCreatingConversation.value = true
    
    if (!newConversationForm.value.from_phone_number_id && props.userPhoneNumbers?.length === 1) {
      newConversationForm.value.from_phone_number_id = props.userPhoneNumbers[0].id
    }
    
    let response
    const mode = newConversationForm.value.contactMode
    
    if (mode === 'existing' && selectedExistingContact.value) {
      response = await fetch('/messenger/send', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          contact_id: selectedExistingContact.value.id,
          content: newConversationForm.value.message,
          from_phone_number_id: newConversationForm.value.from_phone_number_id
        })
      })
    } else if (mode === 'internal' && selectedInternalUser.value) {
      response = await fetch('/messenger/start-conversation', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          contact_mode: 'internal',
          internal_user_id: selectedInternalUser.value.id,
          internal_user_phone_number_id: selectedInternalUserPhoneNumber.value,
          message: newConversationForm.value.message,
          from_phone_number_id: newConversationForm.value.from_phone_number_id
        })
      })
    } else {
      const payload = {
        contact_mode: 'new',
        name: newConversationForm.value.name,
        phone_e164: newConversationForm.value.phone_e164,
        message: newConversationForm.value.message,
        from_phone_number_id: newConversationForm.value.from_phone_number_id
      }

      response = await fetch('/messenger/start-conversation', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
      })
    }

    if (response.ok) {
      const data = await response.json()
      showNewConversation.value = false
      resetNewConversationForm()
      
      await loadConversations()
      await loadAvailableContacts()
      await loadInternalUsers()
      
      if (data.conversation) {
        selectedConversation.value = data.conversation
        await loadMessages(data.conversation.id)
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

// Reset form
const resetNewConversationForm = () => {
  newConversationForm.value = { 
    contactMode: 'existing',
    contact_id: null,
    name: '', 
    phone_e164: '', 
    message: '', 
    from_phone_number_id: null
  }
  clearSelectedContact()
  clearSelectedInternalUser()
}

// Setup real-time
const setupRealtimeBroadcasting = () => {
  if (!window.Echo) {
    console.warn('Echo not initialized')
    return
  }

  const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content')
  
  if (!userId) {
    console.warn('User ID not found')
    return
  }

  window.Echo.channel(`user.${userId}`)
    .listen('.message.received', (event) => {
      console.log('New message received:', event)
      loadConversations()
      
      if (selectedConversation.value && event.conversation?.id === selectedConversation.value.id) {
        loadMessages(selectedConversation.value.id)
      }
      
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('New SMS Message', {
          body: event.message?.content || 'You have a new message',
          icon: '/favicon.ico'
        })
      }
    })

  return () => {
    if (window.Echo) {
      window.Echo.leave(`user.${userId}`)
    }
  }
}

// Watch for conversation changes
watch(() => selectedConversation.value, async (newConv, oldConv) => {
  if (newConv && newConv.id !== oldConv?.id) {
    messages.value = []
    currentPage.value = 1
    hasMoreMessages.value = false
    await loadMessages(newConv.id)
  }
})

// Watch for input changes
watch(newMessage, autoResize)

// Watch contact mode changes in new conversation modal
watch(() => newConversationForm.value.contactMode, (mode) => {
  if (mode !== 'existing') {
    showContactDropdown.value = false
  }

  if (mode !== 'internal') {
    showInternalUserDropdown.value = false
  } else if (!internalUsers.value.length && !isLoadingInternalUsers.value) {
    loadInternalUsers()
  }
})

// Lifecycle
onMounted(() => {
  if (!props.conversations || props.conversations.length === 0) {
    loadConversations()
  }
  loadAvailableContacts()
  loadInternalUsers()
  
  if (props.userPhoneNumbers?.length === 1) {
    selectedPhoneNumber.value = props.userPhoneNumbers[0].id
    newConversationForm.value.from_phone_number_id = props.userPhoneNumbers[0].id
  }
  
  if (conversations.value.length > 0) {
    selectConversation(conversations.value[0])
  }
  
  requestNotificationPermission()
  
  const cleanupBroadcasting = setupRealtimeBroadcasting()
  
  onUnmounted(() => {
    if (cleanupBroadcasting) cleanupBroadcasting()
  })
})
</script>

<template>
  <Head title="Messenger" />

  <DashboardLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">SMS Messenger</h1>
          <p class="text-muted-foreground">Send and receive text messages</p>
        </div>
        <div v-if="hasPhoneNumbers" class="flex items-center gap-2">
          <Button variant="outline" size="icon" @click="loadConversations">
            <RefreshCw :size="16" />
          </Button>
          <Button @click="showNewContact = true" variant="outline" class="gap-2">
            <UserPlus :size="16" />
            New Contact
          </Button>
          <Button @click="showNewConversation = true" class="gap-2">
            <Plus :size="16" />
            New Message
          </Button>
        </div>
      </div>
    </template>

    <!-- No Phone Numbers Warning -->
    <div v-if="!hasPhoneNumbers" class="flex items-center justify-center h-[calc(100vh-12rem)]">
      <Card class="max-w-md">
        <CardContent class="pt-6 text-center">
          <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertCircle :size="32" class="text-yellow-600" />
          </div>
          <h3 class="text-lg font-semibold mb-2">No Phone Numbers Available</h3>
          <p class="text-sm text-muted-foreground mb-4">
            You need to purchase a phone number and assign it to a messaging profile before you can send SMS messages.
          </p>
          <div class="flex flex-col gap-2">
            <a href="/phone-numbers/purchase">
              <Button class="w-full">
                Purchase Phone Number
              </Button>
            </a>
            <a href="/messaging-profiles">
              <Button variant="outline" class="w-full">
                Manage Messaging Profiles
              </Button>
            </a>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Main Messenger Layout -->
    <div v-else class="grid lg:grid-cols-[350px_1fr] gap-6 h-[calc(100vh-12rem)]">
      <!-- Conversations Sidebar -->
      <Card class="flex flex-col overflow-hidden">
        <CardContent class="p-0 flex flex-col h-full">
          <!-- Search -->
          <div class="p-4 border-b">
            <div class="relative">
              <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input
                v-model="searchQuery"
                placeholder="Search conversations..."
                class="pl-9"
              />
            </div>
          </div>

          <!-- Conversations List -->
          <div class="flex-1 overflow-y-auto">
            <div v-if="filteredConversations.length === 0" class="p-8 text-center">
              <MessageSquare :size="48" class="mx-auto text-muted-foreground mb-4" />
              <p class="text-sm text-muted-foreground">No conversations yet</p>
              <p class="text-xs text-muted-foreground mt-1">Start by sending a message</p>
            </div>

            <div
              v-for="conversation in filteredConversations"
              :key="conversation.id"
              @click="selectConversation(conversation)"
              :class="[
                'flex items-start gap-3 p-4 border-b cursor-pointer transition-colors',
                selectedConversation?.id === conversation.id
                  ? 'bg-accent border-l-4 border-l-primary'
                  : 'hover:bg-accent/50 border-l-4 border-l-transparent'
              ]"
            >
              <!-- Avatar -->
              <div class="relative">
                <Avatar>
                  <AvatarFallback class="bg-primary text-primary-foreground">
                    {{ getInitials(conversation.contact?.name || conversation.contact?.phone_e164) }}
                  </AvatarFallback>
                </Avatar>
                <div v-if="conversation.unread_count > 0" class="absolute -top-1 -right-1">
                  <Badge variant="destructive" class="h-5 w-5 p-0 flex items-center justify-center text-xs">
                    {{ conversation.unread_count > 9 ? '9+' : conversation.unread_count }}
                  </Badge>
                </div>
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                  <div class="min-w-0">
                    <p class="text-sm font-semibold truncate">
                      {{ conversation.contact?.name || conversation.contact?.username || conversation.contact?.phone_e164 }}
                    </p>
                    <p v-if="conversation.contact?.email" class="text-xs text-muted-foreground truncate">
                      {{ conversation.contact.email }}
                    </p>
                  </div>
                  <span class="text-xs text-muted-foreground flex-shrink-0 ml-2">
                    {{ formatTime(conversation.last_message_at) }}
                  </span>
                </div>
                <p :class="[
                  'text-xs truncate',
                  conversation.unread_count > 0 ? 'font-medium text-foreground' : 'text-muted-foreground'
                ]">
                  <span v-if="conversation.last_message?.direction === 'outbound'" class="text-primary">You: </span>
                  {{ conversation.last_message?.content || 'No messages yet' }}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Chat Area -->
      <Card class="flex flex-col overflow-hidden">
        <CardContent v-if="!selectedConversation" class="p-0 flex items-center justify-center h-full">
          <div class="text-center">
            <MessageSquare :size="64" class="mx-auto text-muted-foreground mb-4" />
            <h3 class="text-lg font-semibold mb-2">Select a conversation</h3>
            <p class="text-sm text-muted-foreground">Choose a contact to start messaging</p>
          </div>
        </CardContent>

        <CardContent v-else class="p-0 flex flex-col h-full">
          <!-- Chat Header -->
          <div class="flex items-center justify-between p-4 border-b bg-card">
            <div class="flex items-center gap-3">
              <Avatar>
                <AvatarFallback class="bg-primary text-primary-foreground">
                  {{ getInitials(selectedConversation.contact?.name || selectedConversation.contact?.phone_e164) }}
                </AvatarFallback>
              </Avatar>
              <div>
                <h3 class="font-semibold">
                  {{ selectedConversation.contact?.name || selectedConversation.contact?.phone_e164 }}
                </h3>
                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                  <Phone :size="12" />
                  {{ selectedConversation.contact?.phone_e164 }}
                </div>
                <div v-if="selectedConversation.contact?.email" class="flex items-center gap-1 text-xs text-muted-foreground">
                  <Mail :size="12" />
                  {{ selectedConversation.contact.email }}
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="outline" size="sm" @click="markAsRead">
                <CheckCheck :size="16" class="mr-1" />
                Mark Read
              </Button>
              <Button variant="ghost" size="icon">
                <MoreVertical :size="16" />
              </Button>
            </div>
          </div>

          <!-- Messages -->
          <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-muted/20">
            <!-- Load More Button -->
            <div v-if="hasMoreMessages && !isLoading" class="flex justify-center pb-4">
              <Button
                variant="outline"
                size="sm"
                @click="loadMoreMessages"
                :disabled="isLoadingMore"
              >
                <Loader2 v-if="isLoadingMore" :size="16" class="mr-2 animate-spin" />
                <ArrowUp v-else :size="16" class="mr-2" />
                {{ isLoadingMore ? 'Loading...' : 'Load More' }}
              </Button>
            </div>

            <div v-if="isLoading" class="flex items-center justify-center h-full">
              <Loader2 :size="32" class="animate-spin text-primary" />
            </div>

            <div v-else-if="messages.length === 0" class="flex items-center justify-center h-full">
              <div class="text-center">
                <MessageSquare :size="48" class="mx-auto text-muted-foreground mb-4" />
                <p class="text-sm text-muted-foreground">No messages yet</p>
                <p class="text-xs text-muted-foreground mt-1">Send your first message below</p>
              </div>
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
                  'max-w-[70%] rounded-2xl px-4 py-2 space-y-1',
                  message.direction === 'outbound'
                    ? 'bg-primary text-primary-foreground rounded-br-sm'
                    : 'bg-card border rounded-bl-sm'
                ]"
              >
                <p class="text-sm whitespace-pre-wrap break-words">{{ message.content }}</p>
                <div class="flex items-center gap-1 justify-end">
                  <span :class="[
                    'text-xs',
                    message.direction === 'outbound' ? 'text-primary-foreground/70' : 'text-muted-foreground'
                  ]">
                    {{ formatTime(message.created_at) }}
                  </span>
                  <component
                    v-if="message.direction === 'outbound'"
                    :is="getStatusIcon(message.status)"
                    :size="12"
                    :class="message.direction === 'outbound' ? 'text-primary-foreground/70' : 'text-muted-foreground'"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="p-4 border-t bg-card">
            <!-- Phone Number Selector -->
            <div v-if="userPhoneNumbers?.length > 1" class="mb-3">
              <Label class="text-xs">Send From</Label>
              <select
                v-model="selectedPhoneNumber"
                class="w-full mt-1 px-3 py-2 text-sm border border-input bg-background rounded-md"
              >
                <option v-for="phoneNumber in userPhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
                  {{ phoneNumber.phone_number }}
                  <span v-if="phoneNumber.messaging_profile">• {{ phoneNumber.messaging_profile.name }}</span>
                </option>
              </select>
            </div>

            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
              <div class="flex-1">
                <textarea
                  ref="messageInput"
                  v-model="newMessage"
                  @keydown.ctrl.enter="sendMessage"
                  @input="autoResize"
                  placeholder="Type a message... (Ctrl+Enter to send)"
                  rows="1"
                  class="w-full min-h-[44px] max-h-[120px] px-4 py-2 rounded-lg border border-input bg-background resize-none focus:outline-none focus:ring-2 focus:ring-ring"
                  :disabled="isSending"
                />
                <p class="text-xs text-muted-foreground mt-1">
                  {{ newMessage.length }}/1600 characters
                </p>
              </div>
              <Button
                type="submit"
                size="icon"
                :disabled="!newMessage.trim() || isSending || !selectedPhoneNumber"
                class="h-11 w-11"
              >
                <Loader2 v-if="isSending" :size="20" class="animate-spin" />
                <Send v-else :size="20" />
              </Button>
            </form>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- New Contact Dialog -->
    <Dialog :open="showNewContact" @update:open="showNewContact = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add New Contact</DialogTitle>
          <DialogDescription>Create a new contact to start messaging</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="createContact" class="space-y-4">
          <div class="space-y-2">
            <Label>Name</Label>
            <Input v-model="newContactForm.name" placeholder="Contact name" required />
          </div>
          <div class="space-y-2">
            <Label>Phone Number</Label>
            <Input v-model="newContactForm.phone_e164" type="tel" placeholder="+15551234567" required />
          </div>
          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="showNewContact = false">
              Cancel
            </Button>
            <Button type="submit">
              Add Contact
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- New Conversation Dialog -->
    <Dialog :open="showNewConversation" @update:open="showNewConversation = $event">
      <DialogContent class="max-w-md max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Start New Conversation</DialogTitle>
          <DialogDescription>Send a message to start a new conversation</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="startNewConversation" class="space-y-4">
          <!-- Contact Mode Toggle -->
          <div class="space-y-2">
            <div class="flex gap-4">
              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  value="existing"
                  v-model="newConversationForm.contactMode"
                  class="mr-2"
                />
                <span class="text-sm">Existing Contact</span>
                <Badge variant="secondary" class="ml-2 text-xs">Safe</Badge>
              </label>
              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  value="new"
                  v-model="newConversationForm.contactMode"
                  class="mr-2"
                />
                <span class="text-sm">New Contact</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  value="internal"
                  v-model="newConversationForm.contactMode"
                  class="mr-2"
                />
                <span class="text-sm">Internal User</span>
              </label>
            </div>
          </div>

          <!-- Existing Contact Selection -->
          <div v-if="newConversationForm.contactMode === 'existing'" class="space-y-2">
            <Label>Select Contact</Label>
            <div class="relative">
              <Input
                v-model="contactSearchQuery"
                @input="searchContacts"
                @focus="showContactDropdown = true"
                placeholder="Search contacts..."
              />
              
              <!-- Contact Dropdown -->
              <div v-if="showContactDropdown && filteredContacts.length > 0" 
                   class="absolute z-10 w-full mt-1 bg-card border rounded-lg shadow-lg max-h-48 overflow-y-auto">
                <div
                  v-for="contact in filteredContacts"
                  :key="contact.id"
                  @click="selectExistingContact(contact)"
                  class="px-3 py-2 hover:bg-accent cursor-pointer border-b last:border-b-0"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium">{{ contact.name || 'Unknown' }}</div>
                      <div class="text-sm text-muted-foreground">{{ contact.phone_e164 }}</div>
                      <div v-if="contact.email" class="text-xs text-muted-foreground">{{ contact.email }}</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Selected Contact Display -->
              <div v-if="selectedExistingContact" class="mt-2 p-3 bg-accent border rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-medium">{{ selectedExistingContact.name || 'Unknown' }}</div>
                    <div class="text-sm text-muted-foreground">{{ selectedExistingContact.phone_e164 }}</div>
                    <div v-if="selectedExistingContact.email" class="text-xs text-muted-foreground">
                      {{ selectedExistingContact.email }}
                    </div>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    @click="clearSelectedContact"
                    type="button"
                  >
                    <X :size="16" />
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <!-- Internal User Selection -->
          <div v-if="newConversationForm.contactMode === 'internal'" class="space-y-3">
            <div class="space-y-2">
              <Label>Select User</Label>
              <div class="relative">
                <Input
                  v-model="internalUserSearchQuery"
                  @input="searchInternalUsers"
                  @focus="showInternalUserDropdown = true"
                  placeholder="Search by name or email..."
                />

                <div
                  v-if="showInternalUserDropdown"
                  class="absolute z-10 w-full mt-1 bg-card border rounded-lg shadow-lg max-h-48 overflow-y-auto"
                >
                  <div v-if="isLoadingInternalUsers" class="px-3 py-4 text-sm text-muted-foreground flex items-center justify-center gap-2">
                    <Loader2 :size="16" class="animate-spin" />
                    Loading internal users...
                  </div>
                  <template v-else>
                    <div
                      v-for="user in filteredInternalUsers"
                      :key="user.id"
                      @click="selectInternalUser(user)"
                      class="px-3 py-2 hover:bg-accent cursor-pointer border-b last:border-b-0"
                    >
                      <div class="font-medium">{{ user.name || 'Unknown User' }}</div>
                      <div class="text-xs text-muted-foreground">{{ user.email || 'No email' }}</div>
                      <div v-if="user.phone_numbers?.length" class="text-xs text-muted-foreground mt-1">
                        {{ user.phone_numbers[0].phone_number }}
                      </div>
                      <div v-else class="text-xs text-muted-foreground mt-1">
                        No messaging number assigned
                      </div>
                    </div>
                    <div
                      v-if="filteredInternalUsers.length === 0"
                      class="px-3 py-2 text-sm text-muted-foreground"
                    >
                      No matching users found
                    </div>
                  </template>
                </div>
              </div>
            </div>

            <div
              v-if="selectedInternalUser"
              class="p-3 bg-accent border rounded-lg space-y-3"
            >
              <div class="flex items-start justify-between">
                <div>
                  <div class="font-medium">{{ selectedInternalUser.name || 'Unknown User' }}</div>
                  <div class="text-sm text-muted-foreground">{{ selectedInternalUser.email || 'No email' }}</div>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  @click="clearSelectedInternalUser"
                  type="button"
                >
                  <X :size="16" />
                </Button>
              </div>

              <div v-if="selectedInternalUser.phone_numbers?.length" class="space-y-2">
                <Label class="text-xs">Send To</Label>
                <select
                  v-model="selectedInternalUserPhoneNumber"
                  class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm"
                  required
                >
                  <option
                    v-for="phone in selectedInternalUser.phone_numbers"
                    :key="phone.id"
                    :value="phone.id"
                  >
                    {{ formatInternalPhoneOption(phone) }}
                  </option>
                </select>
              </div>

              <p v-else class="text-xs text-muted-foreground">
                This user does not currently have a messaging-enabled phone number.
              </p>
            </div>
          </div>

          <!-- New Contact Fields -->
          <div v-if="newConversationForm.contactMode === 'new'" class="space-y-2">
            <div>
              <Label>Contact Name</Label>
              <Input v-model="newConversationForm.name" placeholder="Contact name" required />
            </div>
            <div>
              <Label>Phone Number</Label>
              <Input v-model="newConversationForm.phone_e164" type="tel" placeholder="+15551234567" required />
            </div>
          </div>

          <div class="space-y-2">
            <Label>Message</Label>
            <textarea
              v-model="newConversationForm.message"
              rows="3"
              placeholder="Type your first message..."
              class="w-full px-3 py-2 rounded-lg border border-input bg-background resize-none focus:outline-none focus:ring-2 focus:ring-ring"
              required
            />
          </div>

          <div v-if="userPhoneNumbers?.length > 1" class="space-y-2">
            <Label>Send From</Label>
            <select
              v-model="newConversationForm.from_phone_number_id"
              class="w-full px-3 py-2 border border-input bg-background rounded-md"
              required
            >
              <option value="">Select phone number</option>
              <option v-for="phoneNumber in userPhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
                {{ phoneNumber.phone_number }}
              </option>
            </select>
          </div>

          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="showNewConversation = false">
              Cancel
            </Button>
            <Button type="submit" :disabled="!isFormValid || isCreatingConversation">
              <Loader2 v-if="isCreatingConversation" :size="16" class="mr-2 animate-spin" />
              {{ isCreatingConversation ? 'Sending...' : 'Send Message' }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  </DashboardLayout>
</template>
