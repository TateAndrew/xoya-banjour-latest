<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PhoneNumber;
use App\Services\TelynxService;
use Illuminate\Support\Facades\Log;

class AssignPhoneNumberToFirstUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first registered user
        $user = User::first();
        
        if (!$user) {
            $this->command->error('No users found in the database. Please create a user first.');
            return;
        }

        $this->command->info("Assigning phone number to user: {$user->name} ({$user->email})");

        // Initialize Telnyx service
        $telnyxService = new TelynxService();

        try {
            // Get the user's existing phone numbers from Telnyx
            $phoneNumbers = \Telnyx\PhoneNumber::all([
                'limit' => 10
            ]);

            if (empty($phoneNumbers->data)) {
                $this->command->error('No phone numbers found in Telnyx account.');
                return;
            }

            // Get the first available phone number
            $telnyxNumber = $phoneNumbers->data[0];
            
            $this->command->info("Found phone number: {$telnyxNumber->phone_number}");

            // Check if this phone number is already in our database
            $existingNumber = PhoneNumber::where('phone_number', $telnyxNumber->phone_number)->first();
            
            if ($existingNumber) {
                $this->command->warn("Phone number {$telnyxNumber->phone_number} already exists in database.");
                return;
            }

            // Create phone number record in our database
            $phoneNumber = PhoneNumber::create([
                'user_id' => $user->id,
                'phone_number' => $telnyxNumber->phone_number,
                'country_code' => $telnyxNumber->country_iso_alpha2 ?? 'US',
                'area_code' => $this->extractAreaCode($telnyxNumber->phone_number),
                'city' => $telnyxNumber->locality ?? null,
                'state' => $telnyxNumber->administrative_area ?? null,
                'carrier' => null,
                'number_type' => $telnyxNumber->phone_number_type ?? 'local',
                'monthly_rate' => 0, // Will be updated from Telnyx if available
                'setup_fee' => 0, // Will be updated from Telnyx if available
                'telynx_id' => $telnyxNumber->id,
                'status' => $telnyxNumber->status ?? 'purchased',
                'capabilities' => $this->extractCapabilities($telnyxNumber),
                'purchased_at' => $telnyxNumber->purchased_at ? \Carbon\Carbon::parse($telnyxNumber->purchased_at) : now(),
                'expires_at' => null,
                'metadata' => [
                    'connection_id' => $telnyxNumber->connection_id,
                    'connection_name' => $telnyxNumber->connection_name,
                    'messaging_profile_id' => $telnyxNumber->messaging_profile_id,
                    'messaging_profile_name' => $telnyxNumber->messaging_profile_name,
                    'voice_product_id' => $telnyxNumber->voice_product_id,
                    'billing_group_id' => $telnyxNumber->billing_group_id,
                    'emergency_enabled' => $telnyxNumber->emergency_enabled,
                    'emergency_address_id' => $telnyxNumber->emergency_address_id,
                    'emergency_status' => $telnyxNumber->emergency_status,
                    'call_forwarding_enabled' => $telnyxNumber->call_forwarding_enabled,
                    'cnam_listing_enabled' => $telnyxNumber->cnam_listing_enabled,
                    'call_recording_enabled' => $telnyxNumber->call_recording_enabled,
                    't38_fax_gateway_enabled' => $telnyxNumber->t38_fax_gateway_enabled,
                    'hd_voice_enabled' => $telnyxNumber->hd_voice_enabled,
                    'noise_suppression' => $telnyxNumber->noise_suppression,
                    'number_level_routing' => $telnyxNumber->number_level_routing,
                    'source_type' => $telnyxNumber->source_type,
                    'release_in_progress' => $telnyxNumber->release_in_progress,
                ]
            ]);

            $this->command->info("✅ Successfully assigned phone number {$telnyxNumber->phone_number} to user {$user->name}");

        } catch (\Exception $e) {
            $this->command->error("Error assigning phone number: " . $e->getMessage());
            Log::error("Phone number assignment error: " . $e->getMessage());
        }
    }

    /**
     * Extract area code from phone number
     */
    private function extractAreaCode(string $phoneNumber): ?string
    {
        // Remove +1 and extract area code (first 3 digits after country code)
        $cleanNumber = preg_replace('/^\+1/', '', $phoneNumber);
        if (strlen($cleanNumber) >= 3) {
            return substr($cleanNumber, 0, 3);
        }
        return null;
    }

    /**
     * Extract capabilities from Telnyx phone number object
     */
    private function extractCapabilities($telnyxNumber): array
    {
        $capabilities = [];
        
        // Check for voice capabilities
        if ($telnyxNumber->voice_enabled ?? false) {
            $capabilities[] = 'voice';
        }
        
        // Check for SMS capabilities
        if ($telnyxNumber->sms_enabled ?? false) {
            $capabilities[] = 'sms';
        }
        
        // Check for MMS capabilities
        if ($telnyxNumber->mms_enabled ?? false) {
            $capabilities[] = 'mms';
        }
        
        // Check for fax capabilities
        if ($telnyxNumber->t38_fax_gateway_enabled ?? false) {
            $capabilities[] = 'fax';
        }
        
        // Check for emergency capabilities
        if ($telnyxNumber->emergency_enabled ?? false) {
            $capabilities[] = 'emergency';
        }
        
        // If no specific capabilities found, assume basic voice/sms
        if (empty($capabilities)) {
            $capabilities = ['voice', 'sms'];
        }
        
        return $capabilities;
    }
}
