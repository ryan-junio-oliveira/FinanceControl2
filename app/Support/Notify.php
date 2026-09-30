<?php

namespace App\Support;

use App\Models\Family;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Envio de notificações respeitando as preferências da família.
 *
 * Chaves de `family_settings.notifications`: compra_dependente,
 * fatura_vencimento, resumo_semanal, dividendo.
 */
final class Notify
{
    public static function enabled(Family $family, string $key): bool
    {
        $prefs = $family->setting()->notifications ?? [];

        return (bool) ($prefs[$key] ?? false);
    }

    /** Gestores (admin/co_admin) da família. */
    public static function gestores(Family $family): Collection
    {
        return $family->users()->whereIn('role', ['admin', 'co_admin'])->get();
    }

    public static function gestoresIf(Family $family, string $key, Notification $notification): void
    {
        if (! self::enabled($family, $key)) {
            return;
        }

        foreach (self::gestores($family) as $gestor) {
            $gestor->notify($notification);
        }
    }

    public static function memberIf(User $member, Family $family, string $key, Notification $notification): void
    {
        if (! self::enabled($family, $key)) {
            return;
        }

        $member->notify($notification);
    }
}
