<?php

namespace Database\Seeders;

use App\Models\SipTrunk;
use App\Models\PhoneNumber;
use App\Models\User;
use Illuminate\Database\Seeder;

class SipTrunkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create one
        $user = User::first();
        
        if (!$user) {
            $user = User::factory()->create();
        }

        // Create some sample phone numbers first
        $phoneNumbers = [];
        for ($i = 1; $i <= 3; $i++) {
            $phoneNumbers[] = PhoneNumber::create([
                'user_id' => $user->id,
                'phone_number' => '+1203720661' . $i,
                'country_code' => '1',
                'area_code' => '203',
                'city' => 'New Haven',
                'state' => 'CT',
                'carrier' => 'Telnyx',
                'number_type' => 'local',
                'monthly_rate' => 1.00,
                'setup_fee' => 0.00,
                'status' => 'purchased',
                'capabilities' => ['voice', 'sms'],
                'purchased_at' => now(),
                'metadata' => [
                    'seeded' => true,
                    'test_number' => $i
                ]
            ]);
        }

        // Create some sample SIP trunks
        $mainTrunk = SipTrunk::create([
            'user_id' => $user->id,
            'name' => 'Main Office SIP Trunk',
            'telnyx_connection_id' => 'conn_main_office_' . uniqid(),
            'connection_type' => 'credential',
            'status' => 'active',
            'sip_uri' => 'sip:mainoffice@telnyx.com',
            'webhook_url' => config('app.url') . '/webhook/telnyx',
            'credentials' => [
                'login' => 'TateAndrew1122',
                'password' => 'Toxic22211',
                'type' => 'credential'
            ],
            'settings' => [
                'codec' => 'G711',
                'dtmf_mode' => 'RFC2833',
                'rtp_timeout' => 30
            ],
            'notes' => 'Primary SIP trunk for main office operations',
            'activated_at' => now(),
            'last_health_check' => now(),
            'metadata' => [
                'location' => 'Main Office',
                'capacity' => '100 concurrent calls',
                'created_by' => 'seeder'
            ]
        ]);

        // Assign phone numbers to the main trunk
        $mainTrunk->phoneNumbers()->attach($phoneNumbers[0]->id, [
            'assignment_type' => 'primary',
            'is_active' => true,
            'assigned_at' => now(),
            'settings' => ['priority' => 'high']
        ]);

        $mainTrunk->phoneNumbers()->attach($phoneNumbers[1]->id, [
            'assignment_type' => 'secondary',
            'is_active' => true,
            'assigned_at' => now(),
            'settings' => ['priority' => 'medium']
        ]);

        $backupTrunk = SipTrunk::create([
            'user_id' => $user->id,
            'name' => 'Backup SIP Trunk',
            'telnyx_connection_id' => 'conn_backup_' . uniqid(),
            'connection_type' => 'credential',
            'status' => 'active',
            'sip_uri' => 'sip:backup@telnyx.com',
            'credentials' => [
                'login' => 'BackupUser123',
                'password' => 'BackupPass456',
                'type' => 'credential'
            ],
            'notes' => 'Backup SIP trunk for failover scenarios',
            'metadata' => [
                'location' => 'Backup Site',
                'capacity' => '50 concurrent calls',
                'created_by' => 'seeder'
            ]
        ]);

        // Assign phone number to backup trunk
        $backupTrunk->phoneNumbers()->attach($phoneNumbers[2]->id, [
            'assignment_type' => 'backup',
            'is_active' => true,
            'assigned_at' => now(),
            'settings' => ['priority' => 'low']
        ]);

        $webhookTrunk = SipTrunk::create([
            'user_id' => $user->id,
            'name' => 'Webhook SIP Trunk',
            'telnyx_connection_id' => 'conn_webhook_' . uniqid(),
            'connection_type' => 'webhook',
            'status' => 'pending',
            'webhook_url' => config('app.url') . '/webhook/sip',
            'notes' => 'SIP trunk configured for webhook-based call handling',
            'metadata' => [
                'webhook_type' => 'call_events',
                'created_by' => 'seeder'
            ]
        ]);

        $this->command->info('SIP Trunks seeded successfully with phone number assignments!');
        $this->command->info('Created ' . count($phoneNumbers) . ' phone numbers');
        $this->command->info('Created 3 SIP trunks with different configurations');
    }
}
