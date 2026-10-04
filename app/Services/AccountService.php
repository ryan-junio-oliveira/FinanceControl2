<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Group;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regras de domínio das contas e transferências internas.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class AccountService
{
    /** Contas do grupo com saldo pré-calculado (sem N+1). */
    public function list(Group $group): Collection
    {
        $contas = $group->accounts()->with('bank')->orderBy('name')->get();
        $balances = Account::balancesForGroup($group->id);
        $contas->each(fn ($a) => $a->setAttribute('balance_cached', $balances[$a->id] ?? (float) $a->initial_balance));

        return $contas;
    }

    /** Extrato de uma conta específica (entradas e saídas, incluindo transferências). */
    public function statementForAccount(Account $account, int $perPage = 25)
    {
        return Transaction::where(function ($q) use ($account) {
            $q->where('account_id', $account->id)->orWhere('transfer_to_account_id', $account->id);
        })
            ->with(['member', 'category'])
            ->orderByDesc('occurred_on')->orderByDesc('id')
            ->paginate($perPage);
    }

    /** Totais pagos de entradas e saídas de uma conta. */
    public function totalsForAccount(Account $account): array
    {
        $fid = $account->group_id;
        $entradas = (float) Transaction::where('group_id', $fid)->where('status', 'pago')
            ->where(function ($q) use ($account) {
                $q->where('account_id', $account->id)->where('type', 'receita')
                    ->orWhere('transfer_to_account_id', $account->id);
            })->sum('amount');
        $saidas = (float) Transaction::where('group_id', $fid)->where('account_id', $account->id)->where('status', 'pago')
            ->whereIn('type', ['despesa', 'aporte', 'transferencia'])->sum('amount');

        return ['entradas' => $entradas, 'saidas' => $saidas];
    }

    /** Bancos ativos para o formulário. */
    public function activeBanks(): Collection
    {
        return Bank::where('is_active', true)->orderBy('name')->get();
    }

    /** Contas ativas + membros para o formulário de transferência. */
    public function transferOptions(Group $group): array
    {
        return [
            'contas' => $group->accounts()->where('active', true)->orderBy('name')->get(),
            'membros' => $group->users()->orderBy('name')->get(),
        ];
    }

    /**
     * Conta de "dinheiro físico" (tipo carteira). Cria automaticamente se a
     * grupo ainda não tiver — separa espécie de dinheiro digital.
     */
    public function dinheiroFisico(Group $group): Account
    {
        $carteira = $group->accounts()->where('active', true)->where('kind', 'carteira')->first();
        if ($carteira) {
            return $carteira;
        }

        return $group->accounts()->create([
            'name' => 'Dinheiro físico',
            'kind' => 'carteira',
            'initial_balance' => 0,
            'active' => true,
        ]);
    }

    public function create(Group $group, array $data): Account
    {
        if (! empty($data['bank_id'])) {
            Bank::where('is_active', true)->findOrFail($data['bank_id']);
        }

        return $group->accounts()->create($data);
    }

    public function update(Account $conta, array $data, bool $active): Account
    {
        if (! empty($data['bank_id'])) {
            Bank::where('is_active', true)->findOrFail($data['bank_id']);
        }
        $data['active'] = $active;
        $conta->update($data);

        return $conta->refresh();
    }

    public function destroy(Account $conta): void
    {
        abort_if($conta->transactions()->exists(), 422, 'Conta com movimentações não pode ser excluída. Desative-a.');
        $conta->delete();
    }

    public function transfer(Group $group, array $data): Transaction
    {
        return DB::transaction(function () use ($group, $data) {
            $from = $group->accounts()->findOrFail($data['from_account_id']);
            $to = $group->accounts()->findOrFail($data['to_account_id']);
            $group->users()->findOrFail($data['user_id']);

            return Transaction::create([
                'group_id' => $group->id,
                'user_id' => $data['user_id'],
                'account_id' => $from->id,
                'type' => 'transferencia',
                'description' => $data['description'] ?? "Transferência {$from->name} → {$to->name}",
                'amount' => $data['amount'],
                'occurred_on' => $data['occurred_on'],
                'status' => 'pago',
                'transfer_to_account_id' => $to->id,
            ]);
        });
    }
}
