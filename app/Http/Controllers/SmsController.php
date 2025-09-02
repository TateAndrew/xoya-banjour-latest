<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\MessagingProfile;
use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SmsController extends Controller
{
    protected $telnyxService;

    public function __construct(TelnyxService $telnyxService)
    {
        $this->telnyxService = $telnyxService;
    }

    /**
     * Display the messenger index page.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's phone numbers that are assigned to messaging profiles
        $userPhoneNumbers = PhoneNumber::where('user_id', $user->id)
            ->where('status', 'assigned')
            ->whereNotNull('messaging_profile_id')
            ->with(['messagingProfile'])
            ->get();
            
        // Get conversations for the user's phone numbers
        $conversations = Conversation::whereIn('sender_number', $userPhoneNumbers->pluck('phone_number'))
            ->with(['contact', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get();
        return Inertia::render('Messenger/Index', [
            'conversations' => $conversations,
            'userPhoneNumbers' => $userPhoneNumbers,
            'hasPhoneNumbers' => $userPhoneNumbers->count() > 0
        ]);
    }

    /**
     * Display a specific conversation.
     */
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

    /**
     * Send a new message.
     */
    public function sendMessage(Request $request)
    {   
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'content' => 'required|string|max:1600',
            'from_phone_number_id' => 'required|exists:phone_numbers,id'
        ]);
        $user = Auth::user();
        $contact = Contact::findOrFail($request->contact_id);
        // Get the user's phone number with messaging profile
        $phoneNumber = PhoneNumber::where('user_id', $user->id)
            ->where('id', $request->from_phone_number_id)
            ->where('status', 'assigned')
            ->whereNotNull('messaging_profile_id')
            ->with(['messagingProfile'])
            ->firstOrFail();
      
        // Get or create conversation
        $conversation = Conversation::firstOrCreate([
            'contact_id' => $contact->id,
            'sender_number' => $phoneNumber->phone_number
        ]);
        
        // Create message record
        $message = $conversation->messages()->create([
            'direction' => Message::DIRECTION_OUTBOUND,
            'content' => $request->content,
            'status' => Message::STATUS_QUEUED
        ]);

        // Send via Telnyx using user's phone number and messaging profile
        // $response = $this->telnyxService->sendSms(
        //     $contact->phone_e164,
        //     $request->content,
        //     $phoneNumber->phone_number,
        //     $phoneNumber->messagingProfile->telnyx_profile_id
        // );

        // if ($response && isset($response['data']['id'])) {
        //     $message->update([
        //         'telnyx_message_id' => $response['data']['id'],
        //         'status' => Message::STATUS_SENDING
        //     ]);
        // }
        
        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => 0
        ]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * Get all contacts.
     */
    public function getContacts()
    {
        $contacts = Contact::orderBy('name')->get();
        return response()->json($contacts);
    }

    /**
     * Store a new contact.
     */
    public function storeContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'phone_e164' => 'required|string|unique:contacts,phone_e164'
        ]);

        $contact = Contact::create($request->all());
        return response()->json($contact);
    }

    /**
     * Start a new conversation (create contact and send first message).
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'phone_e164' => 'required|string',
            'message' => 'required|string|max:1600',
            'from_phone_number_id' => 'required|exists:phone_numbers,id'
        ]);
      
        $user = Auth::user();
        
        // Get the user's phone number with messaging profile
        $phoneNumber = PhoneNumber::where('user_id', $user->id)
            ->where('id', $request->from_phone_number_id)
            ->where('status', 'assigned')
            ->whereNotNull('messaging_profile_id')
            ->with(['messagingProfile'])
            ->firstOrFail();
        // Create or get contact
        $contact = Contact::firstOrCreate(
            ['phone_e164' => $request->phone_e164],
            ['name' => $request->name]
        );
        
        // Get or create conversation
        $conversation = Conversation::firstOrCreate([
            'contact_id' => $contact->id,
            'sender_number' => $phoneNumber->phone_number
        ]);
        
        // Create message record
        $message = $conversation->messages()->create([
            'direction' => Message::DIRECTION_OUTBOUND,
            'content' => $request->message,
            'status' => Message::STATUS_QUEUED
        ]);
        
        // Send via Telnyx using user's phone number and messaging profile
        // $response = $this->telnyxService->sendSms(
        //     $contact->phone_e164,
        //     $request->message,
        //     $phoneNumber->phone_number,
        //     $phoneNumber->messagingProfile->telnyx_profile_id
        // );

        // if ($response && isset($response['data']['id'])) {
        //     $message->update([
        //         'telnyx_message_id' => $response['data']['id'],
        //         'status' => Message::STATUS_SENDING
        //     ]);
        // }
        
        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => 0
        ]);

        return response()->json([
            'success' => true, 
            'conversation' => $conversation->load(['contact', 'lastMessage']),
            'message' => $message
        ]);
    }

    /**
     * Mark a conversation as read.
     */
    public function markAsRead(Conversation $conversation)
    {
        $conversation->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Get conversation messages for API.
     */
    public function getMessages(Conversation $conversation)
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Search contacts.
     */
    public function searchContacts(Request $request)
    {
        $query = $request->get('q');
        
        $contacts = Contact::where('name', 'like', "%{$query}%")
            ->orWhere('phone_e164', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($contacts);
    }

    /**
     * Get conversations as JSON for API consumption.
     */
    public function getConversations()
    {
        $user = Auth::user();
        
        // Get user's phone numbers
        $userPhoneNumbers = PhoneNumber::where('user_id', $user->id)
            ->where('status', 'assigned')
            ->whereNotNull('messaging_profile_id')
            ->pluck('phone_number');
            
        $conversations = Conversation::whereIn('sender_number', $userPhoneNumbers)
            ->with(['contact', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return response()->json($conversations);
    }
}
