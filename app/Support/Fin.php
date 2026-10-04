<?php

namespace App\Support;

use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Fin
{
    /** Grupo do usuário autenticado (ou null). */
    public static function group(): ?Group
    {
        $user = Auth::user();

        return $user?->group;
    }

    public static function groupId(): ?int
    {
        return self::group()?->id;
    }

    /** Timezone do grupo (config) ou padrão da app. */
    public static function timezone(): string
    {
        try {
            $tz = self::group()?->settings?->timezone;
        } catch (\Throwable) {
            $tz = null;
        }

        return is_string($tz) && $tz !== '' ? $tz : (string) config('app.timezone', 'UTC');
    }

    public static function today(): Carbon
    {
        return Carbon::now(self::timezone())->startOfDay();
    }

    /** Mês de referência Y-m (query ?mes=YYYY-MM ou atual). */
    public static function month(): string
    {
        $now = Carbon::now(self::timezone())->format('Y-m');
        $mes = request()->query('mes', $now);

        if (! is_string($mes) || ! preg_match('/^(\d{4})-(\d{2})$/', $mes, $m)) {
            return $now;
        }
        $mm = (int) $m[2];
        if ($mm < 1 || $mm > 12) {
            return $now;
        }

        return $mes;
    }

    public static function money(?float $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
