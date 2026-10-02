<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.view')
            && (! $user->role->isSectorScoped() || $user->sector_id === $meeting->sector_id);
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('meetings.manage');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin();
    }
}
