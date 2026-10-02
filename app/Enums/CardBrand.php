<?php

namespace App\Enums;

/** Bandeiras de cartão aceitas no Prumo. */
enum CardBrand: string
{
    case Visa = 'visa';
    case Mastercard = 'mastercard';
    case Elo = 'elo';
    case Hipercard = 'hipercard';
    case Amex = 'amex';
    case Discover = 'discover';
    case Diners = 'diners';
    case Jcb = 'jcb';
    case UnionPay = 'unionpay';
    case Outra = 'outra';

    public function label(): string
    {
        return match ($this) {
            self::Visa => 'Visa',
            self::Mastercard => 'Mastercard',
            self::Elo => 'Elo',
            self::Hipercard => 'Hipercard',
            self::Amex => 'American Express',
            self::Discover => 'Discover',
            self::Diners => 'Diners Club',
            self::Jcb => 'JCB',
            self::UnionPay => 'UnionPay',
            self::Outra => 'Outra',
        };
    }

    /** @return array<string, string> [value => label] */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
