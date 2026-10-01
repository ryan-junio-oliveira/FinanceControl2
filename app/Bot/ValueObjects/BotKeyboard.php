<?php

namespace App\Bot\ValueObjects;

/**
 * Teclado de botões independente do canal.
 * Cada linha é uma lista de [rótulo, dado].
 */
final class BotKeyboard
{
    /** @param array<int, array<int, array{0: string, 1: string}>> $rows */
    private function __construct(public readonly array $rows) {}

    /** @param array<int, array<int, array{0: string, 1: string}>> $rows */
    public static function inline(array $rows): self
    {
        return new self($rows);
    }

    public static function menu(array $options): self
    {
        $rows = [];
        foreach ($options as $label => $data) {
            $rows[] = [[$label, $data]];
        }

        return new self($rows);
    }
}
