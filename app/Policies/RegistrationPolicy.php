<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    /**
     * View registrations for an eventner.
     */
    public function viewAny(User $user, ?int $eventnerId = null): bool
    {
        if ($user->role === 'Admin') {
            return true;
        }

        $eventnerId = $eventnerId ?? $user->eventner?->id;
        return $eventnerId !== null;
    }

    /**
     * View a specific registration.
     */
    public function view(User $user, Registration $registration): bool
    {
        if ($user->role === 'Admin') {
            return true;
        }

        return $user->id === $registration->eventner->user_id;
    }

    /**
     * Update a registration.
     */
    public function update(User $user, Registration $registration): bool
    {
        if ($user->role === 'Admin') {
            return true;
        }

        return $user->id === $registration->eventner->user_id;
    }

    /**
     * Delete a registration.
     */
    public function delete(User $user, Registration $registration): bool
    {
        if ($user->role === 'Admin') {
            return true;
        }

        return $user->id === $registration->eventner->user_id;
    }
}
