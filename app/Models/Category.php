<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['family_id', 'name', 'type', 'icon', 'monthly_cap', 'archived', 'sort'];

    protected function casts(): array
    {
        return ['monthly_cap' => 'decimal:2', 'archived' => 'boolean'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Soma de despesas pagas/pendentes da categoria no mês (Y-m). */
    public function spentInMonth(string $month): float
    {
        [$y, $m] = explode('-', $month);
        return (float) $this->transactions()
            ->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])
            ->whereYear('occurred_on', $y)
            ->whereMonth('occurred_on', $m)
            ->sum('amount');
    }
}
