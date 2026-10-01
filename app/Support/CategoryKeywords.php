<?php

namespace App\Support;

/** Palavras-chave → nome da categoria (usado no bot e em importações). */
final class CategoryKeywords
{
    private const MAP = [
        'Supermercado' => ['supermercado', 'mercado', 'atacadao', 'atacadão', 'extra', 'carrefour', 'assai', 'hortifruti'],
        'Feira' => ['feira', 'quitanda', 'sacolao', 'sacolão'],
        'Padaria' => ['padaria', 'panificadora'],
        'Restaurantes' => ['restaurante', 'lanchonete', 'pizzaria', 'hamburgueria', 'cantina'],
        'Delivery' => ['ifood', 'ifd', 'rappi', 'delivery', 'aiqfome'],
        'Transporte por app' => ['uber', '99 ', '99taxi', 'cabify', 'indriver'],
        'Combustível' => ['posto', 'shell', 'ipiranga', 'combustivel', 'combustível', 'gasolina', 'etanol'],
        'Farmácia' => ['farmacia', 'farmácia', 'drogaria', 'drogasil', 'raia', 'pacheco'],
        'TV e streaming' => ['netflix', 'spotify', 'prime video', 'disney', 'hbo', 'youtube premium', 'deezer'],
        'Celular' => ['vivo', 'claro', 'tim ', ' oi ', 'recarga'],
        'Internet' => ['internet', 'fibra', 'banda larga'],
        'Energia elétrica' => ['energia', 'enel', 'light', 'eletro', 'copel', 'cemig', 'conta de luz'],
        'Água e esgoto' => ['agua', 'água', 'sabesp', 'sane', 'conta de agua', 'conta de água'],
        'Academia' => ['academia', 'smart fit', 'smartfit', 'bluefit'],
        'Plano de saúde' => ['plano de saude', 'plano de saúde', 'unimed', 'amil', 'sulamerica'],
        'Mensalidade escolar' => ['escola', 'colegio', 'colégio', 'mensalidade', 'faculdade', 'curso'],
        'Aluguel' => ['aluguel', 'aluguer'],
        'Condomínio' => ['condominio', 'condomínio'],
        'Cinema e teatro' => ['cinema', 'teatro', 'cinemark', 'ingresso'],
        'Viagens' => ['hotel', 'decolar', 'latam', 'gol ', 'azul linhas', 'airbnb', 'viagem', 'passagem'],
        'Presentes' => ['presente'],
        'Roupas' => ['renner', 'zara', 'roupa', 'vestuario', 'vestuário'],
        'Salário' => ['salario', 'salário', 'provento', 'holerite', 'pagamento salario'],
        'Tarifas bancárias' => ['tarifa', 'anuidade', 'iof', 'manutencao conta', 'manutenção conta'],
        'Consultas e exames' => ['consulta', 'exame', 'laboratorio', 'laboratório', 'clinica', 'clínica'],
        'Dentista' => ['dentista', 'odonto'],
        'Ração e petshop' => ['pet', 'racao', 'ração', 'cobasi', 'petz'],
        'Veterinário' => ['veterinario', 'veterinário'],
        'Empréstimos' => ['emprestimo', 'empréstimo'],
    ];

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return (string) preg_replace('/\s+/', ' ', trim($text));
    }

    /** Nome da categoria sugerida ou null. */
    public static function guessName(string $description): ?string
    {
        $norm = self::normalize($description);
        if ($norm === '') {
            return null;
        }
        foreach (self::MAP as $category => $words) {
            foreach ($words as $w) {
                if (str_contains($norm, $w)) {
                    return $category;
                }
            }
        }

        return null;
    }
}
