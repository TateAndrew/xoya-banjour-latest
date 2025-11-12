<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\MessagingProfile;
use App\Models\User;
use App\Services\TelnyxService;
use App\Events\MessageSent;
use App\Events\MessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
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
        // dd($userPhoneNumbers);
                
        $userPhoneNumbersList = $userPhoneNumbers->pluck('phone_number')->toArray();
        
        // Get conversations
        $conversations = Conversation::where(function($query) use ($userPhoneNumbersList) {
                $query->whereIn('sender_number', $userPhoneNumbersList);
            })
            ->with([
                'contact',
                'messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->take(1);
                }
            ])
            ->orderBy('last_message_at', 'desc')
            ->get();

        $conversations = $this->enrichConversations($conversations, $userPhoneNumbersList);

        return Inertia::render('Messenger/Modern', [
            'conversations' => $conversations->values(),
            'userPhoneNumbers' => $userPhoneNumbers,
            'hasPhoneNumbers' => $userPhoneNumbers->count() > 0
        ]);
    }

    /**
     * Display a specific conversation.
     */
    public function showConversation(Conversation $conversation)
    {
        $user = Auth::user();
        
        // Get user's phone numbers to check if contact is internal
        $userPhoneNumbers = PhoneNumber::where('user_id', $user->id)
            ->where('status', 'assigned')
            ->pluck('phone_number')
            ->toArray();
        
        // Load conversation with contact
        $conversation->load('contact');
        
        // Check if contact is actually another user in the portal
        $contactIsYourNumber = in_array($conversation->contact->phone_e164, $userPhoneNumbers);
        
        // Get all messages with direction info
        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($conversation, $contactIsYourNumber) {
                
                // If contact is your number (someone sent TO you), reverse the logic
                if ($contactIsYourNumber) {
                    $message->is_from_user = $message->direction === Message::DIRECTION_INBOUND;
                    $message->is_from_contact = $message->direction === Message::DIRECTION_OUTBOUND;
                    $message->sender_name = $message->is_from_contact ? $conversation->sender_number : 'You';
                } else {
                    // Normal case: contact is external
                    $message->is_from_user = $message->direction === Message::DIRECTION_OUTBOUND;
                    $message->is_from_contact = $message->direction === Message::DIRECTION_INBOUND;
                    $message->sender_name = $message->is_from_user ? 'You' : ($conversation->contact->name ?? $conversation->contact->phone_e164);
                }
                
                return $message;
            });
        // Add display name for the conversation
        if ($contactIsYourNumber) {
            $conversation->display_name = $conversation->sender_number;
            $conversation->contact_is_internal = true;
        } else {
            $conversation->display_name = $conversation->contact->name ?? $conversation->contact->phone_e164;
            $conversation->contact_is_internal = false;
        }

        return Inertia::render('Messenger/Conversation', [
            'conversation' => $conversation,
            'messages' => $messages
        ]);
    }

    /**
     * Send a new message.
     */
    public function sendMessage(Request $request)
    {   
        try {
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
            
            // Check if the recipient (contact) is also a user in the portal
            // Normalize the contact phone number for comparison
            $contactE164Clean = preg_replace('/[^0-9]/', '', $contact->phone_e164);
            
            $recipientPhoneNumber = PhoneNumber::where(function($query) use ($contact, $contactE164Clean) {
                    $query->where('phone_number', $contact->phone_e164)
                          ->orWhere('phone_number', '+' . $contactE164Clean)
                          ->orWhereRaw('REPLACE(REPLACE(REPLACE(phone_number, "+", ""), "-", ""), " ", "") = ?', [$contactE164Clean]);
                })
                ->where('status', 'assigned')
                ->first();
                

            $isInternalMessage = !is_null($recipientPhoneNumber);

            if ($isInternalMessage) {
                // Internal message - don't use Telnyx API
                Log::info('Internal message detected - skipping Telnyx API', [
                    'sender' => $phoneNumber->phone_number,
                    'recipient' => $contact->phone_e164
                ]);

                // Create message record with delivered status (no external SMS needed)
                $message = $conversation->messages()->create([
                    'direction' => Message::DIRECTION_OUTBOUND,
                    'content' => $request->content,
                    'status' => Message::STATUS_DELIVERED,
                    'sent_at' => now(),
                    'delivered_at' => now()
                ]);

                // Create a contact for the sender (from recipient's perspective)
                $senderContact = Contact::firstOrCreate(
                    ['phone_e164' => $phoneNumber->phone_number],
                    ['name' => null]
                );

                // Create reciprocal conversation (recipient sees sender as contact)
                $reciprocalConversation = Conversation::firstOrCreate([
                    'contact_id' => $senderContact->id,
                    'sender_number' => $contact->phone_e164
                ]);

                // Create the same message in the reciprocal conversation (from recipient's view it's inbound)
                $reciprocalMessage = $reciprocalConversation->messages()->create([
                    'direction' => Message::DIRECTION_INBOUND, // For recipient, it's inbound
                    'content' => $request->content,
                    'status' => Message::STATUS_DELIVERED,
                    'sent_at' => now(),
                    'delivered_at' => now()
                ]);

                // Update reciprocal conversation
                $reciprocalConversation->update([
                    'last_message_at' => now(),
                    'unread_count' => $reciprocalConversation->unread_count + 1
                ]);

                Log::info('Reciprocal conversation created for internal message', [
                    'conversation_id' => $reciprocalConversation->id,
                    'message_id' => $reciprocalMessage->id
                ]);
                // Broadcast to recipient (internal user)
                event(new MessageReceived($reciprocalMessage, $reciprocalConversation, $recipientPhoneNumber->user_id));
                // broadcast(new MessageReceived($reciprocalMessage, $reciprocalConversation, $recipientPhoneNumber->user_id))->toOthers();
            } else {
                // External message - use Telnyx API
                Log::info('External message - using Telnyx API', [
                    'sender' => $phoneNumber->phone_number,
                    'recipient' => $contact->phone_e164
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
                //     $phoneNumber->e164, // Use E.164 formatted number
                //     $phoneNumber->messagingProfile->telnyx_profile_id
                // );
                
                // if ($response && $response['success'] && isset($response['data']['data']['id'])) {
                //     $message->update([
                //         'telnyx_message_id' => $response['data']['data']['id'],
                //         'status' => Message::STATUS_SENDING
                //     ]);
                // } else {
                //     // If no valid response, mark message as failed
                //     $message->update([
                //         'status' => Message::STATUS_FAILED
                //     ]);
                    
                //     $errorMessage = isset($response['error']) 
                //         ? 'Failed to send message: ' . $response['error']
                //         : 'Failed to send message. Invalid response from Telnyx.';
                    
                //     return response()->json([
                //         'success' => false,
                //         'message' => $errorMessage
                //     ], 500);
                // }
            }
            
            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
                'unread_count' => 0
            ]);

            // Broadcast to sender
            event(new MessageSent($message, $conversation));

            return response()->json(['success' => true, 'message' => $message]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Required resource not found. Please ensure you have a phone number with an active messaging profile.'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('SMS send error: ' . $e->getMessage(), [
                'contact_id' => $request->contact_id ?? null,
                'from_phone_number_id' => $request->from_phone_number_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If message was created, mark it as failed
            if (isset($message)) {
                $message->update(['status' => Message::STATUS_FAILED]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the message: ' . $e->getMessage()
            ], 500);
        }
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
     * Get internal users with messaging-enabled phone numbers.
     */
    public function getInternalUsers(Request $request)
    {
        $search = $request->get('search', $request->get('q'));

        $users = User::query()
            ->whereHas('phoneNumbers', function ($query) {
                $query->where('status', 'assigned')
                      ->whereNotNull('messaging_profile_id');
            })
            ->with(['phoneNumbers' => function ($query) {
                $query->where('status', 'assigned')
                      ->whereNotNull('messaging_profile_id')
                      ->orderBy('phone_number');
            }]);

        if (!empty($search)) {
            $users->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $results = $users->orderBy('name')
            ->limit(25)
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_numbers' => $user->phoneNumbers->map(function (PhoneNumber $phoneNumber) {
                        return [
                            'id' => $phoneNumber->id,
                            'phone_number' => $phoneNumber->phone_number,
                            'e164' => $phoneNumber->e164,
                            'messaging_profile' => $phoneNumber->messagingProfile ? [
                                'id' => $phoneNumber->messagingProfile->id,
                                'name' => $phoneNumber->messagingProfile->name,
                            ] : null,
                        ];
                    })->values(),
                ];
            });

        return response()->json($results);
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
        $contactMode = $request->input('contact_mode', $request->input('contactMode', 'new'));

        try {
            $rules = [
                'contact_mode' => 'nullable|string|in:new,internal',
                'message' => 'required|string|max:1600',
                'from_phone_number_id' => 'required|exists:phone_numbers,id',
            ];

            if ($contactMode === 'internal') {
                $rules['internal_user_id'] = 'required|exists:users,id';
                $rules['internal_user_phone_number_id'] = 'nullable|exists:phone_numbers,id';
            } else {
                $rules['name'] = 'nullable|string|max:191';
                $rules['phone_e164'] = 'required|string';
            }

            $validated = $request->validate($rules);
          
            $user = Auth::user();
            
            // Get the user's phone number with messaging profile
            $phoneNumber = PhoneNumber::where('user_id', $user->id)
                ->where('id', $validated['from_phone_number_id'])
                ->where('status', 'assigned')
                ->whereNotNull('messaging_profile_id')
                ->with(['messagingProfile'])
                ->firstOrFail();

            $isInternalMode = $contactMode === 'internal';
            $contactName = $validated['name'] ?? null;
            $contactPhoneE164 = $validated['phone_e164'] ?? null;
            $recipientPhoneNumber = null;
            $targetInternalUser = null;

            if ($isInternalMode) {
                $targetInternalUser = User::with(['phoneNumbers' => function($query) {
                        $query->where('status', 'assigned')
                              ->whereNotNull('messaging_profile_id');
                    }])
                    ->findOrFail($validated['internal_user_id']);

                if ($targetInternalUser->phoneNumbers->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected user does not have an assigned messaging phone number.'
                    ], 422);
                }

                if (!empty($validated['internal_user_phone_number_id'])) {
                    $recipientPhoneNumber = $targetInternalUser->phoneNumbers
                        ->firstWhere('id', (int) $validated['internal_user_phone_number_id']);

                    if (!$recipientPhoneNumber) {
                        return response()->json([
                            'success' => false,
                            'message' => 'The selected phone number does not belong to the specified user.'
                        ], 422);
                    }
                } else {
                    $recipientPhoneNumber = $targetInternalUser->phoneNumbers->first();
                }

                if (!$recipientPhoneNumber) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to determine a destination phone number for the selected user.'
                    ], 422);
                }

                $contactPhoneE164 = $recipientPhoneNumber->phone_number ?? $recipientPhoneNumber->e164;
                $contactName = $targetInternalUser->name;
            }
            
            // Create or get contact
            $contact = Contact::firstOrCreate(
                ['phone_e164' => $contactPhoneE164],
                ['name' => $contactName]
            );

            if ($isInternalMode && $targetInternalUser && $targetInternalUser->name && $contact->name !== $targetInternalUser->name) {
                $contact->name = $targetInternalUser->name;
                $contact->save();
            }
            
            // Get or create conversation
            $conversation = Conversation::firstOrCreate([
                'contact_id' => $contact->id,
                'sender_number' => $phoneNumber->phone_number
            ]);
            
            // Check if the recipient (contact) is also a user in the portal
            // Normalize the contact phone number for comparison
            $contactE164Clean = preg_replace('/[^0-9]/', '', $contact->phone_e164);
            
            if (!$recipientPhoneNumber) {
                $recipientPhoneNumber = PhoneNumber::where(function($query) use ($contact, $contactE164Clean) {
                        $query->where('phone_number', $contact->phone_e164)
                              ->orWhere('phone_number', '+' . $contactE164Clean)
                              ->orWhereRaw('REPLACE(REPLACE(REPLACE(phone_number, "+", ""), "-", ""), " ", "") = ?', [$contactE164Clean]);
                    })
                    ->where('status', 'assigned')
                    ->first();
            }
                
            Log::info('Start conversation - checking if recipient is internal user', [
                'contact_phone' => $contact->phone_e164,
                'found_recipient' => $recipientPhoneNumber ? 'yes' : 'no',
                'recipient_id' => $recipientPhoneNumber?->id,
                'contact_mode' => $contactMode
            ]);

            $isInternalMessage = !is_null($recipientPhoneNumber);

            if ($isInternalMessage) {
                // Internal message - don't use Telnyx API
                Log::info('Internal message detected - skipping Telnyx API', [
                    'sender' => $phoneNumber->phone_number,
                    'recipient' => $contact->phone_e164,
                    'recipient_user_id' => $recipientPhoneNumber->user_id
                ]);

                // Create message record with delivered status (no external SMS needed)
                $message = $conversation->messages()->create([
                    'direction' => Message::DIRECTION_OUTBOUND,
                    'content' => $validated['message'],
                    'status' => Message::STATUS_DELIVERED,
                    'sent_at' => now(),
                    'delivered_at' => now()
                ]);

                // Create a contact for the sender (from recipient's perspective)
                $senderContact = Contact::firstOrCreate(
                    ['phone_e164' => $phoneNumber->phone_number],
                    ['name' => null]
                );

                // Create reciprocal conversation (recipient sees sender as contact)
                $reciprocalConversation = Conversation::firstOrCreate([
                    'contact_id' => $senderContact->id,
                    'sender_number' => $contact->phone_e164
                ]);

                // Create the same message in the reciprocal conversation (from recipient's view it's inbound)
                $reciprocalMessage = $reciprocalConversation->messages()->create([
                    'direction' => Message::DIRECTION_INBOUND, // For recipient, it's inbound
                    'content' => $validated['message'],
                    'status' => Message::STATUS_DELIVERED,
                    'sent_at' => now(),
                    'delivered_at' => now()
                ]);

                // Update reciprocal conversation
                $reciprocalConversation->update([
                    'last_message_at' => now(),
                    'unread_count' => $reciprocalConversation->unread_count + 1
                ]);

                Log::info('Reciprocal conversation created for internal message', [
                    'conversation_id' => $reciprocalConversation->id,
                    'message_id' => $reciprocalMessage->id
                ]);

                // Broadcast to recipient (internal user)
                event(new MessageReceived($reciprocalMessage, $reciprocalConversation, $recipientPhoneNumber->user_id));
        
            } else {
                // External message - use Telnyx API
                Log::info('External message - using Telnyx API', [
                    'sender' => $phoneNumber->phone_number,
                    'recipient' => $contact->phone_e164
                ]);

                // Create message record
                $message = $conversation->messages()->create([
                    'direction' => Message::DIRECTION_OUTBOUND,
                    'content' => $validated['message'],
                    'status' => Message::STATUS_QUEUED
                ]);
                
                $response = $this->telnyxService->sendSms(
                    $contact->phone_e164,
                    $validated['message'],
                    $phoneNumber->e164, // Use E.164 formatted number
                    $phoneNumber->messagingProfile->telnyx_profile_id
                );

                if ($response && $response['success'] && isset($response['data']['data']['id'])) {
                    $message->update([
                        'telnyx_message_id' => $response['data']['data']['id'],
                        'status' => Message::STATUS_SENDING
                    ]);
                } else {
                    // If no valid response, mark message as failed
                    $message->update([
                        'status' => Message::STATUS_FAILED
                    ]);
                    
                    $errorMessage = isset($response['error']) 
                        ? 'Failed to send message: ' . $response['error']
                        : 'Failed to send message. Invalid response from Telnyx.';
                    
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 500);
                }
            }
                
            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
                'unread_count' => 0
            ]);

            // Broadcast to sender
            event(new MessageSent($message, $conversation));

            $conversation->load([
                'contact',
                'messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->take(1);
                }
            ]);

            $userPhoneNumbersList = PhoneNumber::where('user_id', $user->id)
                ->where('status', 'assigned')
                ->whereNotNull('messaging_profile_id')
                ->pluck('phone_number')
                ->toArray();

            $preparedConversation = $this->enrichConversations(collect([$conversation]), $userPhoneNumbersList)->first();

            return response()->json([
                'success' => true, 
                'conversation' => $preparedConversation,
                'message' => $message
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found or not assigned to a messaging profile.'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Start conversation error: ' . $e->getMessage(), [
                'phone_e164' => $request->phone_e164 ?? null,
                'from_phone_number_id' => $request->from_phone_number_id ?? null,
                'internal_user_id' => $request->internal_user_id ?? null,
                'contact_mode' => $contactMode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If message was created, mark it as failed
            if (isset($message)) {
                $message->update(['status' => Message::STATUS_FAILED]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting conversation: ' . $e->getMessage()
            ], 500);
        }
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
    public function getMessages(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        
        // Get pagination parameters
        $page = $request->input('page', 1);
        $perPage = min($request->input('per_page', 20), 100); // Max 100 per page
        
        // Get user's phone numbers to check if contact is internal
        $userPhoneNumbers = PhoneNumber::where('user_id', $user->id)
            ->where('status', 'assigned')
            ->pluck('phone_number')
            ->toArray();
        // Load conversation with contact
        $conversation->load('contact');
        
        // Check if contact is actually another user in the portal
        $contactIsYourNumber = in_array($conversation->contact->phone_e164, $userPhoneNumbers);
        
        // Get total count
        $totalMessages = $conversation->messages()->count();
        
        // Calculate pagination for DESC order (newest first in DB, but we'll reverse for display)
        $offset = ($page - 1) * $perPage;
        
        // Get messages ordered by created_at DESC (newest first), then we'll reverse
        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get()
            ->reverse() // Reverse to get chronological order (oldest to newest)
            ->values() // Reset array keys
            ->map(function($message) use ($conversation, $contactIsYourNumber) {
                // If contact is your number (someone sent TO you), reverse the logic
                if ($contactIsYourNumber) {
                    $message->is_from_user = $message->direction === Message::DIRECTION_INBOUND;
                    $message->is_from_contact = $message->direction === Message::DIRECTION_OUTBOUND;
                    $message->sender_name = $message->is_from_contact ? $conversation->sender_number : 'You';
                } else {
                    // Normal case: contact is external
                    $message->is_from_user = $message->direction === Message::DIRECTION_OUTBOUND;
                    $message->is_from_contact = $message->direction === Message::DIRECTION_INBOUND;
                    $message->sender_name = $message->is_from_user ? 'You' : ($conversation->contact->name ?? $conversation->contact->phone_e164);
                }
                
                return $message;
            });

        $hasMore = ($offset + $perPage) < $totalMessages;

        return response()->json([
            'messages' => $messages,
            'contact_is_internal' => $contactIsYourNumber,
            'display_name' => $contactIsYourNumber ? $conversation->sender_number : ($conversation->contact->name ?? $conversation->contact->phone_e164),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalMessages,
                'has_more' => $hasMore
            ],
            'has_more' => $hasMore
        ]);
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
            ->pluck('phone_number')
            ->toArray();
        
        Log::info('API: Loading conversations for user', [
            'user_id' => $user->id,
            'user_numbers' => $userPhoneNumbers
        ]);
            
        // Get conversations where user is either the sender OR the recipient
        $conversations = Conversation::where(function($query) use ($userPhoneNumbers) {
                // Conversations you initiated
                $query->whereIn('sender_number', $userPhoneNumbers);
                    // OR conversations sent to you (contact is your number)
                    // ->orWhereHas('contact', function($q) use ($userPhoneNumbers) {
                    //     $q->whereIn('phone_e164', $userPhoneNumbers);
                    // });
            })
            ->with([
                'contact',
                'messages' => function($query) {
                    $query->orderBy('created_at', 'desc')->take(1);
                }
            ])
            ->orderBy('last_message_at', 'desc')
            ->get();
            
        Log::info('API: Found conversations', [
            'count' => $conversations->count()
        ]);

        $conversations = $this->enrichConversations($conversations, $userPhoneNumbers);

        return response()->json($conversations->values());
    }

    /**
     * Enrich conversation collection with additional metadata and normalized relations.
     */
    protected function enrichConversations(Collection $conversations, array $userPhoneNumbersList): Collection
    {
        $internalPhoneCache = [];

        return $conversations->map(function (Conversation $conversation) use (&$internalPhoneCache, $userPhoneNumbersList) {
            $lastMessage = null;

            if ($conversation->relationLoaded('messages') && $conversation->messages->isNotEmpty()) {
                $lastMessage = $conversation->messages->first();
                $conversation->setRelation('messages', collect([$lastMessage]));
            } elseif ($conversation->relationLoaded('lastMessage') && $conversation->lastMessage) {
                $lastMessage = $conversation->lastMessage;
                $conversation->unsetRelation('lastMessage');
            } elseif (isset($conversation->last_message)) {
                $lastMessage = $conversation->last_message;
            }

            $conversation->setAttribute('last_message', $lastMessage);

            $contact = $conversation->contact;

            if ($contact && $contact->phone_e164) {
                $contactPhone = $contact->phone_e164;
                $normalized = preg_replace('/[^0-9]/', '', $contactPhone);

                if ($normalized !== null && $normalized !== '') {
                    if (!array_key_exists($normalized, $internalPhoneCache)) {
                        $internalPhoneCache[$normalized] = PhoneNumber::with('user')
                            ->where(function($query) use ($contactPhone, $normalized) {
                                $query->where('phone_number', $contactPhone)
                                    ->orWhere('phone_number', '+' . $normalized)
                                    ->orWhereRaw('REPLACE(REPLACE(REPLACE(phone_number, "+", ""), "-", ""), " ", "") = ?', [$normalized]);
                            })
                            ->where('status', 'assigned')
                            ->first();
                    }

                    $internalPhoneNumber = $internalPhoneCache[$normalized];
                    if ($internalPhoneNumber && $internalPhoneNumber->user) {
                        $user = $internalPhoneNumber->user;
                        $contact->setAttribute('username', $user->name);
                        $contact->setAttribute('email', $user->email);
                        $contact->setAttribute('user', [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ]);

                        if (empty($contact->name)) {
                            $contact->name = $user->name;
                        }
                    }
                }

                $contactIsYourNumber = in_array($contact->phone_e164, $userPhoneNumbersList, true);
                $conversation->contact_is_internal = $contactIsYourNumber;
                $conversation->display_name = $contactIsYourNumber
                    ? $conversation->sender_number
                    : ($contact->name ?? $contact->phone_e164);
            } else {
                $conversation->contact_is_internal = false;
                $conversation->display_name = $conversation->sender_number;
            }

            return $conversation;
        });
    }
}
