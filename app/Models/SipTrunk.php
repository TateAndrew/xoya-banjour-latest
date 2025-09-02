<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SipTrunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'telnyx_connection_id',
        'sip_uri',
        'webhook_url',
        'status',
        'credentials',
        'settings',
        'metadata',
        'activated_at',
        'last_health_check',
        'notes',
    ];

    protected $casts = [
        'credentials' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'last_health_check' => 'datetime',
    ];

    protected $dates = [
        'activated_at',
        'last_health_check',
    ];

    /**
     * Get the user that owns the SIP trunk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the phone numbers associated with this SIP trunk
     */
    public function phoneNumbers(): BelongsToMany
    {
        return $this->belongsToMany(PhoneNumber::class, 'sip_trunk_phone_number')
                    ->withPivot(['assignment_type', 'is_active', 'assigned_at', 'last_used_at', 'settings'])
                    ->withTimestamps();
    }

    /**
     * Get the calls made through this SIP trunk
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'sip_trunk_id');
    }

    /**
     * Scope for active trunks
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for user's trunks
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the trunk is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Activate the trunk
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    /**
     * Deactivate the trunk
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'activated_at' => null,
        ]);
    }

    /**
     * Update health check timestamp
     */
    public function updateHealthCheck(): void
    {
        $this->update(['last_health_check' => now()]);
    }

    /**
     * Get the password from credentials
     */
    public function getPassword(): ?string
    {
        return $this->credentials['password'] ?? null;
    }

    /**
     * Get the username from credentials
     */
    public function getUsername(): ?string
    {
        return $this->credentials['user_name'] ?? null;
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Get all inbound settings
     */
    public function getInboundSettings(): array
    {
        $inboundKeys = [
            'ani_format', 'dnis_format', 'codecs', 'routing_method', 'channel_limit',
            'ringback_tone', 'isup_headers', 'prack', 'sip_compact_headers',
            'timeout_1xx', 'timeout_2xx', 'shaken_stir'
        ];

        $settings = [];
        foreach ($inboundKeys as $key) {
            $value = $this->getSetting('inbound_' . $key);
            if ($value !== null) {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * Get all outbound settings
     */
    public function getOutboundSettings(): array
    {
        $outboundKeys = [
            'call_parking', 'ani_override', 'channel_limit', 'instant_ringback',
            'ringback_tone', 'localization', 't38_reinvite_source'
        ];

        $settings = [];
        foreach ($outboundKeys as $key) {
            $value = $this->getSetting('outbound_' . $key);
            if ($value !== null) {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * Get webhook settings
     */
    public function getWebhookSettings(): array
    {
        return [
            'url' => $this->webhook_url,
            'failover_url' => $this->getSetting('webhook_failover_url'),
            'api_version' => $this->getSetting('webhook_api_version'),
            'timeout_secs' => $this->getSetting('webhook_timeout_secs'),
        ];
    }

    /**
     * Get RTCP settings
     */
    public function getRtcpSettings(): array
    {
        return [
            'port' => $this->getSetting('rtcp_port'),
            'capture_enabled' => $this->getSetting('rtcp_capture_enabled'),
            'report_frequency' => $this->getSetting('rtcp_report_frequency'),
        ];
    }

    /**
     * Check if the trunk has advanced settings configured
     */
    public function hasAdvancedSettings(): bool
    {
        return !empty($this->settings) && count($this->settings) > 0;
    }

    /**
     * Get a summary of the trunk configuration
     */
    public function getConfigurationSummary(): array
    {
        return [
            'basic' => [
                'name' => $this->name,
                'status' => $this->status,
                'webhook_url' => $this->webhook_url,
            ],
            'credentials' => [
                'has_password' => !empty($this->getPassword()),
                'has_username' => !empty($this->getUsername()),
            ],
            'advanced_settings' => [
                'has_inbound' => !empty($this->getInboundSettings()),
                'has_outbound' => !empty($this->getOutboundSettings()),
                'has_webhook' => !empty($this->getWebhookSettings()),
                'has_rtcp' => !empty($this->getRtcpSettings()),
            ],
            'phone_numbers' => [
                'count' => $this->phoneNumbers()->count(),
                'primary' => $this->phoneNumbers()->wherePivot('assignment_type', 'primary')->count(),
                'secondary' => $this->phoneNumbers()->wherePivot('assignment_type', 'secondary')->count(),
                'backup' => $this->phoneNumbers()->wherePivot('assignment_type', 'backup')->count(),
            ],
        ];
    }
}
