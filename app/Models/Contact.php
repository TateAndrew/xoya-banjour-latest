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
}
