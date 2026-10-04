<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $user->group_id !== null && $user->group_id === $account->group_id;
    }

    /** Criar/editar/excluir contas é ato de gestor. */
    public function manage(User $user, Account $account): bool
    {
        return $user->group_id !== null
            && $user->group_id === $account->group_id
            && $user->isAdmin();
    }
}
