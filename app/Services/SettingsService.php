<?php

namespace App\Services;

use App\Models\Family;

/**
 * Regras de domínio das configurações da conta.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class SettingsService
{
    public function update(Family $family, array $data, bool $consolidate): void
    {
        $family->update(['name' => $data['name']]);
        $family->setting()->update([
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
            'consolidate_dependent_yield' => $consolidate,
        ]);
    }

    /** @return array<string, bool> */
    public function updateNotifications(Family $family, callable $flag): array
    {
        $keys = ['fatura_vencimento', 'conta_vencimento'];
        $notifications = [];
        foreach ($keys as $k) {
            $notifications[$k] = (bool) $flag($k);
        }
        $family->setting()->update(['notifications' => $notifications]);

        return $notifications;
    }
}
