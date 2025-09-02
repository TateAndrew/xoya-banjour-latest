<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h1 class="text-2xl font-bold mb-4">SMS Messenger Test</h1>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Test Contact Creation -->
            <div class="border rounded-lg p-4">
              <h2 class="text-lg font-semibold mb-3">Test Contact Creation</h2>
              <form @submit.prevent="createTestContact" class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Name</label>
                  <input
                    v-model="testContact.name"
                    type="text"
                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                    placeholder="Test Contact"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                  <input
                    v-model="testContact.phone_e164"
                    type="tel"
                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                    placeholder="+15551234567"
                  />
                </div>
                <button
                  type="submit"
                  class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                >
                  Create Test Contact
                </button>
              </form>
            </div>

            <!-- Test SMS Sending -->
            <div class="border rounded-lg p-4">
              <h2 class="text-lg font-semibold mb-3">Test SMS Sending</h2>
              <form @submit.prevent="sendTestSms" class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Contact ID</label>
                  <input
                    v-model="testSms.contact_id"
                    type="number"
                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                    placeholder="1"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Message</label>
                  <textarea
                    v-model="testSms.content"
                    rows="3"
                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"
                    placeholder="Hello from SMS Messenger!"
                  ></textarea>
                </div>
                <button
                  type="submit"
                  class="w-full bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600"
                >
                  Send Test SMS
                </button>
              </form>
            </div>
          </div>

          <!-- Test Results -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-3">Test Results</h2>
            <div class="bg-gray-50 rounded-lg p-4">
              <pre class="text-sm text-gray-700">{{ testResults }}</pre>
            </div>
          </div>

          <!-- Database Status -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-3">Database Status</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ dbStats.contacts }}</div>
                <div class="text-sm text-blue-600">Contacts</div>
              </div>
              <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ dbStats.conversations }}</div>
                <div class="text-sm text-green-600">Conversations</div>
              </div>
              <div class="bg-purple-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600">{{ dbStats.messages }}</div>
                <div class="text-sm text-purple-600">Messages</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const testContact = ref({
  name: 'Test Contact',
  phone_e164: '+15551234567'
})

const testSms = ref({
  contact_id: 1,
  content: 'Hello from SMS Messenger!'
})

const testResults = ref('No tests run yet.')
const dbStats = ref({
  contacts: 0,
  conversations: 0,
  messages: 0
})

const createTestContact = async () => {
  try {
    const response = await fetch('/messenger/contacts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(testContact.value)
    })

    if (response.ok) {
      const result = await response.json()
      testResults.value = `Contact created successfully:\n${JSON.stringify(result, null, 2)}`
      await loadDbStats()
    } else {
      testResults.value = `Failed to create contact: ${response.status}`
    }
  } catch (error) {
    testResults.value = `Error: ${error.message}`
  }
}

const sendTestSms = async () => {
  try {
    const response = await fetch('/messenger/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(testSms.value)
    })

    if (response.ok) {
      const result = await response.json()
      testResults.value = `SMS sent successfully:\n${JSON.stringify(result, null, 2)}`
      await loadDbStats()
    } else {
      testResults.value = `Failed to send SMS: ${response.status}`
    }
  } catch (error) {
    testResults.value = `Error: ${error.message}`
  }
}

const loadDbStats = async () => {
  try {
    const [contactsRes, conversationsRes, messagesRes] = await Promise.all([
      fetch('/messenger/contacts'),
      fetch('/messenger/conversations'),
      fetch('/messenger/messages')
    ])

    if (contactsRes.ok) {
      const contacts = await contactsRes.json()
      dbStats.value.contacts = contacts.length
    }

    if (conversationsRes.ok) {
      const conversations = await conversationsRes.json()
      dbStats.value.conversations = conversations.length
    }

    if (messagesRes.ok) {
      const messages = await messagesRes.json()
      dbStats.value.messages = messages.length
    }
  } catch (error) {
    console.error('Error loading DB stats:', error)
  }
}

onMounted(() => {
  loadDbStats()
})
</script>
