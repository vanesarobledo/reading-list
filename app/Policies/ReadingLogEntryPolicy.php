<?php

namespace App\Policies;

use App\Models\ReadingLogEntry;
use App\Models\User;

class ReadingLogEntryPolicy
{
    //    public function update(User $user, ReadingLogEntry $readingLogEntry): bool
    //    {
    //        return false;
    //    }

    public function delete(User $user, ReadingLogEntry $readingLogEntry): bool
    {
        return $readingLogEntry->user()->is($user);
    }
}
