<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Invitation;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\FaturaVencimento;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FinfamBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_without_smtp_does_not_crash(): void
    {
        User::factory()->create(['email' => 'reset@email.com', 'password' => Hash::make('Senha@123')]);

        // Simula dev/produção sem SMTP configurado (cai no driver log).
        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.username' => null]);
        config(['mail.mailers.smtp.password' => null]);
        $this->app->register(\App\Providers\AppServiceProvider::class, true);
        $this->app->boot();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'reset@email.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        // No driver log o e-mail vai para o arquivo de log.
        $this->assertSame('log', config('mail.default'));
    }

    public function test_guest_pages_render(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
        // convite inválido deve dar 404 (não 500)
        $this->get('/first-access/token-invalido')->assertNotFound();
    }

    public function test_legal_pages_render(): void
    {
        $this->get(route('termos'))->assertOk()->assertSee('Termos de Uso')->assertSee('LGPD');
        $this->get(route('privacidade'))->assertOk()->assertSee('Política de Privacidade')->assertSee('LGPD');
        $this->get(route('termos'))->assertDontSee('sections');
    }

    public function test_pwa_assets_are_served(): void
    {
        // Manifest válido no disco (o servidor web o entrega; nos testes o roteador não serve estáticos).
        $manifest = public_path('manifest.webmanifest');
        $this->assertFileExists($manifest);
        $data = json_decode((string) file_get_contents($manifest), true);
        $this->assertSame('FinFamília', $data['short_name']);
        $this->assertSame('standalone', $data['display']);

        // Service worker com cache versionado e fallback offline.
        $sw = public_path('sw.js');
        $this->assertFileExists($sw);
        $this->assertStringContainsString('finfamilia-v1', (string) file_get_contents($sw));

        // Ícones gerados.
        foreach (['icon-192.png', 'icon-512.png', 'apple-touch-icon.png'] as $icon) {
            $this->assertFileExists(public_path('icons/'.$icon));
        }

        // Página offline e link do manifest no HTML.
        $this->get('/offline')->assertOk()->assertSee('offline');
        $this->get('/login')->assertSee('manifest.webmanifest', false);
    }

    public function test_admin_logs_are_restricted_and_list_actions(): void
    {
        $this->post('/register', [
            'manager_name' => 'Admin Logs',
            'email' => 'adminlogs@email.com',
            'family_name' => 'Família Logs',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $admin = User::where('email', 'adminlogs@email.com')->first();

        // dependente e co_admin não acessam
        foreach (['dependente', 'co_admin'] as $role) {
            $membro = User::create([
                'name' => 'Membro', 'email' => $role.'@email.com', 'password' => bcrypt('Senha@123'),
                'family_id' => $admin->family_id, 'role' => $role,
            ]);
            $this->actingAs($membro)->get('/admin/logs')->assertForbidden();
        }

        // admin executa uma ação e ela aparece na trilha com IP
        $this->actingAs($admin);
        $banco = Bank::create(['code' => '341', 'name' => 'Itaú']);
        $this->post('/accounts', ['name' => 'Conta Logs', 'bank_id' => $banco->id, 'kind' => 'corrente', 'initial_balance' => '0'])->assertSessionHasNoErrors();
        $conta = Account::where('name', 'Conta Logs')->first();
        $cat = $admin->family->categories()->where('type', 'despesa')->first();
        $this->post('/expenses', [
            'description' => 'Compra auditada', 'amount' => '99', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $admin->id, 'category_id' => $cat->id, 'account_id' => $conta->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'user_id' => $admin->id, 'description' => 'Criou Lançamento: Compra auditada',
        ]);
        $this->assertTrue(AuditLog::where('action', 'created')->whereNotNull('ip_address')->exists());

        $this->get('/admin/logs')->assertOk()->assertSee('Criou Lançamento')->assertSee('127.0.0.1');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/expenses')->assertRedirect('/login');
    }

    public function test_full_family_flow(): void
    {
        // --- registro cria família + admin ---
        $res = $this->post('/register', [
            'manager_name' => 'Carlos Silva',
            'email' => 'carlos@email.com',
            'family_name' => 'Família Silva',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ]);
        $res->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $admin = User::where('email', 'carlos@email.com')->first();
        $this->assertNotNull($admin->family_id);
        $this->assertEquals('admin', $admin->role);

        $mes = now()->format('Y-m');

        // --- páginas com estado vazio ---
        foreach (['/', '/expenses', '/incomes', '/accounts', '/cards', '/categories', '/family', '/investments', '/settings'] as $uri) {
            $this->get($uri)->assertOk();
        }

        // --- categoria ---
        $this->post('/categories', ['name' => 'Alimentação', 'type' => 'despesa'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Alimentação']);
        $catDesp = Category::where('name', 'Alimentação')->first();
        $this->post('/categories', ['name' => 'Salário', 'type' => 'receita'])->assertSessionHasNoErrors();
        $catRec = Category::where('name', 'Salário')->first();

        // --- conta (exige banco do catálogo) ---
        $banco = Bank::create(['code' => '341', 'name' => 'Banco Itaú Unibanco']);
        $this->post('/accounts', ['name' => 'Itaú Conjunta', 'bank_id' => $banco->id, 'kind' => 'corrente', 'initial_balance' => '1000'])->assertSessionHasNoErrors();
        $conta = Account::where('name', 'Itaú Conjunta')->first();
        $this->assertNotNull($conta);
        $this->assertEquals(1000, (float) $conta->balance);

        // --- receita + despesa ---
        $this->post('/incomes', [
            'description' => 'Salário', 'amount' => '5000', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $catRec->id,
        ])->assertSessionHasNoErrors();
        $this->post('/expenses', [
            'description' => 'Mercado', 'amount' => '500', 'occurred_on' => now()->toDateString(),
            'status' => 'pendente', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $catDesp->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(6000, (float) $conta->fresh()->balance); // 1000 + 5000 (despesa pendente não abate)

        $desp = Transaction::where('description', 'Mercado')->first();
        $this->post("/transactions/{$desp->id}/settle")->assertSessionHasNoErrors();
        $this->assertEquals(5500, (float) $conta->fresh()->balance);

        // --- transferência interna ---
        $this->post('/accounts', ['name' => 'Nubank', 'bank_id' => $banco->id, 'kind' => 'digital', 'initial_balance' => '0'])->assertSessionHasNoErrors();
        $nu = Account::where('name', 'Nubank')->first();
        $this->post('/accounts/transfer', [
            'from_account_id' => $conta->id, 'to_account_id' => $nu->id,
            'amount' => '500', 'occurred_on' => now()->toDateString(), 'user_id' => $admin->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(5000, (float) $conta->fresh()->balance);
        $this->assertEquals(500, (float) $nu->fresh()->balance);

        // --- cartão + item + liquidação ---
        $this->post('/cards', ['name' => 'Nubank UV', 'credit_limit' => '10000', 'closing_day' => 3, 'due_day' => 10, 'holder_user_id' => $admin->id])->assertSessionHasNoErrors();
        $card = CreditCard::where('name', 'Nubank UV')->first();
        $this->post('/cards/items', [
            'credit_card_id' => $card->id, 'description' => 'Mercado cartão', 'amount' => '200',
            'occurred_on' => now()->toDateString(), 'user_id' => $admin->id, 'category_id' => $catDesp->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(200, (float) $card->fresh()->open_invoice);
        $item = CardTransaction::first();
        $this->post("/cards/items/{$item->id}/settle")->assertSessionHasNoErrors();
        $this->assertEquals(0, (float) $card->fresh()->open_invoice);

        // --- investimentos: carteira + ativo + aporte (abate da conta) ---
        $this->post('/investments/portfolios', ['name' => 'Reserva', 'kind' => 'reserva', 'target_amount' => '50000'])->assertSessionHasNoErrors();
        $cart = Portfolio::where('name', 'Reserva')->first();
        $this->post('/investments/assets', [
            'portfolio_id' => $cart->id, 'code' => 'SELIC', 'name' => 'Tesouro Selic',
            'kind' => 'renda_fixa', 'current_value' => '10000',
        ])->assertSessionHasNoErrors();
        $this->post('/investments/contributions', [
            'portfolio_id' => $cart->id, 'account_id' => $conta->id, 'kind' => 'aporte',
            'amount' => '1000', 'occurred_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertEquals(4000, (float) $conta->fresh()->balance);
        $this->get('/investments')->assertOk();

        // --- convite + primeiro acesso (convidado abre o link deslogado) ---
        Mail::fake();
        $this->post('/family/invites', ['name' => 'Lucas Silva', 'email' => 'lucas@email.com', 'role' => 'dependente'])->assertSessionHasNoErrors();
        $convite = Invitation::where('email', 'lucas@email.com')->first();
        $this->assertNotNull($convite);
        // e-mail de boas-vindas enfileirado com o link de primeiro acesso
        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($convite) {
            return $mail->hasTo('lucas@email.com')
                && str_contains($mail->render(), '/first-access/'.$convite->token);
        });
        $this->post('/logout')->assertRedirect(route('login'));
        $this->get("/first-access/{$convite->token}")->assertOk();
        $this->post("/first-access/{$convite->token}", ['password' => 'Filho@123', 'password_confirmation' => 'Filho@123'])->assertRedirect(route('dashboard'));
        $lucas = User::where('email', 'lucas@email.com')->first();
        $this->assertEquals('dependente', $lucas->role);

        // --- dependente: vê dashboard mas não investimentos/config ---
        $this->actingAs($lucas);
        $this->get('/')->assertOk();
        $this->get('/expenses')->assertOk();
        $this->get('/investments')->assertForbidden();
        $this->get('/settings')->assertForbidden();

        // --- sistema é só registro: sem aprovação, status informado é mantido ---
        $this->actingAs($admin)->patch('/settings', [
            'name' => 'Família Silva', 'currency' => 'BRL', 'timezone' => 'America/Sao_Paulo',
        ])->assertSessionHasNoErrors();
        $this->actingAs($lucas)->post('/expenses', [
            'description' => 'Videogame', 'amount' => '2000', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $lucas->id, 'category_id' => $catDesp->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals('pago', Transaction::where('description', 'Videogame')->first()->status);

        // --- logout + login ---
        $this->actingAs($admin)->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'carlos@email.com', 'password' => 'Senha@123'])->assertRedirect(route('dashboard'));

        // --- reuniões finais de render ---
        $this->get('/')->assertOk();
        $this->get("/expenses?mes={$mes}&status=pendente")->assertOk();
        $this->get('/family')->assertOk();
    }

    public function test_login_invalido(): void
    {
        User::factory()->create(['email' => 'x@email.com', 'password' => Hash::make('Senha@123')]);
        $this->post('/login', ['email' => 'x@email.com', 'password' => 'errada'])->assertSessionHasErrors('email');
        // mensagem de falha vem em pt-BR (tradução ativa)
        $bag = session('errors');
        $msg = $bag->get('email')[0] ?? '';
        $this->assertStringContainsString('Credenciais', $msg);
        $this->assertStringNotContainsString('These credentials', $msg);
    }

    public function test_login_funciona_com_lembrar_marcado(): void
    {
        // Reproduz o envio real do formulário (checkbox "lembrar" vem marcado).
        $this->post('/register', [
            'manager_name' => 'Lembrado',
            'email' => 'lembrado@email.com',
            'family_name' => 'Família Lembrada',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'lembrado@email.com',
            'password' => 'Senha@123',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'lembrado@email.com')->first());

        // E sem o checkbox também funciona.
        $this->post('/logout');
        $this->post('/login', [
            'email' => 'lembrado@email.com',
            'password' => 'Senha@123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_validation_messages_are_friendly_ptbr(): void
    {
        $bag = function (string $field): array {
            $errors = session('errors');
            if ($errors instanceof ViewErrorBag) {
                return $errors->getBag('default')->get($field);
            }
            if ($errors instanceof MessageBag) {
                return $errors->get($field);
            }
            if (is_array($errors)) {
                return $errors[$field] ?? [];
            }

            return [];
        };
        $assertFriendly = function (array $messages): void {
            $this->assertNotEmpty($messages);
            foreach ($messages as $msg) {
                $this->assertStringNotContainsString('validation.', $msg, "Mensagem crua vazou: {$msg}");
            }
        };

        // --- senha fraca: deve listar maiúscula/minúscula, número e símbolo em pt-BR ---
        $this->post('/register', [
            'manager_name' => 'Teste',
            'email' => 'fraco@email.com',
            'family_name' => 'Família Teste',
            'password' => 'senhafraca',
            'password_confirmation' => 'senhafraca',
            'terms' => '1',
        ])->assertSessionHasErrors('password');
        $msgs = $bag('password');
        $assertFriendly($msgs);
        $texto = implode(' ', $msgs);
        $this->assertStringContainsString('maiúscula', $texto);
        $this->assertStringContainsString('número', $texto);
        $this->assertStringContainsString('símbolo', $texto);

        // --- confirmação divergente ---
        $this->post('/register', [
            'manager_name' => 'Teste',
            'email' => 'outro@email.com',
            'family_name' => 'Família Teste',
            'password' => 'Senha@123',
            'password_confirmation' => 'Diferente@123',
            'terms' => '1',
        ])->assertSessionHasErrors('password');
        $assertFriendly($bag('password'));

        // --- e-mail duplicado usa texto próprio ---
        $this->post('/register', [
            'manager_name' => 'Base',
            'email' => 'base@email.com',
            'family_name' => 'Família Base',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/register', [
            'manager_name' => 'Cópia',
            'email' => 'base@email.com',
            'family_name' => 'Família Cópia',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
        $assertFriendly($bag('email'));
        $this->assertStringContainsString('já tem conta', $bag('email')[0]);

        // --- termos não aceitos ---
        $this->post('/register', [
            'manager_name' => 'Sem Termos',
            'email' => 'semtermos@email.com',
            'family_name' => 'Família ST',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
        ])->assertSessionHasErrors('terms');
        $assertFriendly($bag('terms'));

        // --- login vazio ---
        $this->post('/login', ['email' => '', 'password' => ''])->assertSessionHasErrors(['email', 'password']);
        $assertFriendly($bag('email'));
        $assertFriendly($bag('password'));

        // --- lançamento inválido (logado) ---
        $this->post('/register', [
            'manager_name' => 'Dono',
            'email' => 'dono@email.com',
            'family_name' => 'Família Dona',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->post('/expenses', [])->assertSessionHasErrors(['description', 'amount', 'occurred_on', 'status', 'user_id']);
        $assertFriendly($bag('description'));
        $assertFriendly($bag('amount'));
        $this->post('/expenses', [
            'description' => 'Teste', 'amount' => '0', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => User::where('email', 'dono@email.com')->first()->id,
        ])->assertSessionHasErrors('amount');
        $this->assertStringContainsString('maior que zero', $bag('amount')[0]);

        // --- categoria sem nome ---
        $this->post('/categories', ['type' => 'despesa'])->assertSessionHasErrors('name');
        $assertFriendly($bag('name'));
    }

    public function test_mvp_pages_profile_and_card_account_link(): void
    {
        $this->post('/register', [
            'manager_name' => 'Dona',
            'email' => 'dona@email.com',
            'family_name' => 'Família Dona',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $admin = User::where('email', 'dona@email.com')->first();

        // --- todas as páginas de cadastro abrem ---
        foreach ([
            '/expenses/create', '/incomes/create', '/accounts/create', '/accounts/transfer',
            '/cards/create', '/cards/items/create', '/categories/create',
            '/investments/portfolios/create', '/investments/assets/create', '/investments/contributions/create',
            '/family/invites/create',
            '/profile', '/profile/edit', '/profile/password',
        ] as $uri) {
            $this->get($uri)->assertOk($uri);
        }

        // --- conta herda a cor do banco; cartão herda do banco via conta ---
        $bancoInter = Bank::create(['code' => '077', 'name' => 'Banco Inter', 'color' => '#EA580C']);
        $this->post('/accounts', [
            'name' => 'Inter', 'bank_id' => $bancoInter->id, 'kind' => 'digital', 'initial_balance' => '0',
        ])->assertRedirect(route('contas'));
        $conta = Account::where('name', 'Inter')->first();
        $this->assertEquals($bancoInter->id, $conta->bank_id);
        $this->assertEquals('#EA580C', $conta->display_color);

        $this->post('/cards', [
            'name' => 'Inter Gold', 'credit_limit' => '5000', 'closing_day' => 5, 'due_day' => 15,
            'holder_user_id' => $admin->id, 'account_id' => $conta->id,
        ])->assertRedirect(route('cartoes'));
        $cartao = CreditCard::where('name', 'Inter Gold')->first();
        $this->assertEquals($conta->id, $cartao->account_id);
        $this->assertEquals('#EA580C', $cartao->display_color);
        $this->get('/cards/'.$cartao->id.'/edit')->assertOk();
        $this->get('/cards')->assertSee('#EA580C', false);

        // --- conta sem banco é rejeitada ---
        $this->post('/accounts', [
            'name' => 'Sem Banco', 'kind' => 'digital', 'initial_balance' => '0',
        ])->assertSessionHasErrors('bank_id');

        // --- perfil: ver, editar e trocar senha ---
        $this->get('/profile')->assertSee('Dona');
        $this->patch('/profile', [
            'name' => 'Dona Silva', 'email' => 'dona@email.com', 'phone' => '11999998888',
        ])->assertRedirect(route('perfil'));
        $this->assertEquals('Dona Silva', $admin->fresh()->name);
        $this->assertEquals('11999998888', $admin->fresh()->phone);

        $this->patch('/profile/password', [
            'current_password' => 'Senha@123',
            'password' => 'Nova@123',
            'password_confirmation' => 'Nova@123',
        ])->assertRedirect(route('perfil'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'dona@email.com', 'password' => 'Nova@123'])->assertRedirect(route('dashboard'));

        // --- toast de sucesso aparece com timeout de 10s ---
        $res = $this->post('/categories', ['name' => 'Teste Toast', 'type' => 'despesa']);
        $res->assertRedirect(route('categorias'));
        $this->followRedirects($res)->assertSee('data-toast', false)->assertSee('data-toast-timeout="10000"', false);
    }

    public function test_banks_catalog_notifications_sidebar_and_account_closure(): void
    {
        $this->post('/register', [
            'manager_name' => 'Admin',
            'email' => 'admin@email.com',
            'family_name' => 'Família Admin',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $admin = User::where('email', 'admin@email.com')->first();

        // --- catálogo de bancos semeado e exigido na conta ---
        $this->artisan('banks:seed')->assertSuccessful();
        $this->assertTrue(Bank::count() >= 100);
        $bb = Bank::where('code', '001')->first();
        $this->assertNotNull($bb);

        // --- formulário de conta lista o catálogo ---
        $this->get('/accounts/create')->assertOk()->assertSee('001 — Banco do Brasil S.A.', false);

        // --- conta exibe o banco na listagem ---
        $this->post('/accounts', [
            'name' => 'BB Principal', 'bank_id' => $bb->id, 'kind' => 'corrente', 'initial_balance' => '0',
        ])->assertSessionHasNoErrors();
        $this->get('/accounts')->assertOk()->assertSee('Banco do Brasil S.A.');

        // --- prefs de notificação: só vencimento ---
        $this->patch('/settings/notifications', [
            'notifications' => ['fatura_vencimento' => '1', 'conta_vencimento' => '1'],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(
            ['fatura_vencimento' => true, 'conta_vencimento' => true],
            $admin->family->setting()->notifications
        );

        // --- sidebar: clicar na notificação marca como lida e redireciona ---
        $admin->notify(new FaturaVencimento('Teste', 10.0, now()->format('d/m/Y'), 1));
        $n = $admin->notifications()->first();
        $this->assertNull($n->read_at);
        $this->get(route('notificacoes.ler', $n))->assertRedirect(route('despesas', ['status' => 'pendente']));
        $this->assertNotNull($n->fresh()->read_at);

        // --- membro comum não pode encerrar o cadastro ---
        $membro = User::factory()->create([
            'name' => 'Membro', 'email' => 'membro@email.com',
            'family_id' => $admin->family_id, 'role' => 'dependente',
        ]);
        $this->actingAs($membro)->delete('/profile', ['password' => 'password'])->assertForbidden();

        // --- admin encerra: apaga família inteira (membros + registros) ---
        $familyId = $admin->family_id;
        $this->actingAs($admin)->delete('/profile', ['password' => 'Senha@123'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('families', ['id' => $familyId]);
        $this->assertDatabaseMissing('users', ['email' => 'admin@email.com']);
        $this->assertDatabaseMissing('users', ['email' => 'membro@email.com']);
    }
}
