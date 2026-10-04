<?php

namespace App\Support;

use App\Models\Group;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Envio de notificações respeitando as preferências do grupo.
 *
 * Chaves de `group_settings.notifications`: fatura_vencimento
 * (fatura do cartão próxima do vencimento), conta_vencimento
 * (conta/lançamento próximo do pagamento).
 */
final class Notify
{
    public static function enabled(Group $group, string $key): bool
    {
        $prefs = $group->setting()->notifications ?? [];

        return (bool) ($prefs[$key] ?? false);
    }

    /** Gestores (admin/co_admin) do grupo. */
    public static function gestores(Group $group): Collection
    {
        return $group->users()->whereIn('role', ['admin', 'co_admin'])->get();
    }

    public static function gestoresIf(Group $group, string $key, Notification $notification): void
    {
        if (! self::enabled($group, $key)) {
            return;
        }

        foreach (self::gestores($group) as $gestor) {
            $gestor->notify($notification);
        }
    }

    public static function memberIf(User $member, Group $group, string $key, Notification $notification): void
    {
        if (! self::enabled($group, $key)) {
            return;
        }

        $member->notify($notification);
    }
}
