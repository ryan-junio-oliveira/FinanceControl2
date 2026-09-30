<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function view(User $user, Account $account): bool
    {
        return $user->family_id !== null && $user->family_id === $account->family_id;
    }

    /** Criar/editar/excluir contas é ato de gestor. */
    public function manage(User $user, Account $account): bool
    {
        return $user->family_id !== null
            && $user->family_id === $account->family_id
            && $user->isAdmin();
    }
}
