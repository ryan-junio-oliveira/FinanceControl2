<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    public const KINDS = ['reserva' => 'Reserva de Emergência', 'estudos' => 'Educação', 'futuro' => 'Futuro', 'livre' => 'Livre'];

    protected $fillable = ['family_id', 'name', 'objective', 'kind', 'target_amount'];

    protected function casts(): array
    {
        return ['target_amount' => 'decimal:2'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->assets()->sum('current_value');
    }

    public function getProgressAttribute(): ?float
    {
        if (! $this->target_amount || $this->target_amount <= 0) {
            return null;
        }

        return round($this->total / (float) $this->target_amount * 100, 1);
    }
}
