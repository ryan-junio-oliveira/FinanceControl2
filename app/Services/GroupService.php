<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Regras de domínio do grupo (convites, membros, papéis).
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class GroupService
{
    /** Membros com gasto do mês + convites pendentes (tela de grupo). */
    public function dashboard(Group $group, string $mes): array
    {
        [$ano, $m] = array_map('intval', explode('-', $mes));
        $gastos = Transaction::where('group_id', $group->id)
            ->where('type', 'despesa')->whereIn('status', ['pago', 'pendente'])
            ->whereYear('occurred_on', $ano)->whereMonth('occurred_on', $m)
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        $membros = $group->users()->orderBy('name')->get()->each(function ($u) use ($gastos) {
            $u->gasto_mes = (float) ($gastos[$u->id] ?? 0);
        });

        return [
            'mes' => $mes,
            'membros' => $membros,
            'convites' => $group->invitations()->whereNull('accepted_at')->orderByDesc('created_at')->get(),
        ];
    }

    /** Membros do grupo (para API). */
    public function members(Group $group): Collection
    {
        return $group->users()->orderBy('name')->get();
    }

    /** Convites pendentes (para API). */
    public function invites(Group $group): Collection
    {
        return $group->invitations()->whereNull('accepted_at')->orderByDesc('created_at')->get();
    }

    /**
     * Convida pessoa: cria convite com token de primeiro acesso e envia e-mail.
     *
     * @return array{invitation: Invitation, mailed: bool}
     */
    public function invite(Group $group, array $data): array
    {
        abort_if($group->users()->where('email', $data['email'])->exists(), 422, 'Este e-mail já pertence à sua conta.');
        abort_if($group->invitations()->where('email', $data['email'])->whereNull('accepted_at')->exists(), 422, 'Já existe um convite pendente para este e-mail.');

        $invitation = $group->invitations()->create($data + ['token' => Str::random(48), 'expires_at' => now()->addDays(7)]);

        try {
            Mail::to($data['email'])->queue(new WelcomeEmail($invitation));

            return ['invitation' => $invitation, 'mailed' => true];
        } catch (\Throwable) {
            // E-mail segue pendente; o link pode ser compartilhado manualmente.
            return ['invitation' => $invitation, 'mailed' => false];
        }
    }

    public function revokeInvite(Invitation $convite): void
    {
        $convite->delete();
    }

    public function removeMember(User $membro, int $actorId): void
    {
        abort_if($membro->id === $actorId, 422, 'Você não pode remover a si mesmo.');
        abort_if($membro->role === 'admin', 422, 'O administrador principal não pode ser removido.');
        abort_if($membro->transactions()->exists(), 422, 'Membro com lançamentos não pode ser removido.');
        abort_if($membro->cardTransactions()->exists(), 422, 'Membro com compras no cartão não pode ser removido.');

        $membro->delete();
    }

    public function updateRole(User $membro, array $data): User
    {
        abort_if($membro->role === 'admin', 422, 'O papel do administrador principal não pode mudar.');
        $membro->update($data);

        return $membro->refresh();
    }
}
