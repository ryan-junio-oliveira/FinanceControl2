<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'family_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'changes',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => 'criou',
            'updated' => 'alterou',
            'deleted' => 'excluiu',
            default => $this->action,
        };
    }
}
