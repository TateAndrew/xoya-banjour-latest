# Telnyx SMS Messenger 
---

## Table of Contents

1. [Features](#features)
2. [Prerequisites](#prerequisites)
3. [Telnyx Portal Setup](#telnyx-portal-setup)
4. [Local Environment & Installation](#local-environment--installation)
5. [Configuration](#configuration)
6. [Database Schema](#database-schema)
7. [Backend (PHP/Laravel) Implementation](#backend-phplaravel-implementation)

   * [Routes](#routes)
   * [Controllers /Services](#controllers--services)
   * [Webhooks (Inbound SMS & DLR)](#webhooks-inbound-sms--dlr)
   * [Sending Messages (SDK)](#sending-messages-sdk)
   * [Read/Unread & Typing Indicators](#readunread--typing-indicators)
8. [Messenger UI (vue intertia / Tailwind) design same like zoom sms](#messenger-ui-htmltailwind)


---

## Features

* Two‑way SMS with Telnyx Messaging Profiles & Numbers
* Contact list, conversation threads, message composer
* Delivery receipts, inbound message handling, retries
* Read receipts (simulated), typing indicators (optional)
* Clean Tailwind CSS messenger layout
* Works with plain PHP or Laravel 10/11

## Prerequisites

  * Verified **Messaging Profile**
  * **SMS‑capable phone number** attached to that profile
  * **Public Webhook URL** reachable from the internet (e.g., using Cloudflare Tunnel, ngrok, etc.)

> Keep these values safe. We'll wire them as environment variables below.

---

## Local Environment & Installation

```bash
# 1) Install the Telnyx PHP SDK
composer require telnyx/telnyx-php ( already installed )
```

## Configuration

Add these environment variables to your `.env` file:

```env
TELNYX_API_KEY=your_telnyx_api_key_here
TELNYX_MESSAGING_PROFILE_ID=your_messaging_profile_id
TELNYX_WEBHOOK_SECRET=your_webhook_secret
TELNYX_PHONE_NUMBER=+15551234567
```

## Database Schema

Minimum tables for a lightweight messenger:

```sql
-- contacts
CREATE TABLE contacts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  external_id VARCHAR(64) NULL,           -- CRM/ERP id (optional)
  name VARCHAR(191) NULL,
  phone_e164 VARCHAR(32) UNIQUE NOT NULL, -- e.g. +15551234567
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);

-- conversations (one per contact & sender number)
CREATE TABLE conversations (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  contact_id BIGINT UNSIGNED NOT NULL,
  sender_number VARCHAR(32) NOT NULL,     -- your Telnyx number
  last_message_at TIMESTAMP NULL,
  unread_count INT DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY unique_pair (contact_id, sender_number)
);

-- messages
CREATE TABLE messages (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  conversation_id BIGINT UNSIGNED NOT NULL,
  telnyx_message_id VARCHAR(64) NULL,    -- for delivery receipts
  direction ENUM('inbound', 'outbound') NOT NULL,
  content TEXT NOT NULL,
  status ENUM('queued', 'sending', 'sent', 'delivered', 'failed') DEFAULT 'queued',
  sent_at TIMESTAMP NULL,
  delivered_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_conversation (conversation_id),
  INDEX idx_telnyx_id (telnyx_message_id)
);

-- messaging_profiles (already exists in your project)
-- phone_numbers (already exists in your project)
```

## Backend (PHP/Laravel) Implementation

### Routes

Add these routes to your `routes/web.php`:

```php
// SMS Messenger Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/messenger', [SmsController::class, 'index'])->name('messenger.index');
    Route::get('/messenger/conversation/{conversation}', [SmsController::class, 'showConversation'])->name('messenger.conversation');
    Route::post('/messenger/send', [SmsController::class, 'sendMessage'])->name('messenger.send');
    Route::get('/messenger/contacts', [SmsController::class, 'getContacts'])->name('messenger.contacts');
    Route::post('/messenger/contacts', [SmsController::class, 'storeContact'])->name('messenger.contacts.store');
});

// Webhook routes (no auth required)
Route::post('/webhooks/telnyx/sms', [WebhookController::class, 'handleSmsWebhook']);
Route::post('/webhooks/telnyx/dlr', [WebhookController::class, 'handleDeliveryReceipt']);
```

### Controllers /Services

#### SmsController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsController extends Controller
{
    protected $telnyxService;

    public function __construct(TelnyxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    public function index()
    {
        $conversations = Conversation::with(['contact', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return Inertia::render('Messenger/Index', [
            'conversations' => $conversations
        ]);
    }

    public function showConversation(Conversation $conversation)
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Messenger/Conversation', [
            'conversation' => $conversation->load('contact'),
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'content' => 'required|string|max:1600'
        ]);

        $contact = Contact::findOrFail($request->contact_id);
        
        // Get or create conversation
        $conversation = Conversation::firstOrCreate([
            'contact_id' => $contact->id,
            'sender_number' => config('services.telnyx.phone_number')
        ]);

        // Create message record
        $message = $conversation->messages()->create([
            'direction' => 'outbound',
            'content' => $request->content,
            'status' => 'queued'
        ]);

        // Send via Telnyx
        $response = $this->telnyxService->sendSms(
            $contact->phone_e164,
            $request->content
        );

        if ($response && isset($response['data']['id'])) {
            $message->update([
                'telnyx_message_id' => $response['data']['id'],
                'status' => 'sending'
            ]);
        }

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => 0
        ]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function getContacts()
    {
        $contacts = Contact::orderBy('name')->get();
        return response()->json($contacts);
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'phone_e164' => 'required|string|unique:contacts,phone_e164'
        ]);

        $contact = Contact::create($request->all());
        return response()->json($contact);
    }
}
```

#### TelnyxService

```php
<?php

namespace App\Services;

use Telnyx\Telnyx;
use Telnyx\Message;

class TelnyxService
{
    public function __construct()
    {
        Telnyx::setApiKey(config('services.telnyx.api_key'));
    }

    public function sendSms($to, $content)
    {
        try {
            $message = Message::create([
                'from' => config('services.telnyx.phone_number'),
                'to' => $to,
                'text' => $content,
                'messaging_profile_id' => config('services.telnyx.messaging_profile_id')
            ]);

            return $message->toArray();
        } catch (\Exception $e) {
            \Log::error('Telnyx SMS send error: ' . $e->getMessage());
            return null;
        }
    }
}
```

### Webhooks (Inbound SMS & DLR)

#### WebhookController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleSmsWebhook(Request $request)
    {
        Log::info('SMS Webhook received', $request->all());

        $data = $request->all();
        
        if ($data['event_type'] === 'message.received') {
            $this->handleInboundSms($data['data']['payload']);
        }

        return response()->json(['status' => 'success']);
    }

    public function handleDeliveryReceipt(Request $request)
    {
        Log::info('DLR Webhook received', $request->all());

        $data = $request->all();
        
        if ($data['event_type'] === 'message.delivered') {
            $this->handleDeliveryReceipt($data['data']['payload']);
        }

        return response()->json(['status' => 'success']);
    }

    private function handleInboundSms($payload)
    {
        $from = $payload['from']['phone_number'];
        $content = $payload['text'];
        $telnyxId = $payload['id'];

        // Find or create contact
        $contact = Contact::firstOrCreate(
            ['phone_e164' => $from],
            ['name' => 'Unknown Contact']
        );

        // Get or create conversation
        $conversation = Conversation::firstOrCreate([
            'contact_id' => $contact->id,
            'sender_number' => config('services.telnyx.phone_number')
        ]);

        // Create message
        $conversation->messages()->create([
            'telnyx_message_id' => $telnyxId,
            'direction' => 'inbound',
            'content' => $content,
            'status' => 'delivered',
            'delivered_at' => now()
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => $conversation->unread_count + 1
        ]);
    }

    private function handleDeliveryReceipt($payload)
    {
        $telnyxId = $payload['id'];
        $status = $payload['status'];

        $message = Message::where('telnyx_message_id', $telnyxId)->first();
        
        if ($message) {
            $message->update([
                'status' => $status,
                'delivered_at' => $status === 'delivered' ? now() : null
            ]);
        }
    }
}
```

### Sending Messages (SDK)

The TelnyxService above handles sending messages using the official SDK.

### Read/Unread & Typing Indicators

For read receipts, you can implement a simple endpoint that marks messages as read:

```php
// In SmsController
public function markAsRead(Conversation $conversation)
{
    $conversation->update(['unread_count' => 0]);
    return response()->json(['success' => true]);
}
```

## Messenger UI (Vue Inertia / Tailwind)

### Main Messenger Page

Create `resources/js/Pages/Messenger/Index.vue`:

```vue
<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="w-80 bg-white border-r border-gray-200">
      <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-900">SMS Messenger</h1>
      </div>
      
      <!-- Contact List -->
      <div class="overflow-y-auto h-full">
        <div
          v-for="conversation in conversations"
          :key="conversation.id"
          @click="selectConversation(conversation)"
          class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
        >
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
              <span class="text-white font-medium">
                {{ conversation.contact.name ? conversation.contact.name.charAt(0).toUpperCase() : '?' }}
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ conversation.contact.name || conversation.contact.phone_e164 }}
              </p>
              <p class="text-sm text-gray-500 truncate">
                {{ conversation.last_message ? conversation.last_message.content : 'No messages yet' }}
              </p>
            </div>
            <div v-if="conversation.unread_count > 0" class="ml-2">
              <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                {{ conversation.unread_count }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
      <div v-if="selectedConversation" class="flex-1 flex flex-col">
        <ConversationView :conversation="selectedConversation" />
      </div>
      <div v-else class="flex-1 flex items-center justify-center">
        <div class="text-center">
          <h3 class="text-lg font-medium text-gray-900">Select a conversation</h3>
          <p class="text-gray-500">Choose a contact to start messaging</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ConversationView from './ConversationView.vue'

const conversations = ref([])
const selectedConversation = ref(null)

const selectConversation = (conversation) => {
  selectedConversation.value = conversation
}
</script>
```

### Conversation View

Create `resources/js/Pages/Messenger/ConversationView.vue`:

```vue
<template>
  <div class="flex-1 flex flex-col">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
          <span class="text-white font-medium">
            {{ conversation.contact.name ? conversation.contact.name.charAt(0).toUpperCase() : '?' }}
          </span>
        </div>
        <div>
          <h2 class="text-lg font-medium text-gray-900">
            {{ conversation.contact.name || conversation.contact.phone_e164 }}
          </h2>
          <p class="text-sm text-gray-500">{{ conversation.contact.phone_e164 }}</p>
        </div>
      </div>
    </div>

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-6 space-y-4">
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
            'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
            message.direction === 'outbound'
              ? 'bg-blue-500 text-white'
              : 'bg-gray-200 text-gray-900'
          ]"
        >
          <p class="text-sm">{{ message.content }}</p>
          <p class="text-xs mt-1 opacity-75">
            {{ formatTime(message.created_at) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Message Input -->
    <div class="bg-white border-t border-gray-200 p-4">
      <form @submit.prevent="sendMessage" class="flex space-x-4">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Type your message..."
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <button
          type="submit"
          class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Send
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  conversation: Object
})

const messages = ref([])
const newMessage = ref('')

const sendMessage = async () => {
  if (!newMessage.value.trim()) return

  try {
    const response = await fetch('/messenger/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        contact_id: props.conversation.contact.id,
        content: newMessage.value
      })
    })

    if (response.ok) {
      newMessage.value = ''
      // Refresh messages
      loadMessages()
    }
  } catch (error) {
    console.error('Error sending message:', error)
  }
}

const loadMessages = async () => {
  try {
    const response = await fetch(`/messenger/conversation/${props.conversation.id}`)
    const data = await response.json()
    messages.value = data.messages
  } catch (error) {
    console.error('Error loading messages:', error)
  }
}

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

onMounted(() => {
  loadMessages()
})
</script>
```

## Next Steps

1. **Run Migrations**: Create the missing database tables
2. **Configure Environment**: Set up your Telnyx API credentials
3. **Test Webhooks**: Use ngrok or similar to test webhook endpoints
4. **Style UI**: Customize the Tailwind CSS to match your design preferences
5. **Add Features**: Implement typing indicators, read receipts, and message search

## Testing

Test your implementation with:

```bash
# Run migrations
php artisan migrate

# Test webhook locally with ngrok
ngrok http 8000

# Update your Telnyx webhook URL to the ngrok URL
# Send test SMS from your Telnyx number
```

This completes the basic SMS messenger implementation. The system now supports:
- Two-way SMS messaging
- Contact management
- Conversation threads
- Webhook handling for inbound messages and delivery receipts
- Clean Vue.js UI with Tailwind CSS


send sms user assign numbers 
use messaging profile get from db which one user created 