<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    public const KINDS = ['reserva' => 'Reserva de Emergência', 'estudos' => 'Educação', 'futuro' => 'Futuro', 'livre' => 'Livre'];

    protected $fillable = ['family_id', 'name', 'objective', 'kind', 'target_amount', 'deadline'];

    protected function casts(): array
    {
        return ['target_amount' => 'decimal:2', 'deadline' => 'date'];
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
        if (array_key_exists('total', $this->attributes) && $this->attributes['total'] !== null) {
            return (float) $this->attributes['total'];
        }

        return (float) $this->assets()->sum('current_value');
    }

    public function getProgressAttribute(): ?float
    {
        if (! $this->target_amount || $this->target_amount <= 0) {
            return null;
        }

        return round($this->total / (float) $this->target_amount * 100, 1);
    }

    /** Quanto falta para a meta (nunca negativo). */
    public function getRemainingAttribute(): ?float
    {
        if (! $this->target_amount || $this->target_amount <= 0) {
            return null;
        }

        return max(0, (float) $this->target_amount - $this->total);
    }

    /** Meses restantes até o prazo (null sem prazo; 0 se vencido). */
    public function getMonthsLeftAttribute(): ?int
    {
        if (! $this->deadline) {
            return null;
        }
        $now = today()->startOfMonth();
        $end = $this->deadline->copy()->startOfMonth();

        return max(0, $now->diffInMonths($end, false));
    }

    /** Aporte mensal necessário para bater a meta no prazo. */
    public function getMonthlyNeededAttribute(): ?float
    {
        if ($this->remaining === null || $this->months_left === null || $this->months_left <= 0) {
            return null;
        }

        return round($this->remaining / $this->months_left, 2);
    }
}
