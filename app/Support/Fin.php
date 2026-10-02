<?php

namespace App\Support;

use App\Models\Family;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Fin
{
    /** Família do usuário autenticado (ou null). */
    public static function family(): ?Family
    {
        $user = Auth::user();

        return $user?->family;
    }

    public static function familyId(): ?int
    {
        return self::family()?->id;
    }

    /** Timezone da família (config) ou padrão da app. */
    public static function timezone(): string
    {
        try {
            $tz = self::family()?->settings?->timezone;
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
