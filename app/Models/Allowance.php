<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allowance extends Model
{
    protected $fillable = ['family_id', 'user_id', 'amount', 'frequency', 'payday', 'active'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'active' => 'boolean'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Gasto do membro no mês atual (base da % da mesada). */
    public function spentInMonth(string $month): float
    {
        [$y, $m] = explode('-', $month);

        return (float) Transaction::where('family_id', $this->family_id)
            ->where('user_id', $this->user_id)
            ->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])
            ->whereYear('occurred_on', $y)
            ->whereMonth('occurred_on', $m)
            ->sum('amount');
    }

    /** Próxima data de repasse a partir de hoje. */
    public function nextPayday(): Carbon
    {
        $today = Carbon::today();
        if ($this->frequency === 'semanal') {
            $dow = min(max((int) $this->payday, 0), 6); // 0=domingo
            $next = $today->copy()->next($dow);
            return $next->isSameDay($today) ? $today : $next;
        }
        $day = min(max((int) $this->payday, 1), 28);
        $candidate = $today->copy()->day($day);
        if ($candidate->isPast() && ! $candidate->isToday()) {
            $candidate->addMonth();
        }

        return $candidate;
    }
}
