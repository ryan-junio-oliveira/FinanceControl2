<?php

namespace App\Models;

use App\Enums\CardBrand;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    protected $fillable = [
        'family_id', 'holder_user_id', 'account_id', 'name', 'brand', 'last4',
        'credit_limit', 'closing_day', 'due_day', 'active',
    ];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:2', 'active' => 'boolean'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CardTransaction::class);
    }

    /**
     * Período da fatura corrente a partir de uma referência:
     * se dia <= fechamento, (mês ant. C+1 .. mês atual C);
     * senão, (mês atual C+1 .. próx. mês C). Retorna [início, fim].
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function currentInvoiceRange(?Carbon $ref = null): array
    {
        $ref = ($ref ?? Carbon::today())->copy()->startOfDay();
        $c = min(max((int) $this->closing_day, 1), 28);

        if ($ref->day <= $c) {
            $start = $ref->copy()->subMonthNoOverflow()->day(min($c + 1, $ref->copy()->subMonthNoOverflow()->daysInMonth));
            $end = $ref->copy()->day($c);
        } else {
            $start = $ref->copy()->day(min($c + 1, $ref->daysInMonth));
            $end = $ref->copy()->addMonthNoOverflow()->day($c);
        }

        return [$start->startOfDay(), $end->endOfDay()];
    }

    /** Próxima data de fechamento e de vencimento a partir de hoje. */
    public function nextClosingDate(?Carbon $ref = null): Carbon
    {
        $ref = ($ref ?? Carbon::today())->copy()->startOfDay();
        $c = min(max((int) $this->closing_day, 1), 28);

        return $ref->day <= $c ? $ref->copy()->day($c) : $ref->copy()->addMonthNoOverflow()->day($c);
    }

    public function nextDueDate(?Carbon $ref = null): Carbon
    {
        $closing = $this->nextClosingDate($ref);
        $d = min(max((int) $this->due_day, 1), 28);
        $month = $d >= (int) $this->closing_day ? $closing->copy() : $closing->copy()->addMonthNoOverflow();

        return $month->day(min($d, $month->daysInMonth));
    }

    /** Fatura em aberto = itens pendentes. */
    public function getOpenInvoiceAttribute(): float
    {
        // Se veio com withSum (open_invoice_sum), evita N+1.
        if (array_key_exists('open_invoice_sum', $this->attributes) && $this->attributes['open_invoice_sum'] !== null) {
            return (float) $this->attributes['open_invoice_sum'];
        }
        if ($this->relationLoaded('items') && $this->items->isNotEmpty() && $this->items->every(fn ($i) => $i->status === 'pendente')) {
            return (float) $this->items->sum('amount');
        }

        return (float) $this->items()->where('status', 'pendente')->sum('amount');
    }

    public function getAvailableLimitAttribute(): float
    {
        return max(0, (float) $this->credit_limit - $this->open_invoice);
    }

    /**
     * Cor visual do cartão: herdada do banco da conta vinculada
     * (cartão → conta → banco); senão, gradiente padrão escuro.
     */
    public function getDisplayColorAttribute(): string
    {
        return $this->account?->bank?->color ?: 'linear-gradient(135deg,#0f172a,#334155)';
    }

    /** Rótulo pt-BR da bandeira (tolerante a valores legados fora do enum). */
    public function getBrandLabelAttribute(): ?string
    {
        if (empty($this->brand)) {
            return null;
        }

        return CardBrand::tryFrom((string) $this->brand)?->label() ?? (string) $this->brand;
    }
}
