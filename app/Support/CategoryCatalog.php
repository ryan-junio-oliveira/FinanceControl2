<?php

namespace App\Support;

use App\Models\Family;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo padrão de categorias do FinFamília.
 *
 * Semeado automaticamente para cada família nova (via cadastro) e
 * reaplicável via `php artisan categories:seed` sem duplicar.
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
            // ── Moradia ──
            $d('Aluguel', 'home'),
            $d('Condomínio', 'apartment'),
            $d('IPTU', 'receipt_long'),
            $d('Energia elétrica', 'bolt'),
            $d('Água e esgoto', 'water_drop'),
            $d('Gás', 'local_fire_department'),
            $d('Internet', 'wifi'),
            $d('Manutenção residencial', 'handyman'),
            $d('Móveis e decoração', 'chair'),
            $d('Seguro residencial', 'verified_user'),
            $d('Diarista e limpeza', 'cleaning_services'),

            // ── Contas e assinaturas ──
            $d('Celular', 'smartphone'),
            $d('TV e streaming', 'tv'),
            $d('Assinaturas digitais', 'subscriptions'),
            $d('Tarifas bancárias', 'account_balance'),
            $d('Anuidade do cartão', 'credit_card'),
            $d('Juros e multas', 'warning'),

            // ── Alimentação ──
            $d('Supermercado', 'shopping_cart'),
            $d('Feira', 'local_grocery_store'),
            $d('Padaria', 'bakery_dining'),
            $d('Restaurantes', 'restaurant'),
            $d('Delivery', 'delivery_dining'),
            $d('Cafeteria e lanches', 'local_cafe'),
            $d('Encontros e churrasco', 'groups'),

            // ── Transporte ──
            $d('Combustível', 'local_gas_station'),
            $d('Estacionamento', 'local_parking'),
            $d('Pedágio', 'toll'),
            $d('Transporte por app', 'local_taxi'),
            $d('Transporte público', 'directions_bus'),
            $d('Manutenção do carro', 'car_repair'),
            $d('Seguro auto', 'no_crash'),
            $d('IPVA e licenciamento', 'badge'),
            $d('Lavagem do carro', 'local_car_wash'),
            $d('Fretes e motoboy', 'local_shipping'),

            // ── Saúde ──
            $d('Plano de saúde', 'medical_services'),
            $d('Consultas e exames', 'stethoscope'),
            $d('Farmácia', 'medication'),
            $d('Dentista', 'dentistry'),
            $d('Terapia e psicologia', 'psychology'),
            $d('Óculos e lentes', 'eyeglasses'),
            $d('Academia', 'fitness_center'),
            $d('Esportes', 'sports_soccer'),
            $d('Suplementos', 'nutrition'),
            $d('Vacinas', 'vaccines'),
            $d('Cuidador e home care', 'accessible'),

            // ── Educação ──
            $d('Mensalidade escolar', 'school'),
            $d('Faculdade', 'cast_for_education'),
            $d('Cursos e idiomas', 'menu_book'),
            $d('Material escolar', 'edit'),
            $d('Livros', 'book'),
            $d('Transporte escolar', 'directions_bus'),
            $d('Reforço escolar', 'lightbulb'),

            // ── Filhos ──
            $d('Fraldas e bebê', 'child_care'),
            $d('Roupas infantis', 'stroller'),
            $d('Brinquedos', 'toys'),
            $d('Passeios em família', 'park'),

            // ── Pets ──
            $d('Ração e petshop', 'pets'),
            $d('Veterinário', 'healing'),
            $d('Banho e tosa', 'shower'),
            $d('Creche e adestramento', 'star'),

            // ── Vestuário ──
            $d('Roupas', 'checkroom'),
            $d('Calçados', 'footprints'),
            $d('Acessórios', 'watch'),
            $d('Lavanderia e costureira', 'dry_cleaning'),

            // ── Beleza ──
            $d('Cabelo e barbearia', 'content_cut'),
            $d('Manicure e pedicure', 'spa'),
            $d('Cosméticos', 'face'),

            // ── Lazer ──
            $d('Cinema e teatro', 'movie'),
            $d('Shows e eventos', 'celebration'),
            $d('Viagens', 'flight'),
            $d('Games e hobbies', 'sports_esports'),
            $d('Clube e parque', 'attractions'),

            // ── Doações e presentes ──
            $d('Dízimo e ofertas', 'volunteer_activism'),
            $d('Doações', 'favorite'),
            $d('Presentes', 'redeem'),

            // ── Financeiro ──
            $d('Empréstimos', 'payments'),
            $d('Consórcio', 'savings'),
            $d('Imposto de renda', 'receipt'),
            $d('Taxas e documentos', 'description'),

            // ── Trabalho ──
            $d('Coworking', 'work'),
            $d('Equipamentos de trabalho', 'computer'),
            $d('Ferramentas e software', 'build'),

            // ── Receitas ──
            $r('Salário', 'payments'),
            $r('13º salário', 'card_giftcard'),
            $r('Férias', 'beach_access'),
            $r('Bônus e PLR', 'emoji_events'),
            $r('Comissões', 'percent'),
            $r('Freelas e bicos', 'work'),
            $r('Renda extra', 'add_circle'),
            $r('Aposentadoria', 'elderly'),
            $r('Pensão', 'family_restroom'),
            $r('Bolsa de estudos', 'school'),
            $r('Aluguel recebido', 'home'),
            $r('Dividendos', 'trending_up'),
            $r('Rendimentos de investimentos', 'show_chart'),
            $r('Juros recebidos', 'attach_money'),
            $r('Reembolso', 'undo'),
            $r('Restituição do IR', 'receipt'),
            $r('Venda de usados', 'sell'),
            $r('Cashback', 'loyalty'),
            $r('Prêmios', 'celebration'),
            $r('Ajuda de custo', 'handshake'),
            $r('Vale-alimentação', 'lunch_dining'),
            $r('Gorjetas', 'tips_and_updates'),
            $r('Herança', 'account_balance'),
            $r('Outras receitas', 'more_horiz'),
        ];
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
}
