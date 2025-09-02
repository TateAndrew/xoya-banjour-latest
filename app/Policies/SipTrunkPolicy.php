<?php

namespace App\Policies;

use App\Models\SipTrunk;
use App\Models\User;

class SipTrunkPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own SIP trunks
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SipTrunk $sipTrunk): bool
    {
        return $user->id === $sipTrunk->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create SIP trunks
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SipTrunk $sipTrunk): bool
    {
        return $user->id === $sipTrunk->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SipTrunk $sipTrunk): bool
    {
        return $user->id === $sipTrunk->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SipTrunk $sipTrunk): bool
    {
        return $user->id === $sipTrunk->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SipTrunk $sipTrunk): bool
    {
        return $user->id === $sipTrunk->user_id;
    }
}
