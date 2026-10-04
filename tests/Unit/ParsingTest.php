<?php

namespace Tests\Unit;

use App\Bot\BotPresenter;
use App\Http\Requests\FormRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParsingTest extends TestCase
{
    #[DataProvider('botAmountProvider')]
    public function test_bot_parse_amount(string $input, ?float $expected): void
    {
        $this->assertSame($expected, BotPresenter::parseAmount($input));
    }

    public static function botAmountProvider(): array
    {
        return [
            'decimal com vírgula' => ['150,50', 150.50],
            'milhar br' => ['1.234,56', 1234.56],
            'inteiro' => ['30', 30.0],
            'zero rejeitado' => ['0', null],
            'texto rejeitado' => ['abc', null],
            'vazio rejeitado' => ['', null],
        ];
    }

    #[DataProvider('botDateProvider')]
    public function test_bot_parse_date(string $input, ?string $expected): void
    {
        // Avaliado no instante do assert: o provider roda no carregamento e
        // a suíte pode cruzar a meia-noite entre um e outro.
        if ($input === 'hoje') {
            $expected = date('Y-m-d');
        }
        $this->assertSame($expected, BotPresenter::parseDate($input));
    }

    public static function botDateProvider(): array
    {
        return [
            'hoje' => ['hoje', date('Y-m-d')],
            'dia e mês' => ['25/10', date('Y').'-10-25'],
            'data completa' => ['05/10/2026', '2026-10-05'],
            'inválida' => ['32/13', null],
            'texto' => ['amanhã', null],
        ];
    }

    public function test_brazilian_decimal_edge_cases(): void
    {
        $this->assertSame('30.00', FormRequest::parseBrazilianDecimal('30,00'));
        $this->assertNull(FormRequest::parseBrazilianDecimal(''));
        $this->assertNull(FormRequest::parseBrazilianDecimal('   '));
    }
}
