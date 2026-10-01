<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\Family;
use App\Models\Invitation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Regras de domínio da família (convites, membros, papéis).
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class FamilyService
{
    /** Membros com gasto do mês + convites pendentes (tela de família). */
    public function dashboard(Family $family, string $mes): array
    {
        $membros = $family->users()->orderBy('name')->get()->map(function ($u) use ($family, $mes) {
            $gasto = (float) Transaction::where('family_id', $family->id)->where('user_id', $u->id)
                ->where('type', 'despesa')->whereIn('status', ['pago', 'pendente'])
                ->whereYear('occurred_on', substr($mes, 0, 4))->whereMonth('occurred_on', substr($mes, 5, 2))->sum('amount');
            $u->gasto_mes = $gasto;

            return $u;
        });

        return [
            'mes' => $mes,
            'membros' => $membros,
            'convites' => $family->invitations()->whereNull('accepted_at')->orderByDesc('created_at')->get(),
        ];
    }

    /** Membros da família (para API). */
    public function members(Family $family): Collection
    {
        return $family->users()->orderBy('name')->get();
    }

    /** Convites pendentes (para API). */
    public function invites(Family $family): Collection
    {
        return $family->invitations()->whereNull('accepted_at')->orderByDesc('created_at')->get();
    }

    /**
     * Convida pessoa: cria convite com token de primeiro acesso e envia e-mail.
     *
     * @return array{invitation: Invitation, mailed: bool}
     */
    public function invite(Family $family, array $data): array
    {
        abort_if($family->users()->where('email', $data['email'])->exists(), 422, 'Este e-mail já pertence à sua conta.');
        abort_if($family->invitations()->where('email', $data['email'])->whereNull('accepted_at')->exists(), 422, 'Já existe um convite pendente para este e-mail.');

        $invitation = $family->invitations()->create($data + ['token' => Str::random(48)]);

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
