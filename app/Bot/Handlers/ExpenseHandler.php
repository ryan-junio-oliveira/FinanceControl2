<?php

namespace App\Bot\Handlers;

class ExpenseHandler extends TransactionFlowHandler
{
    protected function type(): string
    {
        return 'despesa';
    }

    protected function asksPaymentMethod(): bool
    {
        return true;
    }

    protected function typeLabel(bool $singular = false): string
    {
        return $singular ? 'despesa' : 'Despesas';
    }
}
