<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Family;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
