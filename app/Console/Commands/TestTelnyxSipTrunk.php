<?php

namespace App\Console\Commands;

use App\Services\TelynxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestTelnyxSipTrunk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telnyx:test-sip-trunk {--create : Create a test SIP trunk} {--list : List existing SIP trunks} {--delete= : Delete a specific SIP trunk by ID} {--test-db-connections : Test database connections functionality}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telnyx SIP trunk functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telnyxService = new TelynxService();

        if ($this->option('create')) {
            $this->testCreateSipTrunk($telnyxService);
        } elseif ($this->option('list')) {
            $this->testListSipTrunks($telnyxService);
        } elseif ($deleteId = $this->option('delete')) {
            $this->testDeleteSipTrunk($telnyxService, $deleteId);
        } elseif ($this->option('test-db-connections')) {
            $this->testDatabaseConnections();
        } else {
            $this->info('Please specify an action: --create, --list, --delete=ID, or --test-db-connections');
            $this->info('Examples:');
            $this->info('  php artisan telnyx:test-sip-trunk --create');
            $this->info('  php artisan telnyx:test-sip-trunk --list');
            $this->info('  php artisan telnyx:test-sip-trunk --delete=conn_123456');
            $this->info('  php artisan telnyx:test-sip-trunk --test-db-connections');
        }
    }

    /**
     * Test SIP trunk creation
     */
    private function testCreateSipTrunk(TelynxService $telnyxService)
    {
        $this->info('Testing SIP trunk creation...');

        $testData = [
            'name' => 'Test SIP Trunk ' . now()->format('Y-m-d H:i:s'),
            'password' => 'test123password456',
            'user_name' => 'testuser' . rand(1000, 9999),
            'webhook_url' => 'https://example.com/webhook',
            'anchorsite_override' => 'Latency',
            'sip_uri_calling_preference' => 'disabled',
            'default_on_hold_comfort_noise_enabled' => false,
            'dtmf_type' => 'RFC 2833',
            'encode_contact_header_enabled' => true,
            'encrypted_media' => 'SRTP',
            'onnet_t38_passthrough_enabled' => true,
            'webhook_api_version' => '1',
            'webhook_timeout_secs' => 25,
            'rtcp_port' => 'rtcp-mux',
            'rtcp_capture_enabled' => true,
            'rtcp_report_frequency' => 10,
            'inbound_ani_format' => '+E.164',
            'inbound_dnis_format' => '+e164',
            'inbound_codecs' => 'G722',
            'inbound_routing_method' => 'sequential',
            'inbound_channel_limit' => 10,
            'inbound_ringback_tone' => true,
            'inbound_isup_headers' => true,
            'inbound_prack' => true,
            'inbound_sip_compact_headers' => true,
            'inbound_timeout_1xx' => 10,
            'inbound_timeout_2xx' => 20,
            'inbound_shaken_stir' => true,
            'outbound_call_parking' => true,
            'outbound_ani_override' => 'always',
            'outbound_channel_limit' => 10,
            'outbound_instant_ringback' => true,
            'outbound_ringback_tone' => true,
            'outbound_localization' => 'US',
            'outbound_t38_reinvite_source' => 'customer',
        ];

        try {
            $result = $telnyxService->createSipTrunk($testData);

            if ($result['success']) {
                $this->info('✅ SIP trunk created successfully!');
                $this->info('Connection ID: ' . $result['connection_id']);
                $this->info('SIP URI: ' . ($result['sip_uri'] ?? 'N/A'));
                $this->info('Status: ' . ($result['status'] ?? 'N/A'));
                
                // Store the connection ID for potential deletion
                $this->info('To delete this trunk, run: php artisan telnyx:test-sip-trunk --delete=' . $result['connection_id']);
            } else {
                $this->error('❌ Failed to create SIP trunk: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('SIP trunk creation test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test listing SIP trunks
     */
    private function testListSipTrunks(TelynxService $telnyxService)
    {
        $this->info('Testing SIP trunk listing...');

        try {
            $result = $telnyxService->listSipTrunks();

            if ($result['success']) {
                $this->info('✅ Found ' . $result['total'] . ' SIP trunks:');
                
                if (count($result['data']) > 0) {
                    $this->table(
                        ['ID', 'Name', 'Status', 'Active', 'SIP URI', 'Created'],
                        collect($result['data'])->map(function ($trunk) {
                            return [
                                $trunk['id'],
                                $trunk['connection_name'] ?? 'Unnamed',
                                $trunk['status'] ?? 'unknown',
                                $trunk['active'] ? 'Yes' : 'No',
                                $trunk['sip_uri'] ?? 'N/A',
                                $trunk['created_at'] ?? 'N/A',
                            ];
                        })->toArray()
                    );
                } else {
                    $this->info('No SIP trunks found.');
                }
            } else {
                $this->error('❌ Failed to list SIP trunks: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('SIP trunk listing test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test SIP trunk deletion
     */
    private function testDeleteSipTrunk(TelynxService $telnyxService, string $connectionId)
    {
        $this->info('Testing SIP trunk deletion for ID: ' . $connectionId);

        try {
            $result = $telnyxService->deleteSipTrunk($connectionId);

            if ($result['success']) {
                $this->info('✅ SIP trunk deleted successfully!');
                $this->info('Message: ' . $result['message']);
            } else {
                $this->error('❌ Failed to delete SIP trunk: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('SIP trunk deletion test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test database connections functionality
     */
    private function testDatabaseConnections()
    {
        $this->info('Testing database connections functionality...');

        try {
            // Get the first user
            $user = \App\Models\User::first();
            if (!$user) {
                $this->error('No users found in database. Please create a user first.');
                return;
            }

            $this->info("Testing with user: {$user->name} (ID: {$user->id})");

            // Test SIP trunks from database
            $sipTrunks = \App\Models\SipTrunk::where('user_id', $user->id)
                ->with(['phoneNumbers' => function($query) {
                    $query->wherePivot('is_active', true);
                }])
                ->get();

            $this->info("Found {$sipTrunks->count()} SIP trunks for user");

            foreach ($sipTrunks as $trunk) {
                $this->info("\n📞 SIP Trunk: {$trunk->name}");
                $this->info("   Status: {$trunk->status}");
                $this->info("   Connection Type: {$trunk->connection_type}");
                $this->info("   Phone Numbers: {$trunk->phoneNumbers->count()}");
                
                if ($trunk->phoneNumbers->count() > 0) {
                    foreach ($trunk->phoneNumbers as $phone) {
                        $this->info("     - {$phone->phone_number} ({$phone->pivot->assignment_type})");
                    }
                }

                if ($trunk->credentials) {
                    $this->info("   Has Credentials: ✅");
                } else {
                    $this->info("   Has Credentials: ❌");
                }
            }

            // Test the API endpoint
            $this->info("\n🔗 Testing API endpoint...");
            
            // Create a request context
            $request = new \Illuminate\Http\Request();
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            // Test the controller method
            $controller = new \App\Http\Controllers\TelnyxController();
            $response = $controller->listConnections();
            $data = json_decode($response->getContent(), true);

            if ($data['success']) {
                $this->info("✅ API endpoint working correctly");
                $this->info("   Returned {$data['connections']} connections");
            } else {
                $this->error("❌ API endpoint failed: " . ($data['error'] ?? 'Unknown error'));
            }

            $this->info("\n🎉 Database connections test completed successfully!");

        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
