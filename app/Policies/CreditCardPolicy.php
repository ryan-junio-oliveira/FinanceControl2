<?php

namespace App\Policies;

use App\Models\CreditCard;
use App\Models\User;

class CreditCardPolicy
{
    public function view(User $user, CreditCard $card): bool
    {
        return $user->family_id !== null && $user->family_id === $card->family_id;
    }

    /** Criar/editar/excluir cartões é ato de gestor. */
    public function manage(User $user, CreditCard $card): bool
    {
        return $user->family_id !== null
            && $user->family_id === $card->family_id
            && $user->isAdmin();
    }
}
