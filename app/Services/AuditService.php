<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Family;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Regras de domínio da trilha de auditoria.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class AuditService
{
    /** @return array<string, string> */
    public function actionLabels(): array
    {
        return [
            'created' => 'Criação',
            'updated' => 'Alteração',
            'deleted' => 'Exclusão',
            'login' => 'Login',
            'logout' => 'Logout',
            'settings' => 'Configurações',
            'invite' => 'Convite',
            'role' => 'Papel',
            'member' => 'Membro',
            'info' => 'Ação',
        ];
    }

    public function list(Family $family, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $q = AuditLog::where('family_id', $family->id)->with('member');

        if (! empty($filters['q'])) {
            $q->where('description', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['acao'])) {
            $q->where('action', $filters['acao']);
        }
        if (! empty($filters['membro'])) {
            $q->where('user_id', $filters['membro']);
        }
        if (! empty($filters['de'])) {
            $q->whereDate('created_at', '>=', $filters['de']);
        }
        if (! empty($filters['ate'])) {
            $q->whereDate('created_at', '<=', $filters['ate']);
        }

        return $q->orderByDesc('id')->paginate($perPage);
    }
}
