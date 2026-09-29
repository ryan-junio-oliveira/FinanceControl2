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

    /** Mês de referência Y-m (query ?mes=YYYY-MM ou atual). */
    public static function month(): string
    {
        $mes = request()->query('mes', Carbon::now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $mes) ? $mes : Carbon::now()->format('Y-m');
    }

    /** Semanas do mês para o gráfico de fluxo: [rótulo, início, fim]. */
    public static function weeksOfMonth(string $month): array
    {
        [$y, $m] = explode('-', $month);
        $last = Carbon::create((int) $y, (int) $m, 1)->daysInMonth;

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
