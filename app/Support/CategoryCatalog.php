<?php

namespace App\Support;

use App\Models\CardTransaction;
use App\Models\Family;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo padrão de categorias do Prumo: só os grandes temas.
 *
 * Semeado automaticamente para cada família nova (via cadastro) e
 * reaplicável via `php artisan categories:seed` sem duplicar.
 * Famílias antigas são consolidadas via `php artisan categories:prune`
 * (remapa lançamentos para o tema e apaga as categorias detalhadas).
 */
final class CategoryCatalog
{
    /**
     * @return array<int, array{name: string, type: string, icon: string}>
     */
    public static function items(): array
    {
        $d = fn (string $name, string $icon) => ['name' => $name, 'type' => 'despesa', 'icon' => $icon];
        $r = fn (string $name, string $icon) => ['name' => $name, 'type' => 'receita', 'icon' => $icon];

        return [
            $d('Moradia', 'home'),
            $d('Alimentação', 'restaurant'),
            $d('Transporte', 'directions_car'),
            $d('Saúde', 'medical_services'),
            $d('Educação', 'school'),
            $d('Lazer', 'celebration'),
            $d('Vestuário', 'checkroom'),
            $d('Cuidados pessoais', 'spa'),
            $d('Pets', 'pets'),
            $d('Filhos', 'child_care'),
            $d('Doações e presentes', 'redeem'),
            $d('Contas e assinaturas', 'subscriptions'),
            $d('Financeiro', 'payments'),
            $d('Trabalho', 'work'),
            $d('Outras despesas', 'more_horiz'),

            $r('Salário', 'payments'),
            $r('Renda extra', 'add_circle'),
            $r('Investimentos', 'trending_up'),
            $r('Benefícios', 'card_giftcard'),
            $r('Presentes recebidos', 'redeem'),
            $r('Outras receitas', 'more_horiz'),
        ];
    }

