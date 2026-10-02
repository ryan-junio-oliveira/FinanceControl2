<?php

namespace Tests\Feature;

use App\Bot\BotManager;
use App\Bot\ConversationState;
use App\Bot\Drivers\NullDriver;
use App\Models\Asset;
use App\Models\Bank;
use App\Models\BotIdentity;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CategoryCatalog;
use App\Support\MarketData;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['bot.driver' => 'null']);
        NullDriver::flush();
    }

    private function familyWithLinkedUser(): array
    {
        $family = Family::create(['name' => 'Família Bot']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@bot.com', 'password' => bcrypt('Senha@123'),
            'family_id' => $family->id, 'role' => 'admin', 'bot_code' => '123456',
        ]);
        CategoryCatalog::seedForFamily($family);
        Bank::create(['code' => '341', 'name' => 'Itaú', 'color' => '#EC7000']);

        return [$family, $admin];
    }

    private function send(string $text, string $chat = '99'): void
    {
        $this->postJson('/api/bot/telegram', [
            'message' => ['chat' => ['id' => $chat], 'text' => $text, 'from' => ['first_name' => 'Admin']],
        ])->assertOk();
    }

    public function test_link_and_menu(): void
    {
        [, $admin] = $this->familyWithLinkedUser();

        // Sem vínculo: pede o código.
        $this->send('oi');
        $this->assertStringContainsString('código', (string) NullDriver::lastText());

        // Código errado.
        NullDriver::flush();
        $this->send('000000');
        $this->assertStringContainsString('inválido', (string) NullDriver::lastText());
        $this->assertDatabaseMissing('bot_identities', ['external_id' => '99']);

        // /start com código vincula e mostra o menu.
        NullDriver::flush();
        $this->send('/start 123456');
        $this->assertDatabaseHas('bot_identities', ['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $this->assertStringContainsString('Prumo', (string) NullDriver::lastText());
    }

    public function test_dashboard_query(): void
    {
        [, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);

        $this->send('1');
        $text = (string) NullDriver::lastText();
        $this->assertStringContainsString('Dados financeiros', $text);
        $this->assertStringContainsString('Receitas:', $text);
    }

    public function test_expense_create_flow(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        Bank::where('code', '341')->firstOrFail();
        $conta = $admin->family->accounts()->create([
            'bank_id' => Bank::where('code', '341')->first()->id,
            'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);
        $cat = $family->categories()->where('type', 'despesa')->firstOrFail();

        $this->send('2'); // Despesas
        $this->send('despesa:new'); // Lançar
        $this->send('Mercado semanal');
        $this->send('350,75');
        $this->send('hoje');
        $this->send('pix'); // Forma de pagamento.

        // Conta (só 1) → categoria.
        $this->send('1');
        $state = ConversationState::get('telegram', '99');
        $this->assertSame('category', $state['step']);

        // Descobre o número da categoria criada.
        $cats = $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get();
        $num = $cats->search(fn ($c) => $c->id === $cat->id) + 1;
        $this->send((string) $num);

        // Agora pergunta se é fixa/recorrente.
        $this->assertStringContainsString('fixa', (string) NullDriver::lastText());
        $this->assertSame('fixed', ConversationState::get('telegram', '99')['step']);
        $this->send('nao');
        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());

        $this->send('sim');
        $this->assertDatabaseHas('transactions', [
            'description' => 'Mercado semanal', 'type' => 'despesa', 'user_id' => $admin->id,
        ]);
        $tx = Transaction::where('description', 'Mercado semanal')->first();
        $this->assertEquals(350.75, (float) $tx->amount);
        $this->assertFalse((bool) $tx->is_fixed);
        $this->assertStringContainsString('registrada', (string) NullDriver::lastText());
    }

    public function test_expense_fixed_flow(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $conta = $admin->family->accounts()->create([
            'bank_id' => Bank::where('code', '341')->first()->id,
            'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);
        $cat = $family->categories()->where('type', 'despesa')->firstOrFail();

        $this->send('2');
        $this->send('despesa:new');
        $this->send('Aluguel');
        $this->send('1200');
        $this->send('hoje');
        $this->send('pix'); // Forma de pagamento.
        $this->send('1'); // Conta (só 1) → categoria.

        $cats = $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get();
        $num = $cats->search(fn ($c) => $c->id === $cat->id) + 1;
        $this->send((string) $num);

        // É fixa → sim → pergunta o dia do pagamento.
        $this->send('sim');
        $this->assertSame('dueday', ConversationState::get('telegram', '99')['step']);
        $this->assertStringContainsString('dia', (string) NullDriver::lastText());

        $this->send('10');
        $this->assertStringContainsString('Fixa', (string) NullDriver::lastText());
        $this->assertStringContainsString('dia 10', (string) NullDriver::lastText());
        $this->send('sim');

        $tx = Transaction::where('description', 'Aluguel')->first();
        $this->assertNotNull($tx);
        $this->assertTrue((bool) $tx->is_fixed);
        $this->assertSame(10, (int) Carbon::parse($tx->due_on)->day);
        $this->assertStringContainsString('registrada', (string) NullDriver::lastText());
    }

    public function test_expense_payment_card_flow(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $family->creditCards()->create([
            'name' => 'Inter Mastercard', 'brand' => 'mastercard',
            'credit_limit' => 5000, 'closing_day' => 6, 'due_day' => 12, 'active' => true,
        ]);

        $this->send('2');
        $this->send('despesa:new');
        $this->send('iFood');
        $this->send('89,90');
        $this->send('hoje');
        $this->send('cartao'); // Forma de pagamento → qual cartão?

        $this->assertStringContainsString('qual cartão', (string) NullDriver::lastText());
        $this->send('1'); // Único cartão.
        $this->send('1'); // Primeira categoria.

        $this->assertStringContainsString('Confirmar compra no cartão', (string) NullDriver::lastText());
        $this->send('sim');

        $this->assertDatabaseHas('card_transactions', [
            'description' => 'iFood', 'amount' => 89.90, 'status' => 'pendente',
        ]);
        $this->assertDatabaseMissing('transactions', ['description' => 'iFood']);
        $this->assertStringContainsString('fatura do', (string) NullDriver::lastText());
    }

    public function test_income_dinheiro_fisico_uses_carteira(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $family->accounts()->create([
            'bank_id' => Bank::where('code', '341')->first()->id,
            'name' => 'Corrente', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);

        $this->send('3'); // Receitas
        $this->send('receita:new');
        $this->send('Venda de garagem');
        $this->send('120');
        $this->send('hoje');
        $this->send('dinheiro_fisico'); // Não pergunta conta → direto à categoria.

        $this->assertStringContainsString('categoria', (string) NullDriver::lastText());
        $this->send('1');
        $this->send('nao'); // não é fixa
        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());
        $this->send('sim');

        $tx = Transaction::where('description', 'Venda de garagem')->first();
        $this->assertNotNull($tx);
        $this->assertSame('dinheiro_fisico', $tx->payment_method);
        $carteira = $family->accounts()->where('kind', 'carteira')->first();
        $this->assertNotNull($carteira);
        $this->assertSame($carteira->id, $tx->account_id);
    }

    public function test_investment_aporte_flow(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $conta = $family->accounts()->create([
            'bank_id' => Bank::where('code', '341')->first()->id,
            'name' => 'Corrente', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);

        $this->send('6'); // Investimentos → submenu.
        $this->send('investimentos:new');
        $this->send('aporte');
        $this->send('1'); // Conta.
        $this->send('500');
        $this->send('hoje');

        $this->assertStringContainsString('Confirmar aporte', (string) NullDriver::lastText());
        $this->send('sim');

        $this->assertDatabaseHas('contributions', ['kind' => 'aporte', 'amount' => 500, 'account_id' => $conta->id]);
        $this->assertDatabaseHas('transactions', ['type' => 'aporte', 'amount' => 500]);
        $this->assertEquals(-500, (float) $conta->fresh()->balance);
        $this->assertStringContainsString('Aporte registrado', (string) NullDriver::lastText());
    }

    public function test_investment_rendimento_flow(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $ativo = Asset::create([
            'family_id' => $family->id, 'code' => 'SELIC', 'name' => 'Tesouro Selic', 'kind' => 'renda_fixa', 'current_value' => 10000,
        ]);

        $this->send('6');
        $this->send('investimentos:new');
        $this->send('rendimento');
        $this->send('1'); // Ativo.
        $this->send('120,50');
        $this->send('hoje');

        $this->assertStringContainsString('Confirmar rendimento', (string) NullDriver::lastText());
        $this->send('sim');

        $this->assertDatabaseHas('contributions', ['kind' => 'rendimento', 'amount' => 120.50, 'asset_id' => $ativo->id]);
        $this->assertEquals(10120.50, (float) $ativo->fresh()->current_value);
    }

    public function test_income_deposito_transfers_carteira_to_account(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $conta = $family->accounts()->create([
            'bank_id' => Bank::where('code', '341')->first()->id,
            'name' => 'Corrente', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);

        $this->send('3'); // Receitas
        $this->send('receita:new');
        $this->send('Depósito da feira');
        $this->send('300');
        $this->send('hoje');
        $this->send('deposito');
        $this->send('1'); // Conta de destino (não-carteira).
        $this->send('1'); // Categoria.
        $this->send('nao'); // não é fixa.

        $this->assertStringContainsString('Depósito', (string) NullDriver::lastText());
        $this->send('sim');

        $this->assertDatabaseHas('transactions', ['type' => 'transferencia', 'amount' => 300]);
        $this->assertDatabaseMissing('transactions', ['type' => 'receita', 'description' => 'Depósito da feira']);
        $carteira = $family->accounts()->where('kind', 'carteira')->first();
        $this->assertNotNull($carteira);
        $this->assertStringContainsString('Depósito registrado', (string) NullDriver::lastText());
    }

    public function test_account_create_flow(): void
    {
        [, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);

        $this->send('5'); // Contas → submenu.
        $this->assertStringContainsString('o que deseja', (string) NullDriver::lastText());

        $this->send('contas:new');
        $this->send('Nubank');
        $this->send('341'); // Busca por código → Itaú (único).

        $this->assertStringContainsString('tipo', (string) NullDriver::lastText());
        $this->send('1'); // Corrente.
        $this->send('500,25');

        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());
        $this->assertStringContainsString('Itaú', (string) NullDriver::lastText());
        $this->send('sim');

        $this->assertDatabaseHas('accounts', [
            'name' => 'Nubank', 'kind' => 'corrente', 'initial_balance' => 500.25, 'active' => true,
        ]);
        $this->assertStringContainsString('cadastrada', (string) NullDriver::lastText());
    }

    public function test_account_create_bank_multiple_choice(): void
    {
        [, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        Bank::create(['code' => '077', 'name' => 'Banco Inter', 'color' => '#FF6F00']);

        $this->send('5');
        $this->send('contas:new');
        $this->send('Minha Inter');
        $this->send('a'); // "a" em Itaú e Banco Inter → lista numerada.

        $this->assertStringContainsString('vários bancos', (string) NullDriver::lastText());
        $this->send('1'); // "Banco Inter" vem antes de "Itaú" (ordem alfabética).

        $this->assertStringContainsString('tipo', (string) NullDriver::lastText());
        $this->send('1');
        $this->send('0');
        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());
        $this->assertStringContainsString('Banco Inter', (string) NullDriver::lastText());
    }

    public function test_card_create_flow(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $family->accounts()->create([
            'bank_id' => Bank::where('code', '341')->first()->id,
            'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);

        $this->send('4'); // Cartões → submenu.
        $this->assertStringContainsString('o que deseja', (string) NullDriver::lastText());

        $this->send('cartoes:create');
        $this->send('Nubank Ultravioleta');
        $this->send('1'); // Visa.

        // Membro único pula o titular; pergunta a conta vinculada → "Sem conta".
        $this->send('2');
        $this->send('5000');
        $this->send('10'); // fechamento
        $this->send('15'); // vencimento

        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());
        $this->assertStringContainsString('Nubank Ultravioleta', (string) NullDriver::lastText());
        $this->send('sim');

        $this->assertDatabaseHas('credit_cards', [
            'name' => 'Nubank Ultravioleta', 'brand' => 'visa', 'holder_user_id' => $admin->id,
            'credit_limit' => 5000, 'closing_day' => 10, 'due_day' => 15, 'active' => true,
        ]);
        $this->assertStringContainsString('cadastrado', (string) NullDriver::lastText());
    }

    public function test_cancel_clears_state(): void
    {
        [, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);

        $this->send('2');
        $this->send('despesa:new');
        $this->assertNotNull(ConversationState::get('telegram', '99'));
        $this->send('cancelar');
        $this->assertNull(ConversationState::get('telegram', '99'));
        $this->assertStringContainsString('Prumo', (string) NullDriver::lastText());
    }

    public function test_vencimento_alerts(): void
    {
        [$family, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);
        $family->setting()->update(['notifications' => ['conta_vencimento' => true, 'fatura_vencimento' => false]]);

        $bank = Bank::firstOrCreate(['code' => '341'], ['name' => 'Itaú', 'color' => '#EC7000']);
        $conta = $admin->family->accounts()->create([
            'bank_id' => $bank->id, 'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);
        $cat = $family->categories()->where('type', 'despesa')->firstOrFail();
        $mk = fn (string $desc, string $due) => Transaction::create([
            'family_id' => $family->id, 'user_id' => $admin->id, 'account_id' => $conta->id,
            'category_id' => $cat->id, 'type' => 'despesa', 'description' => $desc,
            'amount' => 100, 'occurred_on' => now()->toDateString(), 'due_on' => $due, 'status' => 'pendente',
        ]);
        $mk('Conta de luz', now()->addDay()->toDateString());
        $mk('Internet futura', now()->addDays(10)->toDateString());

        NullDriver::flush();
        $this->artisan('notify:vencimentos')->assertSuccessful();

        $text = (string) NullDriver::lastText();
        $this->assertStringContainsString('Vencimentos próximos', $text);
        $this->assertStringContainsString('Conta de luz', $text);
        $this->assertStringContainsString('amanhã', $text);
        $this->assertStringNotContainsString('Internet futura', $text);
    }

    public function test_manager_resolves_null_driver(): void
    {
        $this->assertSame('null', BotManager::driver()->name());
        $this->assertSame('telegram', BotManager::driver('telegram')->name());
    }

    public function test_market_menu(): void
    {
        [, $admin] = $this->familyWithLinkedUser();
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);

        // Sem rede: snapshot vazio + ações vazias → mostra "indisponível" sem quebrar.
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response(['chart' => ['result' => []]]),
            '*' => Http::response(null, 500),
        ]);

        $this->send('7');
        $text = (string) NullDriver::lastText();
        $this->assertStringContainsString('Mercado', $text);
        $this->assertStringContainsString('Selic', $text);
        $this->assertStringContainsString('Bitcoin', $text);
    }

    public function test_market_movers_sorting(): void
    {
        Http::fake(function ($request) {
            $url = (string) $request->url();
            if (str_contains($url, 'brapi')) {
                return Http::response(['stocks' => [], 'hasNextPage' => false]);
            }
            $change = str_contains($url, 'PETR4') ? 3.5 : (str_contains($url, 'VALE3') ? -2.25 : (str_contains($url, 'HGLG11') ? 1.5 : 0.5));

            return Http::response([
                'chart' => ['result' => [[
                    'meta' => ['regularMarketPrice' => 10, 'regularMarketChangePercent' => $change, 'shortName' => 'X'],
                ]]],
            ]);
        });

        $movers = MarketData::movers(5);
        $this->assertSame('PETR4', $movers['up'][0]['code']);
        $this->assertSame('acao', $movers['up'][0]['kind']);
        $this->assertSame('VALE3', $movers['down'][0]['code']);

        // FIIs aparecem junto, rotulados.
        $fii = collect(array_merge($movers['up'], $movers['down']))->firstWhere('code', 'HGLG11');
        $this->assertNotNull($fii);
        $this->assertSame('fii', $fii['kind']);
    }
}
