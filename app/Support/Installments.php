<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Divide um valor em parcelas sem drift de float (aritmética em centavos).
 *
 * Extração DRY de TransactionService + CardService (mesmo algoritmo duplicado).
 */
final class Installments
{
    /**
     * @return array<int, array{amount: float, occurred_on: string, due_on: string, installment_number: int, installments_total: int, installment_group_id: string}>
     */
    public static function split(float $amount, int $total, string $occurredOn, ?string $dueOn = null, string $description = '', ?string $statusPrimeira = null, ?string $statusDemais = null): array
    {
        $total = max(1, min(48, $total));
        $group = (string) Str::uuid();
        $totalCents = (int) round($amount * 100);
        $base = intdiv($totalCents, $total);
        $resto = $totalCents % $total;
        $baseDate = Carbon::parse($occurredOn);
        $baseDue = $dueOn ? Carbon::parse($dueOn) : null;

        $out = [];
        for ($i = 1; $i <= $total; $i++) {
            $cents = $base + ($i <= $resto ? 1 : 0);
            $occ = $baseDate->copy()->addMonthsNoOverflow($i - 1)->toDateString();
            $due = $baseDue ? $baseDue->copy()->addMonthsNoOverflow($i - 1)->toDateString() : $occ;
            $out[] = [
                'amount' => $cents / 100,
                'occurred_on' => $occ,
                'due_on' => $due,
                'description' => $total > 1 ? "{$description} ({$i}/{$total})" : $description,
                'status' => $i === 1 ? $statusPrimeira : $statusDemais,
                'installment_number' => $i,
                'installments_total' => $total,
                'installment_group_id' => $group,
            ];
        }

        return $out;
    }
}
