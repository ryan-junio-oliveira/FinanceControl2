<?php

namespace Tests\Feature;

use App\Bot\ConversationState;
use App\Bot\Drivers\NullDriver;
use App\Models\Bank;
use App\Models\BotIdentity;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use App\Receipts\Contracts\ReceiptReader;
use App\Receipts\ReceiptParser;
use App\Support\CategoryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeReceiptReader implements ReceiptReader
{
    public function __construct(private string $text) {}

    public function name(): string
    {
        return 'fake';
    }

    public function supports(string $mime): bool
    {
        return true;
    }

    public function read(string $path): string
    {
        return $this->text;
    }
}

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['bot.driver' => 'null']);
        config(['billing.enabled' => false]);
        NullDriver::flush();
        NullDriver::$fixturePath = null;
    }

    public function test_parser_understands_pix_sent_and_received(): void
    {
        $out = ReceiptParser::parse("Comprovante Pix\nPix enviado\nValor: R$ 350,75\nData: 05/10/2026\nFavorecido: Maria Silva\nNubank\n");
        $this->assertSame('pix', $out['channel']);
        $this->assertSame('out', $out['direction']);
        $this->assertSame('despesa', $out['type']);
        $this->assertEquals(350.75, $out['amount']);
        $this->assertSame('2026-10-05', $out['date']);
        $this->assertSame('Nubank', $out['bank']);
        $this->assertSame([], $out['missing']);

        $in = ReceiptParser::parse("Pix recebido\nValor R$ 1.200,00\n20/09/2026\n");
        $this->assertSame('in', $in['direction']);
        $this->assertSame('receita', $in['type']);
        $this->assertEquals(1200.0, $in['amount']);
    }

    public function test_parser_detects_boleto_and_missing_fields(): void
    {
        $out = ReceiptParser::parse("Comprovante de pagamento de boleto\nLinha digitável 23793...\n");
        $this->assertSame('boleto', $out['channel']);
        $this->assertSame('out', $out['direction']);
        $this->assertContains('amount', $out['missing']);
        $this->assertContains('date', $out['missing']);
    }

    public function test_parser_does_not_use_bank_name_as_description(): void
    {
        $out = ReceiptParser::parse("Nubank\nComprovante de Pix\nPix enviado\nValor: R$ 50,00\nData: 05/10/2026\n");
        $this->assertSame('Nubank', $out['bank']);
        $this->assertNotSame('Nubank', $out['description']);
        $this->assertNotSame('Comprovante de Pix', $out['description']);
    }

    public function test_parser_uses_structured_description_field(): void
    {
        $out = ReceiptParser::parse("Banco Inter\nPix recebido\nDescrição: Salário de outubro\nValor: R$ 5.000,00\nData: 05/10/2026\n");
        $this->assertSame('Salário de outubro', $out['description']);
    }

    private function linkedFamily(): array
    {
        $family = Family::create(['name' => 'Família Bot']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@bot.com', 'password' => bcrypt('Senha@123'),
            'family_id' => $family->id, 'role' => 'admin', 'bot_code' => '123456',
        ]);
        CategoryCatalog::seedForFamily($family);
        $bank = Bank::create(['code' => '341', 'name' => 'Itaú', 'color' => '#EC7000']);
        $conta = $family->accounts()->create([
            'bank_id' => $bank->id, 'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);
        BotIdentity::create(['user_id' => $admin->id, 'channel' => 'telegram', 'external_id' => '99']);

        return [$family, $admin, $conta];
    }

    private function photo(string $chat = '99'): void
    {
        $this->postJson('/api/bot/telegram', [
            'message' => [
                'chat' => ['id' => $chat],
                'photo' => [['file_id' => 'FILE123', 'file_size' => 999]],
                'from' => ['first_name' => 'Admin'],
            ],
        ])->assertOk();
    }

    private function send(string $text, string $chat = '99'): void
    {
        $this->postJson('/api/bot/telegram', [
            'message' => ['chat' => ['id' => $chat], 'text' => $text, 'from' => ['first_name' => 'Admin']],
        ])->assertOk();
    }

    public function test_receipt_flow_creates_transaction_with_attachment(): void
    {
        [$family, $admin] = $this->linkedFamily();

        $fixture = tempnam(sys_get_temp_dir(), 'rcp').'.png';
        file_put_contents($fixture, 'fake-bytes');
        NullDriver::$fixturePath = $fixture;
        app()->instance(ReceiptReader::class, new FakeReceiptReader(
            "Comprovante Pix\nPix enviado\nValor: R$ 350,75\nData: 05/10/2026\nFavorecido: Supermercado X\n"
        ));

        $this->photo(); // baixa + lê + mostra revisão
        $this->send('ok'); // mantém descrição

        $state = ConversationState::get('telegram', '99');
        $this->assertSame('account', $state['step']);

        $this->send('1'); // conta
        $cats = $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get();
        $supermercado = $cats->firstWhere('name', 'Alimentação');
        $num = $cats->search(fn ($c) => $c->id === $supermercado->id) + 1;
        $this->send((string) $num); // categoria
        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());

        $this->send('sim');
        $tx = Transaction::where('description', 'Supermercado X')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(350.75, (float) $tx->amount);
        $this->assertEquals('despesa', $tx->type);
        $this->assertEquals('2026-10-05', $tx->occurred_on->format('Y-m-d'));
        $this->assertSame(1, $tx->attachments()->count());
        $this->assertStringContainsString('registrado', (string) NullDriver::lastText());
    }

    public function test_receipt_auto_selects_account_by_bank(): void
    {
        [$family, $admin] = $this->linkedFamily();
        $nubank = Bank::create(['code' => '260', 'name' => 'Nubank', 'color' => '#820AD1']);
        $contaNubank = $family->accounts()->create([
            'bank_id' => $nubank->id, 'name' => 'Nubank Conta', 'kind' => 'digital', 'initial_balance' => 0,
        ]);

        $fixture = tempnam(sys_get_temp_dir(), 'rcp').'.png';
        file_put_contents($fixture, 'fake-bytes');
        NullDriver::$fixturePath = $fixture;
        app()->instance(ReceiptReader::class, new FakeReceiptReader(
            "Nubank\nComprovante de Pix\nPix recebido\nValor: R$ 100,00\nData: 05/10/2026\n"
        ));

        $this->photo();
        $this->send('ok'); // mantém a descrição

        $state = ConversationState::get('telegram', '99');
        $this->assertSame('category', $state['step']);
        $this->assertEquals($contaNubank->id, $state['data']['account_id']);
    }

    public function test_receipt_bank_with_multiple_accounts_asks_choice(): void
    {
        [$family, $admin] = $this->linkedFamily();
        $nubank = Bank::create(['code' => '260', 'name' => 'Nubank', 'color' => '#820AD1']);
        $family->accounts()->create(['bank_id' => $nubank->id, 'name' => 'Nubank A', 'kind' => 'digital', 'initial_balance' => 0]);
        $family->accounts()->create(['bank_id' => $nubank->id, 'name' => 'Nubank B', 'kind' => 'digital', 'initial_balance' => 0]);

        $fixture = tempnam(sys_get_temp_dir(), 'rcp').'.png';
        file_put_contents($fixture, 'fake-bytes');
        NullDriver::$fixturePath = $fixture;
        app()->instance(ReceiptReader::class, new FakeReceiptReader(
            "Nubank\nComprovante de Pix\nPix recebido\nValor: R$ 100,00\nData: 05/10/2026\n"
        ));

        $this->photo();
        $this->send('ok');

        $state = ConversationState::get('telegram', '99');
        $this->assertSame('account', $state['step']);
        $this->assertCount(2, $state['data']['_accounts']);
        $this->assertStringContainsString('Nubank', (string) NullDriver::lastText());
    }

    public function test_unreadable_receipt_falls_back_to_manual(): void
    {
        [$family, $admin] = $this->linkedFamily();

        $fixture = tempnam(sys_get_temp_dir(), 'rcp').'.png';
        file_put_contents($fixture, 'x');
        NullDriver::$fixturePath = $fixture;
        app()->instance(ReceiptReader::class, new FakeReceiptReader(''));

        $this->photo();
        $this->assertStringContainsString('manualmente', (string) NullDriver::lastText());
        $this->send('despesa');
        $this->assertSame('amount', ConversationState::get('telegram', '99')['step']);
    }
}
