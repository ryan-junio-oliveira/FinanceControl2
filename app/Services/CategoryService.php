<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Group;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Regras de domínio das categorias.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class CategoryService
{
    public function list(Group $group, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $q = $group->categories();
        if (! empty($filters['tipo']) && in_array($filters['tipo'], ['despesa', 'receita'], true)) {
            $q->where('type', $filters['tipo']);
        }
        if (! empty($filters['q'])) {
            $q->where('name', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['arquivadas'])) {
            $q->where('archived', true);
        } else {
            $q->where('archived', false);
        }

        return $q->orderBy('sort')->orderBy('name')->paginate($perPage);
    }

    public function create(Group $group, array $data): Category
    {
        return $group->categories()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? 'tag',
            'sort' => ($group->categories()->max('sort') ?? 0) + 1,
        ]);
    }

    public function update(Category $categoria, array $data, bool $archived): Category
    {
        $data['archived'] = $archived;
        $categoria->update($data);

        return $categoria->refresh();
    }

    public function destroy(Category $categoria): void
    {
        abort_if($categoria->transactions()->exists(), 422, 'Categoria com lançamentos não pode ser excluída. Arquive-a.');
        $categoria->delete();
    }
}
