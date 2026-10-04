<?php

namespace App\Services;

use App\Models\Group;

/**
 * Regras de domínio das configurações da conta.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class SettingsService
{
    public function update(Group $group, array $data, bool $consolidate): void
    {
        $group->update(['name' => $data['name']]);
        $group->setting()->update([
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
            'consolidate_dependent_yield' => $consolidate,
        ]);
    }

    /** @return array<string, bool> */
    public function updateNotifications(Group $group, callable $flag): array
    {
        $keys = ['fatura_vencimento', 'conta_vencimento'];
        $notifications = [];
        foreach ($keys as $k) {
            $notifications[$k] = (bool) $flag($k);
        }
        $group->setting()->update(['notifications' => $notifications]);

        return $notifications;
    }
}
