<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'family_id', 'user_id', 'action', 'description',
        'auditable_type', 'auditable_id', 'changes',
        'ip_address', 'user_agent', 'method', 'url',
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
            'created' => 'Criação',
            'updated' => 'Alteração',
            'deleted' => 'Exclusão',
            'login' => 'Login',
            'logout' => 'Logout',
            'settings' => 'Configurações',
            'transfer' => 'Transferência',
            'settle' => 'Liquidação',
            'pay' => 'Pagamento',
            'invite' => 'Convite',
            'role' => 'Papel',
            'member' => 'Membro',
            'info' => 'Ação',
            default => ucfirst((string) $this->action),
        };
    }

    public function actionType(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'critical',
            'login' => 'success',
            'logout' => 'neutral',
            'settings', 'role', 'member', 'invite' => 'warning',
            default => 'neutral',
        };
    }

    /** Recurso de forma legível (ex.: Lançamento #12 ou Conta #3). */
    public function resourceLabel(): ?string
    {
        if (! $this->auditable_type) {
            return null;
        }
        $map = [
            'App\Models\Transaction' => 'Lançamento',
            'App\Models\Account' => 'Conta',
            'App\Models\CreditCard' => 'Cartão',
            'App\Models\CardTransaction' => 'Item de cartão',
            'App\Models\Category' => 'Categoria',
            'App\Models\Portfolio' => 'Carteira',
            'App\Models\Asset' => 'Ativo',
            'App\Models\Contribution' => 'Aporte/Rendimento',
            'App\Models\Invitation' => 'Convite',
            'App\Models\User' => 'Membro',
            'App\Models\FamilySetting' => 'Configurações',
        ];

        return ($map[$this->auditable_type] ?? class_basename($this->auditable_type)).' #'.$this->auditable_id;
    }
}
