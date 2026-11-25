<?php

namespace Modules\Rooms\Policies;

use App\Models\User;
use Modules\Rooms\Models\Room;

class RoomPolicy
{
    /**
     * Determine whether the user can view any rooms.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the room.
     */
    public function view(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create rooms.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the room.
     */
    public function update(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the room.
     */
    public function delete(User $user, Room $room): bool
    {
        // Only admins can delete, and only if there are no active devices
        return $user->isAdmin() && $room->activeDevice === null;
    }
}
