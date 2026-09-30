<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->family_id !== null && $user->family_id === $transaction->family_id;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->family_id !== null
            && $user->family_id === $transaction->family_id
            && in_array($transaction->type, ['despesa', 'receita'], true);
    }

    public function settle(User $user, Transaction $transaction): bool
    {
        return $user->family_id !== null && $user->family_id === $transaction->family_id;
    }

    /** Excluir é ato de gestor (admin/co_admin). */
    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->family_id !== null
            && $user->family_id === $transaction->family_id
            && $user->isAdmin();
    }
}
