<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lista negra de períodos de teste gratuito.
 *
 * Impede que um e-mail (ou IP, se habilitado) que já usou o trial crie outra
 * conta gratuita para burlar a assinatura.
 */
class TrialBlacklist extends Model
{
    public $timestamps = false;

    protected $table = 'trial_blacklist';

    protected $fillable = ['identifier', 'type', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
