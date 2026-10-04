<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Bank;
use App\Models\CardTransaction;
use App\Models\Contribution;
use App\Models\CreditCard;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BankCatalog;
use App\Support\CategoryCatalog;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Popula o banco com dados fake de referência para o ano de 2026
 * usando o usuário #1 (criado manualmente) e o grupo dele.
 *
 * Uso: php artisan db:seed --class=PopulateDatabaseSeeder
 *
 * O seeder é reexecutável: limpa os registros de domínio do grupo
 * (mantém usuários, bancos do catálogo e categorias) antes de repopular.
 */
class PopulateDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::find((int) env('SEED_USER_ID', 1))
            ?? User::where('role', 'admin')->whereNotNull('group_id')->orderBy('id')->first();
        if (! $admin) {
            $this->command?->error('Nenhum usuário admin com grupo. Crie sua conta antes de popular (ou defina SEED_USER_ID).');

            return;
        }

        $group = $admin->group;
        if (! $group) {
            $this->command?->error('O usuário #1 não pertence a um grupo.');

            return;
        }

        $faker = FakerFactory::create('pt_BR');
        $faker->seed(2026);
        $today = Carbon::today();
        $year = 2026;

        DB::transaction(function () use ($group, $admin, $faker, $today, $year) {
            $this->limpar($group);

            // Catálogo base (idempotente).
            BankCatalog::seed();
            CategoryCatalog::seedForGroup($group);

            $membros = $this->membros($group, $admin);
            $contas = $this->contas($group);
            $cartoes = $this->cartoes($group, $contas, $membros);

            $cat = fn (string $tipo, string $nome) => $group->categories()
                ->where('type', $tipo)->where('name', $nome)->first();

            $statusPorData = function (Carbon $data, bool $futuroPodePendente = true) use ($today) {
                if ($data->lt($today)) {
                    // 1 em cada 12 vencidas fica pendente (em atraso).
                    return random_int(0, 11) === 0 ? 'pendente' : 'pago';
                }

                // Futuras: quase sempre agendadas; ocasionalmente pendente (a vencer).
                return $futuroPodePendente && random_int(0, 9) === 0 ? 'pendente' : 'agendado';
            };

            // ───────────────────────── RECEITAS ─────────────────────────
            $salarios = [
                [$membros['admin'], 8500.00, 'Salário'],
                [$membros['parceiro'], 4200.00, 'Salário'],
            ];
            foreach ($salarios as [$membro, $valor, $desc]) {
                for ($mes = 1; $mes <= 12; $mes++) {
                    $data = Carbon::create($year, $mes, 5);
                    $this->transacao($group, [
                        'user_id' => $membro->id,
                        'type' => 'receita',
                        'description' => $desc,
                        'amount' => $valor,
                        'occurred_on' => $data,
                        'due_on' => $data,
                        'status' => $statusPorData($data, false),
                        'is_fixed' => true,
                        'account_id' => $contas['itau']->id,
                        'category_id' => $cat('receita', 'Salário')?->id,
                    ]);
                }
            }

            // 13º em dezembro.
            $data13 = Carbon::create($year, 12, 20);
            $this->transacao($group, [
                'user_id' => $membros['admin']->id,
                'type' => 'receita',
                'description' => '13º salário',
                'amount' => 8500.00,
                'occurred_on' => $data13,
                'due_on' => $data13,
                'status' => $statusPorData($data13),
                'account_id' => $contas['itau']->id,
                'category_id' => $cat('receita', 'Benefícios')?->id,
            ]);

            // Rendas extras esporádicas.
            foreach ([['Freelance — site', 1800], ['Venda de usados', 650], ['Reembolso', 320], ['Dividendos FII', 210], ['Cashback', 84]] as [$desc, $valor]) {
                $mes = random_int(1, 12);
                $data = Carbon::create($year, $mes, random_int(1, 27));
                $this->transacao($group, [
                    'user_id' => $membros['admin']->id,
                    'type' => 'receita',
                    'description' => $desc,
                    'amount' => $valor,
                    'occurred_on' => $data,
                    'due_on' => $data,
                    'status' => $statusPorData($data),
                    'account_id' => $contas['itau']->id,
                    'category_id' => $cat('receita', match ($desc) {
                        'Freelance — site' => 'Renda extra',
                        'Venda de usados' => 'Renda extra',
                        'Reembolso' => 'Outras receitas',
                        'Dividendos FII' => 'Investimentos',
                        default => 'Outras receitas',
                    })?->id,
                ]);
            }

            // ───────────────────── DESPESAS FIXAS ─────────────────────
            $fixas = [
                ['Aluguel', 2300, 10, 'Moradia', 'Moradia'],
                ['Condomínio', 580, 10, 'Moradia', 'Moradia'],
                ['Plano de saúde', 920, 5, 'Saúde', 'Saúde'],
                ['Mensalidade escolar', 1850, 10, 'Educação', 'Educação'],
                ['Internet fibra', 149, 8, 'Moradia', 'Contas e assinaturas'],
                ['Celular (2 linhas)', 128, 8, 'Contas e assinaturas', 'Contas e assinaturas'],
                ['TV e streaming', 65, 15, 'Contas e assinaturas', 'Lazer'],
                ['Academia', 119, 3, 'Saúde', 'Saúde'],
            ];

            foreach ($fixas as [$desc, $valor, $dia, $grupo, $nomeCat]) {
                for ($mes = 1; $mes <= 12; $mes++) {
                    $data = Carbon::create($year, $mes, $dia);
                    $this->transacao($group, [
                        'user_id' => $membros['admin']->id,
                        'type' => 'despesa',
                        'description' => $desc,
                        'amount' => $valor,
                        'occurred_on' => $data,
                        'due_on' => $data,
                        'status' => $statusPorData($data),
                        'is_fixed' => true,
                        'account_id' => $contas['itau']->id,
                        'category_id' => $cat('despesa', $nomeCat)?->id,
                    ]);
                }
            }

            // Contas de consumo variáveis por mês.
            foreach (range(1, 12) as $mes) {
                $consumo = [
                    ['Energia elétrica', random_int(180, 320), 12],
                    ['Água e esgoto', random_int(110, 170), 15],
                ];
                foreach ($consumo as [$desc, $valor, $dia]) {
                    $data = Carbon::create($year, $mes, $dia);
                    $this->transacao($group, [
                        'user_id' => $membros['admin']->id,
                        'type' => 'despesa',
                        'description' => $desc,
                        'amount' => $valor,
                        'occurred_on' => $data,
                        'due_on' => $data,
                        'status' => $statusPorData($data),
                        'account_id' => $contas['itau']->id,
                        'category_id' => $cat('despesa', 'Moradia')?->id,
                    ]);
                }
            }

            // ───────────────────── DESPESAS VARIÁVEIS ─────────────────────
            $variaveis = [
                ['Supermercado', 640, 980, 'Alimentação'],
                ['Feira', 90, 160, 'Alimentação'],
                ['Padaria', 25, 70, 'Alimentação'],
                ['Restaurantes', 120, 320, 'Alimentação'],
                ['Delivery', 40, 120, 'Alimentação'],
                ['Cafeteria e lanches', 15, 55, 'Alimentação'],
                ['Combustível', 180, 360, 'Transporte'],
                ['Transporte por app', 30, 120, 'Transporte'],
                ['Farmácia', 40, 180, 'Saúde'],
                ['Roupas', 90, 320, 'Vestuário'],
                ['Cinema e teatro', 50, 140, 'Lazer'],
                ['Presentes', 60, 250, 'Doações e presentes'],
            ];

            foreach (range(1, 12) as $mes) {
                $qtd = random_int(8, 12);
                foreach (array_rand($variaveis, $qtd) as $i) {
                    [$desc, $min, $max, $catNome] = $variaveis[$i];
                    $data = Carbon::create($year, $mes, random_int(1, 28));
                    $this->transacao($group, [
                        'user_id' => random_int(0, 3) === 0 ? $membros['parceiro']->id : $membros['admin']->id,
                        'type' => 'despesa',
                        'description' => $desc,
                        'amount' => (float) number_format($faker->randomFloat(2, $min, $max), 2, '.', ''),
                        'occurred_on' => $data,
                        'due_on' => $data,
                        'status' => $statusPorData($data),
                        'account_id' => random_int(0, 2) === 0 ? $contas['nubank']->id : $contas['itau']->id,
                        'category_id' => $cat('despesa', $catNome)?->id,
                    ]);
                }
            }

            // Férias em janeiro e julho.
            foreach ([['Viagem — praia', 2600, 1, 12], ['Viagem — serra', 1900, 7, 18]] as [$desc, $valor, $mes, $dia]) {
                $data = Carbon::create($year, $mes, $dia);
                $this->transacao($group, [
                    'user_id' => $membros['admin']->id,
                    'type' => 'despesa',
                    'description' => $desc,
                    'amount' => $valor,
                    'occurred_on' => $data,
                    'due_on' => $data,
                    'status' => $statusPorData($data),
                    'account_id' => $contas['nubank']->id,
                    'category_id' => $cat('despesa', 'Lazer')?->id,
                ]);
            }

            // ───────────────────── PARCELAS ─────────────────────
            $parcelas = [
                ['Smart TV 65"', 3200, 12, 1],   // de janeiro/2026
                ['iPhone 16', 7800, 12, 4],       // de abril/2026
                ['Notebook', 4200, 6, 8],         // de agosto/2026
            ];
            foreach ($parcelas as [$desc, $total, $qtd, $mesInicio]) {
                $this->parcelas($group, $membros['admin'], $contas['itau'], $cat('despesa', 'Moradia')?->id, $desc, $total, $qtd, Carbon::create($year, $mesInicio, 5), $statusPorData);
            }

            // ───────────────────── TRANSFERÊNCIAS ─────────────────────
            foreach (range(1, 12) as $mes) {
                $data = Carbon::create($year, $mes, 6);
                $this->transacao($group, [
                    'user_id' => $membros['admin']->id,
                    'type' => 'transferencia',
                    'description' => 'Investimento mensal',
                    'amount' => 1000.00,
                    'occurred_on' => $data,
                    'due_on' => $data,
                    'status' => $statusPorData($data, false),
                    'account_id' => $contas['itau']->id,
                    'transfer_to_account_id' => $contas['xp']->id,
                ]);
            }

            // ───────────────────── COMPRAS NO CARTÃO ─────────────────────
            $comprasCartao = [
                ['Mercado cartão', 350, 'Alimentação'],
                ['Restaurante', 180, 'Alimentação'],
                ['Combustível', 240, 'Transporte'],
                ['Farmácia', 90, 'Saúde'],
                ['Eletrônicos', 650, 'Moradia'],
                ['Streaming', 45, 'Lazer'],
            ];
            foreach (range(1, 12) as $mes) {
                $qtd = random_int(3, 5);
                foreach (array_rand($comprasCartao, $qtd) as $i) {
                    [$desc, $valor, $catNome] = $comprasCartao[$i];
                    $cartao = $cartoes[array_rand($cartoes)];
                    $data = Carbon::create($year, $mes, random_int(1, 27));
                    $status = $statusPorData($data);
                    CardTransaction::create([
                        'group_id' => $group->id,
                        'credit_card_id' => $cartao->id,
                        'user_id' => $membros['admin']->id,
                        'category_id' => $cat('despesa', $catNome)?->id,
                        'description' => $desc,
                        'amount' => (float) number_format($faker->randomFloat(2, $valor * 0.6, $valor * 1.4), 2, '.', ''),
                        'occurred_on' => $data,
                        'status' => $status === 'agendado' ? 'pendente' : $status,
                    ]);
                }
            }

            // ───────────────────── INVESTIMENTOS ─────────────────────
            $carteiras = [
                ['Reserva de Emergência', 'reserva', 30000, null, 'Cobertura de 6 meses de despesas'],
                ['Aposentadoria', 'futuro', 250000, Carbon::create(2045, 12, 31), 'Complemento de renda na aposentadoria'],
                ['Viagem 2027', 'livre', 25000, Carbon::create(2027, 6, 30), 'Volta ao mundo'],
            ];
            foreach ($carteiras as [$nome, $tipo, $meta, $prazo, $objetivo]) {
                Portfolio::create([
                    'group_id' => $group->id,
                    'name' => $nome,
                    'kind' => $tipo,
                    'target_amount' => $meta,
                    'deadline' => $prazo,
                    'objective' => $objetivo,
                ]);
            }

            $reserva = Portfolio::where('group_id', $group->id)->where('kind', 'reserva')->first();
            $futuro = Portfolio::where('group_id', $group->id)->where('kind', 'futuro')->first();
            $viagem = Portfolio::where('group_id', $group->id)->where('kind', 'livre')->first();

            $ativos = [
                [$reserva, 'CDB Inter DI', 'CDB', 'renda_fixa', 105, 'cdi', 'Banco Inter', 12000],
                [$reserva, 'Tesouro Selic 2029', 'SELIC29', 'renda_fixa', null, 'selic', 'Tesouro Direto', 8500],
                [$futuro, 'Fundo HGLG11', 'HGLG11', 'fii', null, null, 'BTG Pactual', 9800],
                [$futuro, 'ETF BOVA11', 'BOVA11', 'etf', null, null, 'XP Investimentos', 6100],
                [$futuro, 'Previdência PGBL', 'PGBL', 'previdencia', 112, 'cdi', 'Brasilprev', 15400],
                [$viagem, 'CDB Prefixado', 'CDB-PRE', 'renda_fixa', 14.5, 'prefixado', 'Banco Daycoval', 4000],
            ];
            $assetModels = [];
            foreach ($ativos as [$carteira, $nome, $codigo, $tipo, $yield, $base, $inst, $valor]) {
                $assetModels[] = Asset::create([
                    'group_id' => $group->id,
                    'portfolio_id' => $carteira->id,
                    'code' => $codigo,
                    'name' => $nome,
                    'institution' => $inst,
                    'kind' => $tipo,
                    'current_value' => $valor,
                    'yield_percent' => $yield,
                    'yield_base' => $base ?? 'cdi',
                ]);
            }

            // Aportes mensais na reserva (sai da XP) + rendimentos trimestrais.
            foreach (range(1, 12) as $mes) {
                $data = Carbon::create($year, $mes, 6);
                $aporte = random_int(800, 1200);
                Contribution::create([
                    'group_id' => $group->id,
                    'portfolio_id' => $reserva->id,
                    'asset_id' => $assetModels[0]->id,
                    'account_id' => $contas['xp']->id,
                    'kind' => 'aporte',
                    'amount' => $aporte,
                    'occurred_on' => $data,
                    'note' => 'Aporte mensal',
                ]);
                $this->transacao($group, [
                    'user_id' => $membros['admin']->id,
                    'type' => 'aporte',
                    'description' => "Aporte — {$reserva->name}",
                    'amount' => $aporte,
                    'occurred_on' => $data,
                    'due_on' => $data,
                    'status' => $statusPorData($data, false),
                    'account_id' => $contas['xp']->id,
                    'portfolio_id' => $reserva->id,
                ]);
                $assetModels[0]->increment('current_value', $aporte);

                if (in_array($mes, [3, 6, 9, 12], true)) {
                    $rend = random_int(250, 550);
                    $alvo = $assetModels[random_int(0, count($assetModels) - 1)];
                    Contribution::create([
                        'group_id' => $group->id,
                        'portfolio_id' => $alvo->portfolio_id,
                        'asset_id' => $alvo->id,
                        'kind' => 'rendimento',
                        'amount' => $rend,
                        'occurred_on' => $data,
                        'note' => 'Rendimento creditado',
                    ]);
                    $alvo->increment('current_value', $rend);
                }
            }

            $this->command?->info("Populate concluído para '{$group->name}' (#{$group->id}).");
        });
    }

    /** Apaga os registros de domínio do grupo (mantém usuários e catálogo). */
    private function limpar($group): void
    {
        CardTransaction::where('group_id', $group->id)->delete();
        Contribution::where('group_id', $group->id)->delete();
        Transaction::where('group_id', $group->id)->delete();
        Asset::where('group_id', $group->id)->delete();
        Portfolio::where('group_id', $group->id)->delete();
        CreditCard::where('group_id', $group->id)->delete();
        Account::where('group_id', $group->id)->delete();
        $group->invitations()->delete();
        DB::table('attachments')->where('group_id', $group->id)->delete();
        DB::table('audit_logs')->where('group_id', $group->id)->delete();
    }

    /** Garante os membros do grupo (admin, parceiro e filho). */
    private function membros($group, User $admin): array
    {
        $parceiro = User::firstOrCreate(
            ['email' => 'maria@prumo.local'],
            [
                'name' => 'Maria Silva',
                'password' => Hash::make('Senha@123'),
                'group_id' => $group->id,
                'role' => 'co_admin',
                'phone' => '(11) 98888-7777',
                'birthdate' => Carbon::create(1988, 4, 12),
            ]
        );
        if ($parceiro->group_id !== $group->id) {
            $parceiro->update(['group_id' => $group->id, 'role' => 'co_admin']);
        }

        $filho = User::firstOrCreate(
            ['email' => 'pedro@prumo.local'],
            [
                'name' => 'Pedro Silva',
                'password' => Hash::make('Senha@123'),
                'group_id' => $group->id,
                'role' => 'junior',
                'phone' => '(11) 97777-6666',
                'birthdate' => Carbon::create(2012, 9, 3),
            ]
        );
        if ($filho->group_id !== $group->id) {
            $filho->update(['group_id' => $group->id, 'role' => 'junior']);
        }

        return ['admin' => $admin, 'parceiro' => $parceiro, 'filho' => $filho];
    }

    /** Contas ligadas aos bancos do catálogo. */
    private function contas($group): array
    {
        $porCodigo = fn (string $cod) => Bank::where('code', $cod)->first();
        $itau = $porCodigo('341');
        $nubank = $porCodigo('260');
        $inter = $porCodigo('077');
        $xp = $porCodigo('102');

        $criar = function (string $nome, $banco, string $tipo, float $saldo) use ($group) {
            return Account::create([
                'group_id' => $group->id,
                'bank_id' => $banco?->id,
                'name' => $nome,
                'kind' => $tipo,
                'initial_balance' => $saldo,
                'active' => true,
            ]);
        };

        return [
            'itau' => $criar('Conta Itaú', $itau, 'corrente', 4200),
            'nubank' => $criar('Nubank', $nubank, 'digital', 1500),
            'inter' => $criar('Poupança Inter', $inter, 'poupanca', 12000),
            'xp' => $criar('XP Investimentos', $xp, 'investimento', 18000),
        ];
    }

    /** Cartões vinculados às contas. */
    private function cartoes($group, array $contas, array $membros): array
    {
        $criar = fn (string $nome, $conta, string $bandeira, float $limite, int $fecha, int $vence) => CreditCard::create([
            'group_id' => $group->id,
            'holder_user_id' => $membros['admin']->id,
            'account_id' => $conta->id,
            'name' => $nome,
            'brand' => $bandeira,
            'credit_limit' => $limite,
            'closing_day' => $fecha,
            'due_day' => $vence,
            'active' => true,
        ]);

        return [
            $criar('Itaú Visa Infinite', $contas['itau'], 'visa', 25000, 5, 12),
            $criar('Nubank Mastercard', $contas['nubank'], 'master', 12000, 1, 10),
            $criar('Inter Gold', $contas['inter'], 'master', 8000, 3, 15),
        ];
    }

    /** Cria um lançamento comum. */
    private function transacao($group, array $dados): void
    {
        $dados['group_id'] = $group->id;
        Transaction::create($dados);
    }

    /** Cria um parcelamento (divide em centavos, 1x ao mês). */
    private function parcelas($group, User $membro, Account $conta, ?int $categoriaId, string $desc, float $total, int $qtd, Carbon $inicio, callable $statusPorData): void
    {
        $group = (string) Str::uuid();
        $totalCents = (int) round($total * 100);
        $base = intdiv($totalCents, $qtd);
        $resto = $totalCents % $qtd;

        for ($i = 1; $i <= $qtd; $i++) {
            $cents = $base + ($i <= $resto ? 1 : 0);
            $data = $inicio->copy()->addMonthsNoOverflow($i - 1);
            Transaction::create([
                'group_id' => $group->id,
                'user_id' => $membro->id,
                'account_id' => $conta->id,
                'category_id' => $categoriaId,
                'type' => 'despesa',
                'description' => "{$desc} ({$i}/{$qtd})",
                'amount' => $cents / 100,
                'occurred_on' => $data->toDateString(),
                'due_on' => $data->toDateString(),
                'status' => $i === 1 ? $statusPorData($data) : 'pendente',
                'installment_group_id' => $group,
                'installment_number' => $i,
                'installments_total' => $qtd,
            ]);
        }
    }
}