    public static function isTheme(string $type, string $name): bool
    {
        foreach (self::items() as $item) {
            if ($item['type'] === $type && $item['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Palavras-chave por tema (ordem importa: prefixos específicos primeiro).
     *
     * @return array<string, array<int, string>>
     */
    private static function keywords(string $type): array
    {
        if ($type === 'receita') {
            return [
                'Benefícios' => ['decimo', '13', 'ferias', 'plr', 'bonus', 'vale', 'bolsa', 'pensao', 'aposentadoria', 'beneficio'],
                'Presentes recebidos' => ['presente recebido', 'presente de', 'presente do', 'ganhei', 'gift'],
                'Salário' => ['salario', 'holerite', 'provento'],
                'Investimentos' => ['dividendo', 'rendimento', 'investimento', 'acao', 'fii', 'juros'],
                'Renda extra' => ['freela', 'bico', 'comissao', 'venda', 'extra', 'premio', 'gorjeta', 'ajuda de custo', 'cashback', 'aluguel'],
            ];
        }

        return [
            'Educação' => ['escola', 'escolar', 'faculdade', 'universidade', 'curso', 'livro', 'material', 'reforco', 'mensalidade', 'professor'],
            'Saúde' => ['saude', 'medico', 'consulta', 'exame', 'farmacia', 'drogaria', 'dentista', 'terapia', 'oculos', 'academia', 'esporte', 'suplemento', 'vacina', 'hospital', 'plano de saude', 'clinica', 'remedio'],
            'Transporte' => ['transporte por app', 'transporte publico', 'uber', '99', 'combustivel', 'gasolina', 'posto', 'estacionamento', 'pedagio', 'motoboy', 'frete', 'carro', 'ipva', 'seguro auto', 'lavagem', 'onibus', 'metro', 'passagem de onibus'],
            'Alimentação' => ['alimentacao', 'supermercado', 'mercado', 'feira', 'padaria', 'restaurante', 'delivery', 'ifood', 'lanche', 'cafeteria', 'churrasco', 'comida', 'hortifruti', 'sacolao', 'atacadao', 'carrefour', 'assai', 'pao de acucar'],
            'Moradia' => ['moradia', 'aluguel', 'condominio', 'iptu', 'energia', 'luz', 'agua', 'esgoto', 'gas', 'diarista', 'limpeza', 'manutencao', 'moveis', 'imobiliaria', 'reforma', 'faxina'],
            'Lazer' => ['lazer', 'cinema', 'teatro', 'show', 'evento', 'viagem', 'hotel', 'streaming', 'netflix', 'spotify', 'game', 'hobby', 'clube', 'parque', 'passeio', 'festa', 'praia'],
            'Vestuário' => ['roupa', 'calcado', 'sapato', 'vestuario', 'acessorio', 'lavanderia', 'costureira', 'moda'],
            'Cuidados pessoais' => ['cabelo', 'barbearia', 'manicure', 'pedicure', 'cosmetico', 'beleza', 'higiene', 'perfume', 'salao'],
            'Pets' => ['pet', 'racao', 'veterinario', 'tosa', 'adestramento', 'petshop', 'cobasi', 'petz'],
            'Filhos' => ['filho', 'filha', 'bebe', 'fralda', 'brinquedo', 'infantil', 'crianca'],
            'Doações e presentes' => ['dizimo', 'doacao', 'presente', 'oferta', 'caridade'],
            'Contas e assinaturas' => ['celular', 'assinatura', 'internet', 'telefone', 'vivo', 'claro', 'tim'],
            'Financeiro' => ['tarifa', 'anuidade', 'juro', 'multa', 'emprestimo', 'consorcio', 'imposto', 'taxa', 'documento', 'financiamento', 'divida', 'iof', 'banco'],
            'Trabalho' => ['trabalho', 'coworking', 'escritorio', 'equipamento', 'ferramenta', 'software'],
        ];
    }

    /**
     * Tema correspondente a um nome/descrição livre. Null = sem palpite.
     * O fallback (Outras...) fica por conta de quem chama.
     */
    public static function themeFor(string $type, string $text): ?string
    {
        $norm = CategoryKeywords::normalize($text);
        if ($norm === '') {
            return null;
        }

        foreach (self::keywords($type) as $theme => $words) {
            foreach ($words as $w) {
                if (str_contains($norm, $w)) {
                    return $theme;
                }
            }
        }

        return null;
    }

    public static function fallback(string $type): string
    {
        return $type === 'receita' ? 'Outras receitas' : 'Outras despesas';
    }

    /**
     * Semeia o catálogo para a família (idempotente: não duplica).
     *
     * @return int quantidade de categorias criadas (0 = já tinha tudo)
     */
    public static function seedForFamily(Family $family): int
    {
        return DB::transaction(function () use ($family) {
            $created = 0;
            $sort = (int) ($family->categories()->max('sort') ?? 0);

            foreach (self::items() as $item) {
                $exists = $family->categories()
                    ->where('name', $item['name'])
                    ->where('type', $item['type'])
                    ->exists();

                if (! $exists) {
                    $sort++;
                    $family->categories()->create([
                        'name' => $item['name'],
                        'type' => $item['type'],
                        'icon' => $item['icon'],
                        'sort' => $sort,
                    ]);
                    $created++;
                }
            }

            return $created;
        });
    }

    /**
     * Consolida categorias detalhadas nos temas: move lançamentos
     * (transações e itens de cartão) e apaga as excedentes.
     *
     * @return array{moved: int, removed: int}
     */
    public static function pruneForFamily(Family $family): array
    {
        return DB::transaction(function () use ($family) {
            self::seedForFamily($family);

            $themeIds = [];
            foreach (['despesa', 'receita'] as $type) {
                foreach (self::items() as $item) {
                    if ($item['type'] !== $type) {
                        continue;
                    }
                    $themeIds[$type][$item['name']] = $family->categories()
                        ->where('type', $type)->where('name', $item['name'])->value('id');
                }
            }

            $moved = 0;
            $removed = 0;
            foreach (['despesa', 'receita'] as $type) {
                $themeNames = array_column(array_filter(self::items(), fn ($i) => $i['type'] === $type), 'name');
                $old = $family->categories()->where('type', $type)->whereNotIn('name', $themeNames)->get();

                foreach ($old as $cat) {
                    $theme = self::themeFor($cat->type, $cat->name) ?? self::fallback($cat->type);
                    $target = $themeIds[$cat->type][$theme] ?? null;
                    if (! $target) {
                        continue;
                    }

                    $moved += $family->transactions()->where('category_id', $cat->id)->update(['category_id' => $target]);
                    $moved += CardTransaction::where('family_id', $family->id)->where('category_id', $cat->id)->update(['category_id' => $target]);
                    $cat->delete();
                    $removed++;
                }
            }

            return ['moved' => $moved, 'removed' => $removed];
        });
    }
}
