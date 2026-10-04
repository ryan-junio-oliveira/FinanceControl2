<?php

namespace App\Support;

use App\Models\Bank;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo padrão de bancos do Prumo.
 *
 * Tabela global `banks` (não é por grupo): semeada via
 * `php artisan banks:seed` de forma idempotente (não duplica;
 * atualiza cor e status dos já existentes), nos mesmos moldes
 * do CategoryCatalog.
 *
 * A cor é a identidade visual do banco: contas e cartões a usam
 * (conta herda do banco; cartão herda da conta vinculada).
 */
final class BankCatalog
{
    public const DEFAULT_COLOR = '#0F172A';

    /**
     * @return array<int, array{code: string|null, name: string, color: string}>
     */
    public static function items(): array
    {
        return [
            // ── Grandes bancos de varejo ──
            ['code' => '001', 'name' => 'Banco do Brasil S.A.', 'color' => '#005CA9'],
            ['code' => '033', 'name' => 'Banco Santander Brasil', 'color' => '#EC0000'],
            ['code' => '104', 'name' => 'Caixa Econômica Federal', 'color' => '#005CA9'],
            ['code' => '237', 'name' => 'Banco Bradesco', 'color' => '#CC092F'],
            ['code' => '341', 'name' => 'Banco Itaú Unibanco', 'color' => '#EC7000'],
            ['code' => '422', 'name' => 'Banco Safra', 'color' => '#002855'],
            ['code' => '655', 'name' => 'Banco Votorantim (Neon)', 'color' => '#009E5C'],
            ['code' => '184', 'name' => 'Banco Itaú BBA', 'color' => '#003DA6'],
            ['code' => '036', 'name' => 'Banco Bradesco BBI', 'color' => '#CC092F'],
            ['code' => '029', 'name' => 'Banco Itaú Consignado', 'color' => '#EC7000'],
            ['code' => '394', 'name' => 'Banco Bradesco Financiamentos', 'color' => '#CC092F'],
            ['code' => '062', 'name' => 'Hipercard Banco Múltiplo', 'color' => '#D5001C'],
            ['code' => '663', 'name' => 'Banco Bradescard', 'color' => '#CC092F'],
            ['code' => '641', 'name' => 'Banco Alvorada', 'color' => '#CC092F'],

            // ── Bancos digitais e fintechs ──
            ['code' => '260', 'name' => 'Nubank', 'color' => '#820AD1'],
            ['code' => '077', 'name' => 'Banco Inter', 'color' => '#EA580C'],
            ['code' => '336', 'name' => 'C6 Bank', 'color' => '#1A1A1A'],
            ['code' => '212', 'name' => 'Banco Original', 'color' => '#007A53'],
            ['code' => '218', 'name' => 'Banco BS2', 'color' => '#009E96'],
            ['code' => '290', 'name' => 'PagBank (PagSeguro)', 'color' => '#00793A'],
            ['code' => '323', 'name' => 'Mercado Pago', 'color' => '#008ECF'],
            ['code' => '380', 'name' => 'PicPay', 'color' => '#21C25E'],
            ['code' => '197', 'name' => 'Stone Pagamentos', 'color' => '#00A651'],
            ['code' => '403', 'name' => 'Cora', 'color' => '#E63946'],
            ['code' => '404', 'name' => 'SumUp', 'color' => '#1D4ED8'],
            ['code' => '312', 'name' => 'Nu Invest (Nubank)', 'color' => '#820AD1'],
            ['code' => '386', 'name' => 'Nu Financeira', 'color' => '#820AD1'],
            ['code' => '340', 'name' => 'Superdigital (Santander)', 'color' => '#EC0000'],
            ['code' => '362', 'name' => 'Cielo', 'color' => '#0097C4'],
            ['code' => '364', 'name' => 'Efi Bank (Gerencianet)', 'color' => '#F26522'],
            ['code' => '332', 'name' => 'Acesso Soluções de Pagamento', 'color' => '#5B21B6'],
            ['code' => '342', 'name' => 'Creditas', 'color' => '#00A86B'],
            ['code' => '371', 'name' => 'Warren', 'color' => '#C81E4E'],
            ['code' => '325', 'name' => 'Órama', 'color' => '#E56B00'],
            ['code' => '637', 'name' => 'Banco Sofisa Direto', 'color' => '#004B8D'],

            // ── Investimentos e múltiplos ──
            ['code' => '102', 'name' => 'XP Investimentos', 'color' => '#111111'],
            ['code' => '208', 'name' => 'Banco BTG Pactual', 'color' => '#0A1F44'],
            ['code' => '746', 'name' => 'Banco Modal (XP)', 'color' => '#005B96'],
            ['code' => '707', 'name' => 'Banco Daycoval', 'color' => '#003B73'],
            ['code' => '643', 'name' => 'Banco Pine', 'color' => '#374151'],
            ['code' => '246', 'name' => 'Banco ABC Brasil', 'color' => '#003A70'],
            ['code' => '025', 'name' => 'Banco Alfa', 'color' => '#004B93'],
            ['code' => '318', 'name' => 'Banco BMG', 'color' => '#E85D04'],
            ['code' => '389', 'name' => 'Banco Mercantil do Brasil', 'color' => '#0077B6'],
            ['code' => '224', 'name' => 'Banco Fibra', 'color' => '#65A30D'],
            ['code' => '265', 'name' => 'Banco Fator', 'color' => '#1E3A5F'],
            ['code' => '082', 'name' => 'Banco Topázio', 'color' => '#A67C00'],
            ['code' => '107', 'name' => 'Banco Bocom BBM', 'color' => '#1E2A5A'],
            ['code' => '243', 'name' => 'Banco Master', 'color' => '#B00D28'],
            ['code' => '074', 'name' => 'Banco J. Safra', 'color' => '#002855'],
            ['code' => '075', 'name' => 'Banco ABN Amro', 'color' => '#007A70'],
            ['code' => '066', 'name' => 'Banco Morgan Stanley', 'color' => '#002855'],
            ['code' => '065', 'name' => 'Andbank Brasil', 'color' => '#1D4F9C'],
            ['code' => '600', 'name' => 'Banco Luso Brasileiro', 'color' => '#00569E'],
            ['code' => '604', 'name' => 'Banco Industrial do Brasil', 'color' => '#C8102E'],
            ['code' => '611', 'name' => 'Banco Paulista', 'color' => '#D97706'],
            ['code' => '612', 'name' => 'Banco Guanabara', 'color' => '#0063A6'],
            ['code' => '613', 'name' => 'Omni Banco', 'color' => '#004E89'],
            ['code' => '626', 'name' => 'Banco Ficsa', 'color' => '#C8102E'],
            ['code' => '654', 'name' => 'Banco Renner', 'color' => '#D22630'],
            ['code' => '374', 'name' => 'Realize (Lojas Renner)', 'color' => '#D22630'],
            ['code' => '656', 'name' => 'Banco Crefisa', 'color' => '#00833E'],
            ['code' => '233', 'name' => 'Banco Cifra', 'color' => '#004080'],
            ['code' => '078', 'name' => 'Paraná Banco', 'color' => '#0077B6'],
            ['code' => '108', 'name' => 'Portocred', 'color' => '#C8102E'],
            ['code' => '276', 'name' => 'Banco Senff', 'color' => '#004E89'],
            ['code' => '633', 'name' => 'Banco Rendimento', 'color' => '#008F46'],
            ['code' => '634', 'name' => 'Tribanco', 'color' => '#004E89'],
            ['code' => '739', 'name' => 'Banco Cetelem', 'color' => '#007A70'],
            ['code' => '741', 'name' => 'Banco Ribeirão Preto', 'color' => '#004E89'],
            ['code' => '743', 'name' => 'Banco Semear', 'color' => '#65A30D'],
            ['code' => '712', 'name' => 'Banco Ourinvest', 'color' => '#1E3A5F'],
            ['code' => '299', 'name' => 'Banco Sorocred', 'color' => '#C8102E'],
            ['code' => '330', 'name' => 'Banco Bari', 'color' => '#6D28D9'],
            ['code' => '353', 'name' => 'Qi Sociedade de Crédito', 'color' => '#6D28D9'],

            // ── Varejo e cartões de loja ──
            ['code' => '623', 'name' => 'Banco Pan', 'color' => '#0080C8'],
            ['code' => '368', 'name' => 'Banco Carrefour (CSF)', 'color' => '#004E9B'],
            ['code' => '358', 'name' => 'Midway (Riachuelo)', 'color' => '#B91C1C'],
            ['code' => '381', 'name' => 'Luizacred (Magazine Luiza)', 'color' => '#0066CC'],
            ['code' => '396', 'name' => 'Hub Pagamentos (Magazine Luiza)', 'color' => '#0066CC'],
            ['code' => '130', 'name' => 'Caruana (Pernambucanas)', 'color' => '#15803D'],

            // ── Bancos públicos regionais ──
            ['code' => '003', 'name' => 'Banco da Amazônia', 'color' => '#006B54'],
            ['code' => '004', 'name' => 'Banco do Nordeste do Brasil', 'color' => '#1E40AF'],
            ['code' => '021', 'name' => 'Banestes', 'color' => '#047857'],
            ['code' => '037', 'name' => 'Banpará', 'color' => '#047857'],
            ['code' => '041', 'name' => 'Banrisul', 'color' => '#0067B1'],
            ['code' => '047', 'name' => 'Banese', 'color' => '#00833E'],
            ['code' => '070', 'name' => 'BRB – Banco de Brasília', 'color' => '#004E9B'],

            // ── Cooperativas ──
            ['code' => '748', 'name' => 'Sicredi', 'color' => '#2F9E44'],
            ['code' => '756', 'name' => 'Sicoob', 'color' => '#099268'],
            ['code' => '136', 'name' => 'Unicred', 'color' => '#1864AB'],
            ['code' => '133', 'name' => 'Cresol', 'color' => '#D9480F'],
            ['code' => '085', 'name' => 'Ailos', 'color' => '#0C9E96'],
            ['code' => '010', 'name' => 'Credicoamo', 'color' => '#2B8A3E'],
            ['code' => '084', 'name' => 'Uniprime Norte do Paraná', 'color' => '#1864AB'],
            ['code' => '099', 'name' => 'Uniprime', 'color' => '#1864AB'],
            ['code' => '661', 'name' => 'Credisis', 'color' => '#7048E8'],
            ['code' => '760', 'name' => 'Credicitrus', 'color' => '#E67700'],
            ['code' => '379', 'name' => 'Cooperforte', 'color' => '#2F9E44'],

            // ── Estrangeiros no Brasil ──
            ['code' => '477', 'name' => 'Citibank', 'color' => '#003B70'],
            ['code' => '376', 'name' => 'Banco J.P. Morgan', 'color' => '#0E5CAD'],
            ['code' => '487', 'name' => 'Deutsche Bank Brasil', 'color' => '#001489'],
            ['code' => '505', 'name' => 'Credit Suisse Hedging-Griffo', 'color' => '#002855'],
            ['code' => '064', 'name' => 'Banco Goldman Sachs do Brasil', 'color' => '#3B6EA5'],
            ['code' => '747', 'name' => 'Banco Rabobank', 'color' => '#D97706'],
            ['code' => '752', 'name' => 'Banco BNP Paribas Brasil', 'color' => '#00814F'],
            ['code' => '755', 'name' => 'Bank of America Merrill Lynch', 'color' => '#C8102E'],
            ['code' => '751', 'name' => 'Scotiabank Brasil', 'color' => '#D80027'],
            ['code' => '366', 'name' => 'Banco Societe Generale Brasil', 'color' => '#212529'],
            ['code' => '370', 'name' => 'Banco Mizuho do Brasil', 'color' => '#D80027'],
            ['code' => '320', 'name' => 'China Construction Bank Brasil', 'color' => '#003A70'],
            ['code' => '300', 'name' => 'Banco de la Nación Argentina', 'color' => '#0077B6'],
        ];
    }

    /**
     * Semeia o catálogo global (idempotente: cria os novos e
     * atualiza cor/status dos já existentes).
     *
     * @return int quantidade de bancos criados
     */
    public static function seed(): int
    {
        return DB::transaction(function () {
            $created = 0;

            foreach (self::items() as $item) {
                $bank = Bank::updateOrCreate(
                    ['code' => $item['code'], 'name' => $item['name']],
                    ['color' => $item['color'], 'is_active' => true],
                );

                if ($bank->wasRecentlyCreated) {
                    $created++;
                }
            }

            return $created;
        });
    }
}
