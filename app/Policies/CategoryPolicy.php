<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->group_id !== null && $user->group_id === $category->group_id;
    }

    /** Criar/editar/excluir categorias é ato de gestor. */
    public function manage(User $user, Category $category): bool
    {
        return $user->group_id !== null
            && $user->group_id === $category->group_id
            && $user->isAdmin();
    }
}
