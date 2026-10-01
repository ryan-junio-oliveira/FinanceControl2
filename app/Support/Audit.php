<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de trilha de auditoria.
 *
 * Só grava em requisições web autenticadas (ignora CLI/seed/testes):
 * captura quem, o quê, quando e de onde (IP, agente, método, URL).
 */
final class Audit
{
    private static bool $silenced = false;

    /** Executa $fn sem registrar trilha (ex.: limpeza em massa da conta). */
    public static function silence(callable $fn): mixed
    {
        self::$silenced = true;
        try {
            return $fn();
        } finally {
            self::$silenced = false;
        }
    }

    /** Registra ação ligada a um modelo (observers). */
    public static function model(Model $model, string $action, mixed $changes = null, ?string $description = null): ?AuditLog
    {
        $label = self::labels()[$model::class] ?? class_basename($model);
        $nome = $model->name ?? $model->description ?? ('#'.$model->getKey());
        $description ??= self::frase($action, $label, $nome);

        return self::criar(
            familyId: $model->family_id,
            action: $action,
            description: $description,
            changes: $changes,
            auditableType: $model::class,
            auditableId: $model->getKey(),
        );
    }

    /** Registra ação sem vínculo com modelo (login, logout, configurações). */
    public static function log(string $description, ?string $action = 'info', mixed $changes = null, ?User $user = null): ?AuditLog
    {
        $user ??= request()->user();
        if (! $user) {
            return null;
        }

        return self::criar($user->family_id, $action, $description, $changes, null, null, $user);
    }

    private static function criar(
        ?int $familyId,
        string $action,
        string $description,
        mixed $changes = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?User $user = null,
    ): ?AuditLog {
        if (self::$silenced) {
            return null;
        }

        // Fora de requisição web autenticada (artisan, seed, testes sem actingAs): não registra.
        if (app()->runningInConsole() && ! request()->user()) {
            return null;
        }

        $user ??= request()->user();
        $familyId ??= $user?->family_id;
        if ($familyId === null) {
            return null;
        }

        return AuditLog::create([
            'family_id' => $familyId,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
            'method' => request()->method(),
            'url' => mb_substr((string) request()->fullUrl(), 0, 500),
        ]);
    }

    private static function frase(string $action, string $label, string $nome): string
    {
        $verbo = match ($action) {
            'created' => 'Criou',
            'updated' => 'Alterou',
            'deleted' => 'Excluiu',
            default => ucfirst((string) $action),
        };

        return "{$verbo} {$label}: {$nome}";
    }

    private static function labels(): array
    {
        return [
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
    }
}
