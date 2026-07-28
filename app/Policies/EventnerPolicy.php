<?php

namespace App\Policies;

use App\Models\Eventner;
use App\Models\User;

class EventnerPolicy
{
    /**
     * User can view any eventner (admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * User can view this eventner.
     * Admin sees all. Eventner sees own only.
     */
    public function view(User $user, Eventner $eventner): bool
    {
        if ($user->role === 'Admin') {
            return true;
        }

        return $user->id === $eventner->user_id;
    }

    /**
     * User can update this eventner.
     */
    public function update(User $user, Eventner $eventner): bool
    {
        if ($user->role === 'Admin') {
            return true;
        }

        return $user->id === $eventner->user_id;
    }

    /**
     * User can delete this eventner (admin only).
     */
    public function delete(User $user, Eventner $eventner): bool
    {
        return $user->role === 'Admin';
    }

    /**
     * User can manage (scoped write operations) this eventner.
     */
    public function manage(User $user, Eventner $eventner): bool
    {
        return $user->id === $eventner->user_id;
    }
}
