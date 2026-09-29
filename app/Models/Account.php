<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = ['family_id', 'name', 'agency', 'number', 'kind', 'color', 'initial_balance', 'active'];

    protected function casts(): array
    {
        return ['initial_balance' => 'decimal:2', 'active' => 'boolean'];
    }

    /** Cores sugeridas por banco (o usuário pode escolher qualquer cor). */
    public const BANK_COLORS = [
        'Itaú' => '#EC7000',
        'Nubank' => '#820AD1',
        'Inter' => '#EA580C',
        'Bradesco' => '#CC092F',
        'Santander' => '#EC0000',
        'Banco do Brasil' => '#FCFC30',
        'Caixa' => '#005CA9',
        'XP' => '#FBC105',
        'C6' => '#1A1A1A',
        'PicPay' => '#21C25E',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Saldo atual = inicial + receitas pagas − despesas/aportes pagos + transferências líquidas. */
    public function getBalanceAttribute(): float
    {
        $in = (float) $this->transactions()->where('status', 'pago')->where('type', 'receita')->sum('amount');
        $out = (float) $this->transactions()->where('status', 'pago')->whereIn('type', ['despesa', 'aporte'])->sum('amount');
        $tIn = (float) Transaction::where('transfer_to_account_id', $this->id)->where('status', 'pago')->sum('amount');
        $tOut = (float) $this->transactions()->where('type', 'transferencia')->where('status', 'pago')->sum('amount');

        return (float) $this->initial_balance + $in - $out + $tIn - $tOut;
    }

    public function getLabelAttribute(): string
    {
        $parts = array_filter([$this->agency ? "Ag {$this->agency}" : null, $this->number]);
        $suffix = $parts ? ' · '.implode(' · ', $parts) : '';

        return $this->name.$suffix;
    }
}
