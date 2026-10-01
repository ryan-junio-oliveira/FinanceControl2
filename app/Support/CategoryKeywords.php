<?php

namespace App\Support;

/** Palavras-chave → tema de categoria (usado no bot e em importações). */
final class CategoryKeywords
{
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return (string) preg_replace('/\s+/', ' ', trim($text));
    }

    /** Nome do tema sugerido ou null. O chamador valida pelo tipo. */
    public static function guessName(string $description): ?string
    {
        return CategoryCatalog::themeFor('despesa', $description)
            ?? CategoryCatalog::themeFor('receita', $description);
    }
}
