<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotIdentity extends Model
{
    protected $fillable = ['user_id', 'channel', 'external_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
