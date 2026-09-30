<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->family_id !== null && $user->family_id === $category->family_id;
    }

    /** Criar/editar/excluir categorias é ato de gestor. */
    public function manage(User $user, Category $category): bool
    {
        return $user->family_id !== null
            && $user->family_id === $category->family_id
            && $user->isAdmin();
    }
}
