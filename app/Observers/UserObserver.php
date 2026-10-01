<?php

namespace App\Observers;

use App\Models\User;
use App\Observers\Concerns\LogsModelActivity;
use App\Support\Audit;

class UserObserver
{
    use LogsModelActivity;

    public function updated(User $user): void
    {
        $changes = [];
        foreach ($user->getChanges() as $field => $new) {
            if (in_array($field, ['updated_at', 'remember_token'], true)) {
                continue;
            }
            $changes[$field] = ['de' => $user->getOriginal($field), 'para' => $new];
        }
        if ($changes === []) {
            return;
        }

        if (isset($changes['role'])) {
            Audit::model($user, 'updated', $changes, "Alterou o papel do membro: {$user->name}");

            return;
        }

        Audit::model($user, 'updated', $changes);
    }
}
