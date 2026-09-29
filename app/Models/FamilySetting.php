<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilySetting extends Model
{
    protected $fillable = [
        'family_id', 'currency', 'timezone', 'closing_day',
        'approval_threshold', 'privacy_hide_under',
        'consolidate_dependent_yield', 'notifications',
    ];

    protected function casts(): array
    {
        return [
            'approval_threshold' => 'decimal:2',
            'privacy_hide_under' => 'decimal:2',
            'consolidate_dependent_yield' => 'boolean',
            'notifications' => 'array',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function notify(string $key): bool
    {
        return (bool) ($this->notifications[$key] ?? false);
    }
}
