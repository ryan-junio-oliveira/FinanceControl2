<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bank;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use App\Support\CategoryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['billing.enabled' => false]);
    }

    private function groupWithAdmin(): array
    {
        $group = Group::create(['name' => 'Grupo API']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@api.com', 'password' => bcrypt('Senha@123'),
            'group_id' => $group->id, 'role' => 'admin',
        ]);
        CategoryCatalog::seedForGroup($group);

        return [$group, $admin];
    }

    public function test_token_authentication(): void
    {
        [, $admin] = $this->groupWithAdmin();

        $this->postJson('/api/v1/auth/token', ['email' => 'admin@api.com', 'password' => 'Senha@123', 'device_name' => 'bot'])
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user']);

        $this->postJson('/api/v1/auth/token', ['email' => 'admin@api.com', 'password' => 'errada', 'device_name' => 'bot'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Credenciais inválidas. Verifique o e-mail e a senha e tente de novo.']);

        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_transactions_crud(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $this->actingAs($admin);
        $bank = Bank::create(['code' => '341', 'name' => 'Itaú']);
        $this->postJson('/api/v1/accounts', ['name' => 'Conta', 'bank_id' => $bank->id, 'kind' => 'corrente', 'initial_balance' => '0'])->assertCreated();
        $conta = Account::first();
        $cat = Category::where('group_id', $group->id)->where('type', 'despesa')->first();

        // create
        $res = $this->postJson('/api/v1/transactions/despesa', [
            'description' => 'Mercado', 'amount' => '150,50', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $cat->id,
        ])->assertCreated();
        $id = $res->json('data.id');
        $this->assertEquals(150.5, $res->json('data.amount'));

        // index + show
        $this->getJson('/api/v1/transactions/despesa')
            ->assertOk()->assertJsonFragment(['description' => 'Mercado']);
        $this->getJson("/api/v1/transactions/despesa/{$id}")
            ->assertOk()->assertJsonFragment(['description' => 'Mercado']);

        // update
        $this->patchJson("/api/v1/transactions/despesa/{$id}", ['description' => 'Mercado Central', 'amount' => '200', 'occurred_on' => now()->toDateString(), 'status' => 'pago', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $cat->id])
            ->assertOk()->assertJsonFragment(['description' => 'Mercado Central']);

        // settle
        $this->postJson("/api/v1/transactions/despesa/{$id}/settle")
            ->assertOk()->assertJsonFragment(['status' => 'pago']);

        // destroy
        $this->deleteJson("/api/v1/transactions/despesa/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('transactions', ['id' => $id]);
    }

    public function test_accounts_and_transfer(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $this->actingAs($admin);
        $bank = Bank::create(['code' => '341', 'name' => 'Itaú']);
        $this->postJson('/api/v1/accounts', ['name' => 'A', 'bank_id' => $bank->id, 'kind' => 'corrente', 'initial_balance' => '1000'])->assertCreated();
        $this->postJson('/api/v1/accounts', ['name' => 'B', 'bank_id' => $bank->id, 'kind' => 'digital', 'initial_balance' => '0'])->assertCreated();
        $a = Account::where('name', 'A')->first();
        $b = Account::where('name', 'B')->first();

        $this->postJson('/api/v1/accounts/transfer', [
            'from_account_id' => $a->id, 'to_account_id' => $b->id,
            'amount' => '250', 'occurred_on' => now()->toDateString(), 'user_id' => $admin->id,
        ])->assertCreated();

        $this->assertEquals(750.0, (float) $a->fresh()->balance);
        $this->assertEquals(250.0, (float) $b->fresh()->balance);
    }

    public function test_cards_and_items(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $this->actingAs($admin);
        $bank = Bank::create(['code' => '341', 'name' => 'Itaú']);
        $this->postJson('/api/v1/accounts', ['name' => 'C', 'bank_id' => $bank->id, 'kind' => 'corrente', 'initial_balance' => '0'])->assertCreated();
        $conta = Account::first();
        $this->postJson('/api/v1/cards', ['name' => 'Nubank', 'credit_limit' => '5000', 'closing_day' => 1, 'due_day' => 10, 'holder_user_id' => $admin->id, 'account_id' => $conta->id])->assertCreated();
        $card = CreditCard::first();
        $cat = Category::where('group_id', $group->id)->where('type', 'despesa')->first();

        $this->postJson('/api/v1/cards/items', [
            'credit_card_id' => $card->id, 'description' => 'Compra', 'amount' => '120',
            'occurred_on' => now()->toDateString(), 'user_id' => $admin->id, 'category_id' => $cat->id,
        ])->assertCreated();
        $this->assertEquals(120.0, (float) $card->fresh()->open_invoice);

        $item = CardTransaction::first();
        $this->postJson("/api/v1/cards/items/{$item->id}/settle")
            ->assertOk()->assertJsonFragment(['status' => 'pago']);
    }

    public function test_dashboard_and_audit_logs(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $this->actingAs($admin);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure(['kpi', 'patrimonio', 'a_pagar', 'cartoes', 'investimentos', 'charts']);

        // admin vê os logs
        $this->getJson('/api/v1/admin/logs')->assertOk();

        // co_admin não vê
        $co = User::create(['name' => 'Co', 'email' => 'co@api.com', 'password' => bcrypt('Senha@123'), 'group_id' => $group->id, 'role' => 'co_admin']);
        $this->actingAs($co);
        $this->getJson('/api/v1/admin/logs')->assertForbidden();
    }

    public function test_group_members_and_invites(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $this->actingAs($admin);

        $this->postJson('/api/v1/group/invites', ['name' => 'Maria', 'email' => 'maria@api.com', 'role' => 'dependente'])->assertCreated();
        $this->getJson('/api/v1/group/invites')
            ->assertOk()->assertJsonFragment(['email' => 'maria@api.com']);

        $invite = Invitation::first();
        $this->deleteJson("/api/v1/group/invites/{$invite->id}")->assertNoContent();
    }

    public function test_logout_revokes_token(): void
    {
        [, $admin] = $this->groupWithAdmin();
        $token = $admin->createToken('bot')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $admin->id]);

        // Simula um request real (app fresca): limpa os guards cacheados no teste.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
    }
}
