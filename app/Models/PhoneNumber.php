<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PhoneNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'country_code',
        'area_code',
        'city',
        'state',
        'carrier',
        'number_type',
        'monthly_rate',
        'setup_fee',
        'telynx_id',
        'status',
        'capabilities',
        'purchased_at',
        'expires_at',
        'metadata',
        'messaging_profile_id',
        'assigned_to_profile_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'monthly_rate' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'assigned_to_profile_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messagingProfile(): BelongsTo
    {
        return $this->belongsTo(MessagingProfile::class);
    }

    public function sipTrunks(): BelongsToMany
    {
        return $this->belongsToMany(SipTrunk::class, 'sip_trunk_phone_number')
                    ->withPivot(['assignment_type', 'is_active', 'assigned_at', 'last_used_at', 'settings'])
                    ->withTimestamps();
    }

    public function activeSipTrunks(): BelongsToMany
    {
        return $this->sipTrunks()->wherePivot('is_active', true);
    }

    public function primarySipTrunk(): BelongsToMany
    {
        return $this->sipTrunks()->wherePivot('assignment_type', 'primary');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isPurchased(): bool
    {
        return $this->status === 'purchased';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function getFormattedNumberAttribute(): string
    {
        return '+' . $this->country_code . ' ' . $this->phone_number;
    }

    public function isAssignedToSipTrunk(): bool
    {
        return $this->activeSipTrunks()->exists();
    }

    public function getAssignedSipTrunk(): ?SipTrunk
    {
        return $this->primarySipTrunk()->first();
    }
}
