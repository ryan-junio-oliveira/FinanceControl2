<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    public const KINDS = [
        'renda_fixa' => 'Renda Fixa',
        'fii' => 'FII',
        'acao' => 'Ação',
        'etf' => 'ETF',
        'previdencia' => 'Previdência',
    ];

    protected $fillable = [
        'family_id', 'portfolio_id', 'code', 'name', 'institution',
        'holder', 'kind', 'current_value', 'profitability',
    ];

    protected function casts(): array
    {
        return ['current_value' => 'decimal:2'];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
