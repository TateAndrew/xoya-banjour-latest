<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'monthly_rate' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
