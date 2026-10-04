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

    public const YIELD_BASES = [
        'cdi' => 'CDI',
        'selic' => 'Selic',
        'ipca' => 'IPCA',
        'prefixado' => 'Prefixado',
    ];

    protected $fillable = [
        'group_id', 'portfolio_id', 'code', 'name', 'institution',
        'holder', 'kind', 'current_value', 'yield_percent', 'yield_base',
    ];

    protected function casts(): array
    {
        return ['current_value' => 'decimal:2', 'yield_percent' => 'decimal:2'];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    /** Ex.: "120% do CDI" ou "14,5% a.a." (prefixado). */
    public function getYieldLabelAttribute(): ?string
    {
        if ($this->yield_percent === null) {
            return null;
        }
        $num = number_format((float) $this->yield_percent, 2, ',', '.');

        return $this->yield_base === 'prefixado'
            ? "{$num}% a.a."
            : "{$num}% do ".(self::YIELD_BASES[$this->yield_base] ?? strtoupper((string) $this->yield_base));
    }
}
