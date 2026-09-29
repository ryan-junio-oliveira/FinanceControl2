<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankConnection extends Model
{
    protected $fillable = ['family_id', 'bank', 'details', 'status', 'last_sync_at'];

    protected function casts(): array
    {
        return ['last_sync_at' => 'datetime'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
