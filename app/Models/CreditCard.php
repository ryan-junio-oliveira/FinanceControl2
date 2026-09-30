<?php

namespace App\Models;

use App\Enums\CardBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    protected $fillable = [
        'family_id', 'holder_user_id', 'account_id', 'name', 'brand', 'last4',
        'credit_limit', 'closing_day', 'due_day', 'color', 'active',
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
     * Cor visual do cartão: herda da conta vinculada; senão, cor própria;
     * senão, gradiente padrão escuro.
     */
    public function getDisplayColorAttribute(): string
    {
        return $this->account?->color ?: ($this->color ?: 'linear-gradient(135deg,#0f172a,#334155)');
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
