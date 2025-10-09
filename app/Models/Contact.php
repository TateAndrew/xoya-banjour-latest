<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'phone_e164',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the conversations for this contact.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the display name for the contact.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->phone_e164;
    }

    /**
     * Get the initials for avatar display.
     */
    public function getInitialsAttribute(): string
    {
        if ($this->name) {
            $words = explode(' ', $this->name);
            $initials = '';
            foreach (array_slice($words, 0, 2) as $word) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
            return $initials;
        }
        
        return strtoupper(substr($this->phone_e164, -2));
    }

    /**
     * Ensure phone number is in E.164 format before saving.
     */
    public function setPhoneE164Attribute($value)
    {
        if (empty($value)) {
            $this->attributes['phone_e164'] = null;
            return;
        }

        // Remove any non-numeric characters except leading +
        $number = preg_replace('/[^0-9+]/', '', $value);
        
        // If it doesn't start with +, add it
        if (!str_starts_with($number, '+')) {
            $number = '+' . $number;
        }
        
        $this->attributes['phone_e164'] = $number;
    }

    /**
     * Get phone number formatted for display.
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (empty($this->phone_e164)) {
            return '';
        }

        // Format: +1 (555) 123-4567
        $number = $this->phone_e164;
        
        // Remove the + sign for formatting
        $number = ltrim($number, '+');
        
        // Check if it's a US/Canada number (starts with 1)
        if (str_starts_with($number, '1') && strlen($number) === 11) {
            return sprintf('+1 (%s) %s-%s', 
                substr($number, 1, 3), 
                substr($number, 4, 3), 
                substr($number, 7, 4)
            );
        }
        
        // For other numbers, just return with + prefix
        return '+' . $number;
    }
}
