<?php

namespace Tests\Feature;

use App\Bot\BotManager;
use App\Bot\ConversationState;
use App\Bot\Drivers\NullDriver;
use App\Models\Bank;
use App\Models\BotIdentity;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CategoryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertStringContainsString('FinFamília', (string) NullDriver::lastText());
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

        // Conta (só 1) → categoria.
        $this->send('1');
        $state = ConversationState::get('telegram', '99');
        $this->assertSame('category', $state['step']);

        // Descobre o número da categoria criada.
        $cats = $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get();
        $num = $cats->search(fn ($c) => $c->id === $cat->id) + 1;
        $this->send((string) $num);
        $this->assertStringContainsString('Confirmar', (string) NullDriver::lastText());

        $this->send('sim');
        $this->assertDatabaseHas('transactions', [
            'description' => 'Mercado semanal', 'type' => 'despesa', 'user_id' => $admin->id,
        ]);
        $tx = Transaction::where('description', 'Mercado semanal')->first();
        $this->assertEquals(350.75, (float) $tx->amount);
        $this->assertStringContainsString('registrada', (string) NullDriver::lastText());
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
        $this->assertStringContainsString('FinFamília', (string) NullDriver::lastText());
    }

    public function test_manager_resolves_null_driver(): void
    {
        $this->assertSame('null', BotManager::driver()->name());
        $this->assertSame('telegram', BotManager::driver('telegram')->name());
    }
}
