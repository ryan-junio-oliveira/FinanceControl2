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

    /** Semanas do mês para o gráfico de fluxo: [rótulo, início, fim]. */
    public static function weeksOfMonth(string $month): array
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $month, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
            $month = Carbon::now(self::timezone())->format('Y-m');
            [$y, $mm] = explode('-', $month);
        } else {
            [$y, $mm] = explode('-', $month);
        }
        $last = Carbon::create((int) $y, (int) $mm, 1, 0, 0, 0, self::timezone())->daysInMonth;

        return [
            ['Sem 1 (01-07)', 1, min(7, $last)],
            ['Sem 2 (08-14)', 8, min(14, $last)],
            ['Sem 3 (15-21)', 15, min(21, $last)],
            ['Sem 4 (22-'.$last.')', 22, $last],
        ];
    }

    public static function money(?float $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }

    public static function signedMoney(float $value, string $type): string
    {
        $prefix = $type === 'receita' ? '+' : '−';

        return $prefix.self::money($value);
    }
}
