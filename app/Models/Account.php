<?php

namespace App\Models;

use App\Support\BankCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['group_id', 'bank_id', 'name', 'kind', 'initial_balance', 'active'];

    protected function casts(): array
    {
        return ['initial_balance' => 'decimal:2', 'active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Saldo atual = inicial + receitas pagas − despesas/aportes pagos + transferências líquidas. */
    public function getBalanceAttribute(): float
    {
        // Se pré-calculado via balancesForGroup (balance_cached), evita N+1.
        if (array_key_exists('balance_cached', $this->attributes) && $this->attributes['balance_cached'] !== null) {
            return (float) $this->attributes['balance_cached'];
        }
        // Mantido para compatibilidade pontual (ex.: testes). Para listagens,
        // prefira balancesForGroup() que resolve tudo em 4 queries.
        $in = (float) $this->transactions()->where('status', 'pago')->where('type', 'receita')->sum('amount');
        $out = (float) $this->transactions()->where('status', 'pago')->whereIn('type', ['despesa', 'aporte'])->sum('amount');
        $tIn = (float) Transaction::where('transfer_to_account_id', $this->id)->where('status', 'pago')->sum('amount');
        $tOut = (float) $this->transactions()->where('type', 'transferencia')->where('status', 'pago')->sum('amount');

        return (float) $this->initial_balance + $in - $out + $tIn - $tOut;
    }

    /**
     * Saldos de todas as contas do grupo em apenas 4 agregações SQL.
     *
     * @return array<int, float> [account_id => balance]
     */
    public static function balancesForGroup(int $groupId): array
    {
        $initial = self::where('group_id', $groupId)->pluck('initial_balance', 'id');

        $byAccount = Transaction::where('group_id', $groupId)
            ->where('status', 'pago')
            ->whereNotNull('account_id')
            ->selectRaw('account_id, type, SUM(amount) as total')
            ->groupBy('account_id', 'type')
            ->get()
            ->groupBy('account_id');

        $transferIn = Transaction::where('group_id', $groupId)
            ->where('status', 'pago')
            ->whereNotNull('transfer_to_account_id')
            ->selectRaw('transfer_to_account_id as account_id, SUM(amount) as total')
            ->groupBy('transfer_to_account_id')
            ->pluck('total', 'account_id');

        $balances = [];
        foreach ($initial as $id => $init) {
            $rows = $byAccount->get($id, collect());
            $in = (float) $rows->where('type', 'receita')->sum('total');
            $out = (float) $rows->whereIn('type', ['despesa', 'aporte'])->sum('total');
            // Transferência de saída já está em transactions com account_id + type=transferencia
            $tOut = (float) $rows->where('type', 'transferencia')->sum('total');
            $tIn = (float) ($transferIn[$id] ?? 0);

            $balances[$id] = (float) $init + $in - $out + $tIn - $tOut;
        }

        return $balances;
    }

    public function getLabelAttribute(): string
    {
        return $this->name;
    }

    /** Cor visual: herdada do banco (1 conta pertence a 1 banco). */
    public function getDisplayColorAttribute(): string
    {
        return $this->bank?->color ?: BankCatalog::DEFAULT_COLOR;
    }
}
