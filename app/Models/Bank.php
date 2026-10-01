<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = ['code', 'name', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /** Rótulo "001 — Banco do Brasil S.A.". */
    public function getLabelAttribute(): string
    {
        return $this->code ? "{$this->code} — {$this->name}" : $this->name;
    }
}
