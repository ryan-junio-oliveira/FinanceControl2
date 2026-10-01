<?php

namespace App\Bot\Handlers;

class IncomeHandler extends TransactionFlowHandler
{
    protected function type(): string
    {
        return 'receita';
    }

    protected function typeLabel(bool $singular = false): string
    {
        return $singular ? 'receita' : 'Receitas';
    }
}
