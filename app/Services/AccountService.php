<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Family;
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
    /** Contas da família com saldo pré-calculado (sem N+1). */
    public function list(Family $family): Collection
    {
        $contas = $family->accounts()->with('bank')->orderBy('name')->get();
        $balances = Account::balancesForFamily($family->id);
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
        $fid = $account->family_id;
        $entradas = (float) Transaction::where('family_id', $fid)->where('status', 'pago')
            ->where(function ($q) use ($account) {
                $q->where('account_id', $account->id)->where('type', 'receita')
                    ->orWhere('transfer_to_account_id', $account->id);
            })->sum('amount');
        $saidas = (float) Transaction::where('family_id', $fid)->where('account_id', $account->id)->where('status', 'pago')
            ->whereIn('type', ['despesa', 'aporte', 'transferencia'])->sum('amount');

        return ['entradas' => $entradas, 'saidas' => $saidas];
    }

    /** Bancos ativos para o formulário. */
    public function activeBanks(): Collection
    {
        return Bank::where('is_active', true)->orderBy('name')->get();
    }

    /** Contas ativas + membros para o formulário de transferência. */
    public function transferOptions(Family $family): array
    {
        return [
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
        ];
    }

    /**
     * Conta de "dinheiro físico" (tipo carteira). Cria automaticamente se a
     * família ainda não tiver — separa espécie de dinheiro digital.
     */
    public function dinheiroFisico(Family $family): Account
    {
        $carteira = $family->accounts()->where('active', true)->where('kind', 'carteira')->first();
        if ($carteira) {
            return $carteira;
        }

        return $family->accounts()->create([
            'name' => 'Dinheiro físico',
            'kind' => 'carteira',
            'initial_balance' => 0,
            'active' => true,
        ]);
    }

    public function create(Family $family, array $data): Account
    {
        if (! empty($data['bank_id'])) {
            Bank::where('is_active', true)->findOrFail($data['bank_id']);
        }

        return $family->accounts()->create($data);
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

    public function transfer(Family $family, array $data): Transaction
    {
        return DB::transaction(function () use ($family, $data) {
            $from = $family->accounts()->findOrFail($data['from_account_id']);
            $to = $family->accounts()->findOrFail($data['to_account_id']);
            $family->users()->findOrFail($data['user_id']);

            return Transaction::create([
                'family_id' => $family->id,
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
