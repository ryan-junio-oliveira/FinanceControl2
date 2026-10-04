<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupSetting extends Model
{
    protected $fillable = [
        'group_id', 'currency', 'timezone',
        'consolidate_dependent_yield', 'notifications', 'secret_phrase',
    ];

    protected function casts(): array
    {
        return [
            'consolidate_dependent_yield' => 'boolean',
            'notifications' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function notify(string $key): bool
    {
        return (bool) ($this->notifications[$key] ?? false);
    }
}
