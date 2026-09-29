<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const TYPES = ['receita', 'despesa', 'transferencia', 'aporte'];
    public const STATUSES = ['pago', 'pendente', 'agendado'];

    protected $fillable = [
        'family_id', 'user_id', 'account_id', 'category_id', 'subcategory_id',
        'type', 'is_fixed', 'description', 'amount', 'occurred_on', 'due_on', 'status',
        'transfer_to_account_id', 'portfolio_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
            'due_on' => 'date',
            'is_fixed' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function scopeOfFamily(Builder $q, int $familyId): Builder
    {
        return $q->where('transactions.family_id', $familyId);
    }

    public function scopeInMonth(Builder $q, string $month, string $column = 'occurred_on'): Builder
    {
        [$y, $m] = explode('-', $month);

        return $q->whereYear($column, $y)->whereMonth($column, $m);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pendente'
            && $this->due_on
            && $this->due_on->isPast();
    }

    /** Rótulo de exibição alinhado ao protótipo (Paga/Recebido/A vencer/Agendado). */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'pago') {
            return $this->type === 'receita' ? 'Recebido' : 'Pago';
        }
        if ($this->status === 'agendado') {
            return 'Agendado';
        }
        if ($this->due_on && Carbon::parse($this->due_on)->isFuture()) {
            return 'A vencer';
        }

        return 'Pendente';
    }

    public function getDisplayStatusTypeAttribute(): string
    {
        return match ($this->display_status) {
            'Recebido', 'Pago' => 'success',
            'A vencer' => 'warning',
            'Agendado' => 'info',
            default => 'critical',
        };
    }
}
