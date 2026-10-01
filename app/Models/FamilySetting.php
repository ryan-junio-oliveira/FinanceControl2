<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilySetting extends Model
{
    protected $fillable = [
        'family_id', 'currency', 'timezone',
        'consolidate_dependent_yield', 'notifications',
    ];

    protected function casts(): array
    {
        return [
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
