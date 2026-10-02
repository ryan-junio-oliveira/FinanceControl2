<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Bank;
use App\Models\CardTransaction;
use App\Models\Family;
use App\Models\Invitation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function family(string $plan = 'pro_trial', bool $trial = true): Family
    {
        return Family::create([
            'name' => 'Família Sec',
            'plan' => $plan,
            'trial_ends_at' => $trial ? now()->addDays(5) : now()->subDay(),
        ]);
    }

    private function member(Family $family, string $role = 'admin'): User
    {
        return User::create([
            'name' => 'User', 'email' => $role.'.'.random_int(1, 999999).'@sec.com', 'password' => bcrypt('Senha@123'),
            'family_id' => $family->id, 'role' => $role, 'bot_code' => (string) random_int(100000, 999999),
        ]);
    }

    public function test_security_headers_present(): void
    {
        $res = $this->get('/login');
        $res->assertOk();
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $res->headers->get('X-Frame-Options'));
        $this->assertNotNull($res->headers->get('Referrer-Policy'));
        $this->assertNotNull($res->headers->get('Permissions-Policy'));
    }

    public function test_api_blocked_when_plan_expired(): void
    {
        config(['billing.enabled' => true]);
        $family = $this->family('pro_trial', false);
        $token = $this->member($family)->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/accounts')->assertForbidden();
    }

    public function test_api_manager_routes_require_admin_role(): void
    {
        config(['billing.enabled' => true]);
        $family = $this->family('pro_trial', true);
        $dep = $this->member($family, 'dependente');
        $token = $dep->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/accounts', [
            'name' => 'Conta X', 'kind' => 'corrente', 'initial_balance' => '0',
        ])->assertForbidden();

        $this->withToken($token)->postJson('/api/v1/family/invites', [
            'name' => 'Novo', 'email' => 'novo@sec.com', 'role' => 'dependente',
        ])->assertForbidden();
    }

    public function test_expired_invite_returns_410(): void
    {
        $family = $this->family();
        $inv = Invitation::create([
            'family_id' => $family->id, 'name' => 'Lucas', 'email' => 'lucas@sec.com',
            'role' => 'dependente', 'token' => Str::random(48),
            'expires_at' => now()->subDay(),
        ]);

        $this->get("/first-access/{$inv->token}")->assertStatus(410);
        $this->post("/first-access/{$inv->token}", [
            'password' => 'Filho@123', 'password_confirmation' => 'Filho@123',
        ])->assertStatus(410);
    }

    public function test_invite_without_expiry_still_works(): void
    {
        $family = $this->family();
        $inv = Invitation::create([
            'family_id' => $family->id, 'name' => 'Lucas', 'email' => 'lucas@sec.com',
            'role' => 'dependente', 'token' => Str::random(48),
        ]);

        $this->get("/first-access/{$inv->token}")->assertOk();
    }

    public function test_attachment_download_sanitizes_filename(): void
    {
        config(['billing.enabled' => false]);
        Storage::fake('local');
        $family = $this->family();
        $admin = $this->member($family);
        Storage::disk('local')->put('anexos/1/fake.pdf', 'bytes');
        $ax = Attachment::create([
            'family_id' => $family->id, 'user_id' => $admin->id,
            'attachable_type' => User::class, 'attachable_id' => $admin->id,
            'path' => 'anexos/1/fake.pdf', 'original_name' => "nota\"\r\nfiscal.pdf", 'mime' => 'application/pdf', 'size' => 5,
        ]);

        $res = $this->actingAs($admin)->get(route('anexos.download', $ax));
        $res->assertOk();
        $disp = (string) $res->headers->get('Content-Disposition');
        $this->assertStringNotContainsString('"', str_replace(['filename=', 'attachment;'], '', $disp));
        $this->assertStringNotContainsString("\n", $disp);
    }

    public function test_cross_family_records_return_404(): void
    {
        config(['billing.enabled' => false]);
        $a = Family::create(['name' => 'Família A']);
        $adminA = User::create([
            'name' => 'A', 'email' => 'a@sec.com', 'password' => bcrypt('Senha@123'),
            'family_id' => $a->id, 'role' => 'admin', 'bot_code' => (string) random_int(100000, 999999),
        ]);
        $tx = Transaction::create([
            'family_id' => $a->id, 'user_id' => $adminA->id, 'type' => 'despesa',
            'description' => 'X', 'amount' => 10, 'occurred_on' => now()->toDateString(), 'status' => 'pago',
        ]);

        $b = Family::create(['name' => 'Família B']);
        $adminB = $this->member($b);

        $this->actingAs($adminB)->get(route('lancamentos.edit', $tx))->assertNotFound();

        $token = $adminB->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson("/api/v1/transactions/despesa/{$tx->id}")->assertNotFound();
    }

    public function test_delete_with_movements_returns_422(): void
    {
        config(['billing.enabled' => false]);
        $family = $this->family();
        $admin = $this->member($family);
        $bank = Bank::create(['code' => '341', 'name' => 'Itaú', 'color' => '#EC7000']);
        $conta = $family->accounts()->create([
            'bank_id' => $bank->id, 'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);
        Transaction::create([
            'family_id' => $family->id, 'user_id' => $admin->id, 'account_id' => $conta->id,
            'type' => 'despesa', 'description' => 'X', 'amount' => 10,
            'occurred_on' => now()->toDateString(), 'status' => 'pago',
        ]);
        $this->actingAs($admin)->delete(route('contas.destroy', $conta))->assertStatus(422);

        $card = $family->creditCards()->create([
            'name' => 'Cartão', 'credit_limit' => 1000, 'closing_day' => 10, 'due_day' => 15, 'active' => true,
        ]);
        CardTransaction::create([
            'family_id' => $family->id, 'credit_card_id' => $card->id, 'user_id' => $admin->id,
            'description' => 'Compra', 'amount' => 50, 'occurred_on' => now()->toDateString(), 'status' => 'pendente',
        ]);
        $this->actingAs($admin)->delete(route('cartoes.destroy', $card))->assertStatus(422);
    }

    public function test_transfer_same_account_fails_validation(): void
    {
        config(['billing.enabled' => false]);
        $family = $this->family();
        $admin = $this->member($family);
        $bank = Bank::create(['code' => '341', 'name' => 'Itaú', 'color' => '#EC7000']);
        $conta = $family->accounts()->create([
            'bank_id' => $bank->id, 'name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0,
        ]);

        $this->actingAs($admin)->post(route('contas.transfer'), [
            'from_account_id' => $conta->id, 'to_account_id' => $conta->id,
            'amount' => '10', 'occurred_on' => now()->toDateString(), 'user_id' => $admin->id,
        ])->assertSessionHasErrors('to_account_id');
    }

    public function test_secret_phrase_too_long_rejected(): void
    {
        config(['billing.enabled' => false]);
        $family = $this->family();
        $admin = $this->member($family);

        $this->actingAs($admin)->post(route('familia.secret'), [
            'secret_phrase' => str_repeat('a', 81),
        ])->assertStatus(422);
    }

    public function test_invite_accept_marks_blacklist(): void
    {
        config(['billing.enabled' => false]);
        Mail::fake();
        $this->post('/register', [
            'manager_name' => 'Ana', 'email' => 'ana@sec.com', 'family_name' => 'Família Ana',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123', 'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $admin = User::where('email', 'ana@sec.com')->first();

        $this->actingAs($admin)->post('/family/invites', [
            'name' => 'Lucas', 'email' => 'lucas@sec.com', 'role' => 'dependente',
        ])->assertSessionHasNoErrors();

        $this->post('/logout');
        $inv = Invitation::where('email', 'lucas@sec.com')->first();
        $this->post("/first-access/{$inv->token}", [
            'password' => 'Filho@123', 'password_confirmation' => 'Filho@123',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('trial_blacklist', ['identifier' => 'lucas@sec.com', 'type' => 'email']);
    }

    public function test_landing_redirects_authenticated(): void
    {
        config(['billing.enabled' => false]);
        $family = $this->family();
        $this->actingAs($this->member($family))->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_profile_export_contains_expected_keys(): void
    {
        config(['billing.enabled' => false]);
        $this->post('/register', [
            'manager_name' => 'Eva', 'email' => 'eva@sec.com', 'family_name' => 'Família Eva',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123', 'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $data = $this->get('/profile/export')->assertOk()->json();
        foreach (['exportado_em', 'usuario', 'familia', 'membros', 'categorias', 'contas', 'cartoes', 'lancamentos', 'itens_de_fatura', 'ativos', 'aportes_e_rendimentos'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }
}
