<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\TrialBlacklist;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private function familyWith(string $plan, ?string $trial, ?string $paidUntil = null): Family
    {
        return Family::create([
            'name' => 'Família Billing',
            'plan' => $plan,
            'trial_ends_at' => $trial ? now()->addDays(5) : now()->subDay(),
            'plan_paid_until' => $paidUntil,
        ]);
    }

    private function member(Family $family): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin'.random_int(1, 999999).'@billing.com', 'password' => bcrypt('Senha@123'),
            'family_id' => $family->id, 'role' => 'admin', 'bot_code' => (string) random_int(100000, 999999),
        ]);
    }

    public function test_middleware_blocks_when_plan_expired(): void
    {
        config(['billing.enabled' => true]);
        $family = $this->familyWith('free', null);
        $this->actingAs($this->member($family))->get('/expenses')->assertRedirect(route('plans'));
    }

    public function test_middleware_allows_trial_and_pro(): void
    {
        config(['billing.enabled' => true]);

        $trial = $this->familyWith('pro_trial', 'ativo');
        $this->actingAs($this->member($trial))->get('/expenses')->assertOk();

        $pro = $this->familyWith('pro', null, now()->addMonth()->toDateString());
        $this->actingAs($this->member($pro))->get('/expenses')->assertOk();
    }

    public function test_middleware_blocks_when_paid_until_expired(): void
    {
        config(['billing.enabled' => true]);
        $family = $this->familyWith('pro', null, now()->subDay()->toDateString());
        $this->actingAs($this->member($family))->get('/expenses')->assertRedirect(route('plans'));
    }

    public function test_middleware_allows_profile_and_admin_group_after_expiry(): void
    {
        config(['billing.enabled' => true]);
        $family = $this->familyWith('free', null);
        $admin = $this->member($family);

        $this->actingAs($admin)->get('/profile')->assertOk();
        $this->actingAs($admin)->get('/family')->assertOk();
        $this->actingAs($admin)->get('/expenses')->assertRedirect(route('plans'));
    }

    public function test_billing_disabled_never_blocks(): void
    {
        config(['billing.enabled' => false]);
        $family = $this->familyWith('free', null);
        $this->actingAs($this->member($family))->get('/expenses')->assertOk();
    }

    public function test_checkout_generates_mp_link_and_stores_preapproval(): void
    {
        config(['billing.enabled' => true, 'billing.mercado_pago.access_token' => 'TEST-TOKEN']);
        Http::fake([
            'api.mercadopago.com/preapproval' => Http::response(['id' => 'PRE1', 'init_point' => 'https://mp/pagar'], 201),
        ]);

        $family = $this->familyWith('free', null);
        $url = app(BillingService::class)->checkoutUrl($family, 'mensal');

        $this->assertSame('https://mp/pagar', $url);
        $this->assertSame('PRE1', $family->fresh()->mp_preapproval_id);
    }

    public function test_webhook_activates_plan(): void
    {
        config(['billing.mercado_pago.access_token' => 'TEST-TOKEN']);
        $family = Family::create([
            'name' => 'Família MP', 'plan' => 'free', 'trial_ends_at' => null, 'mp_preapproval_id' => 'PRE1',
        ]);

        Http::fake(['api.mercadopago.com/preapproval/PRE1' => Http::response(['status' => 'authorized', 'next_payment_date' => now()->addMonth()->toIso8601String()])]);
        app(BillingService::class)->handleWebhook(['type' => 'preapproval', 'data' => ['id' => 'PRE1']]);
        $fresh = $family->fresh();
        $this->assertSame('pro', $fresh->plan);
        $this->assertNotNull($fresh->plan_paid_until);
    }

    public function test_webhook_payment_renews_paid_until(): void
    {
        config(['billing.mercado_pago.access_token' => 'TEST-TOKEN']);
        $family = Family::create([
            'name' => 'Família MP', 'plan' => 'free', 'trial_ends_at' => null, 'mp_preapproval_id' => 'PRE1',
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/PAY1' => Http::response(['status' => 'approved', 'preapproval_id' => 'PRE1']),
            'api.mercadopago.com/preapproval/PRE1' => Http::response(['auto_recurring' => ['frequency' => 1]]),
        ]);
        app(BillingService::class)->handleWebhook(['type' => 'payment', 'data' => ['id' => 'PAY1']]);

        $fresh = $family->fresh();
        $this->assertSame('pro', $fresh->plan);
        $this->assertTrue($fresh->plan_paid_until->isFuture());
    }

    public function test_register_marks_email_on_blacklist(): void
    {
        $this->post('/register', [
            'manager_name' => 'Ana', 'email' => 'ana@email.com', 'family_name' => 'Família Ana',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123', 'terms' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('trial_blacklist', ['identifier' => 'ana@email.com', 'type' => 'email']);
    }

    public function test_register_blocks_blacklisted_email(): void
    {
        TrialBlacklist::create(['identifier' => 'ana@email.com', 'type' => 'email']);

        $this->post('/register', [
            'manager_name' => 'Ana', 'email' => 'ana@email.com', 'family_name' => 'Família Ana',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123', 'terms' => '1',
        ])->assertSessionHasErrors('email');
    }

    public function test_register_blocks_blacklisted_ip_when_enabled(): void
    {
        config(['billing.blacklist_ip' => true]);
        TrialBlacklist::create(['identifier' => '127.0.0.1', 'type' => 'ip']);

        $this->post('/register', [
            'manager_name' => 'Bia', 'email' => 'bia@email.com', 'family_name' => 'Família Bia',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123', 'terms' => '1',
        ])->assertSessionHasErrors('email');
    }

    public function test_checkout_invalid_plan_returns_422(): void
    {
        config(['billing.enabled' => true, 'billing.mercado_pago.access_token' => 'TEST-TOKEN']);
        $family = $this->familyWith('pro_trial', 'ativo');
        $admin = $this->member($family);

        $this->actingAs($admin)->post('/plans/checkout', ['plano' => 'semanal'])->assertStatus(422);
    }

    public function test_cancel_without_subscription_ok(): void
    {
        config(['billing.enabled' => true]);
        $family = $this->familyWith('pro', null, now()->addMonth()->toDateString());
        $admin = $this->member($family);

        $this->actingAs($admin)->post('/plans/cancel')->assertRedirect(route('plans'));
        $this->assertSame('free', $family->fresh()->plan);
        $this->assertNull($family->fresh()->plan_paid_until);
    }

    public function test_webhook_unknown_preapproval_ignored(): void
    {
        config(['billing.mercado_pago.access_token' => 'TEST']);
        $family = Family::create([
            'name' => 'Família MP', 'plan' => 'free', 'trial_ends_at' => null, 'mp_preapproval_id' => 'PRE1',
        ]);
        Http::fake(['api.mercadopago.com/preapproval/NOPE' => Http::response(null, 404)]);

        $this->postJson('/webhooks/mercadopago', ['type' => 'preapproval', 'data' => ['id' => 'NOPE']])->assertOk();
        $this->assertSame('free', $family->fresh()->plan);
    }

    public function test_webhook_validates_signature(): void
    {
        $secret = 'segredo-teste';
        config(['billing.mercado_pago.access_token' => 'TEST', 'billing.mercado_pago.webhook_secret' => $secret]);
        $family = Family::create([
            'name' => 'Família MP', 'plan' => 'free', 'trial_ends_at' => null, 'mp_preapproval_id' => 'PRE1',
        ]);
        Http::fake(['api.mercadopago.com/preapproval/PRE1' => Http::response(['status' => 'authorized', 'next_payment_date' => now()->addMonth()->toIso8601String()])]);

        // Assinatura válida → processa e ativa.
        $ts = time();
        $v1 = hash_hmac('sha256', 'id:PRE1:'.$ts, $secret);
        $this->postJson('/webhooks/mercadopago', ['type' => 'preapproval', 'data' => ['id' => 'PRE1']], ['x-signature' => "ts={$ts},v1={$v1}"])->assertOk();
        $this->assertSame('pro', $family->fresh()->plan);

        // Assinatura errada → 401 e não altera.
        $this->postJson('/webhooks/mercadopago', ['type' => 'preapproval', 'data' => ['id' => 'PRE1']], ['x-signature' => "ts={$ts},v1=".str_repeat('0', 64)])->assertStatus(401);
        $this->assertSame('pro', $family->fresh()->plan);
    }

    public function test_webhook_accepts_without_signature_like_simulation(): void
    {
        config(['billing.mercado_pago.access_token' => 'TEST', 'billing.mercado_pago.webhook_secret' => 'segredo']);
        $family = Family::create([
            'name' => 'Família MP', 'plan' => 'free', 'trial_ends_at' => null, 'mp_preapproval_id' => 'PRE1',
        ]);
        Http::fake(['api.mercadopago.com/preapproval/PRE1' => Http::response(['status' => 'authorized', 'next_payment_date' => now()->addMonth()->toIso8601String()])]);

        // Formato real da simulação do MP (sem x-signature, type subscription_preapproval).
        $this->postJson('/webhooks/mercadopago', [
            'action' => 'updated', 'type' => 'subscription_preapproval', 'entity' => 'preapproval',
            'data' => ['id' => 'PRE1'],
        ])->assertOk();
        $this->assertSame('pro', $family->fresh()->plan);
    }

    public function test_webhook_cancels_plan(): void
    {
        config(['billing.mercado_pago.access_token' => 'TEST-TOKEN']);
        $family = Family::create([
            'name' => 'Família MP', 'plan' => 'pro', 'trial_ends_at' => null, 'mp_preapproval_id' => 'PRE1',
        ]);

        Http::fake(['api.mercadopago.com/preapproval/PRE1' => Http::response(['status' => 'cancelled'])]);
        app(BillingService::class)->handleWebhook(['type' => 'preapproval', 'data' => ['id' => 'PRE1']]);
        $this->assertSame('free', $family->fresh()->plan);
    }
}
