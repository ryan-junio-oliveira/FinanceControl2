<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinfamBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_render(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/cadastro')->assertOk();
        $this->get('/recuperar-senha')->assertOk();
        // convite inválido deve dar 404 (não 500)
        $this->get('/primeiro-acesso/token-invalido')->assertNotFound();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/despesas')->assertRedirect('/login');
    }

    public function test_full_family_flow(): void
    {
        // --- registro cria família + admin ---
        $res = $this->post('/cadastro', [
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
        foreach (['/', '/despesas', '/receitas', '/contas', '/cartoes', '/categorias', '/familia', '/investimentos', '/configuracoes'] as $uri) {
            $this->get($uri)->assertOk();
        }

        // --- categoria ---
        $this->post('/categorias', ['name' => 'Alimentação', 'type' => 'despesa'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Alimentação']);
        $catDesp = \App\Models\Category::where('name', 'Alimentação')->first();
        $this->post('/categorias', ['name' => 'Salário', 'type' => 'receita'])->assertSessionHasNoErrors();
        $catRec = \App\Models\Category::where('name', 'Salário')->first();

        // --- conta ---
        $this->post('/contas', ['name' => 'Itaú Conjunta', 'kind' => 'corrente', 'initial_balance' => '1000'])->assertSessionHasNoErrors();
        $conta = \App\Models\Account::where('name', 'Itaú Conjunta')->first();
        $this->assertNotNull($conta);
        $this->assertEquals(1000, (float) $conta->balance);

        // --- receita + despesa ---
        $this->post('/receitas', [
            'description' => 'Salário', 'amount' => '5000', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $catRec->id,
        ])->assertSessionHasNoErrors();
        $this->post('/despesas', [
            'description' => 'Mercado', 'amount' => '500', 'occurred_on' => now()->toDateString(),
            'status' => 'pendente', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $catDesp->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(6000, (float) $conta->fresh()->balance); // 1000 + 5000 (despesa pendente não abate)

        $desp = \App\Models\Transaction::where('description', 'Mercado')->first();
        $this->post("/lancamentos/{$desp->id}/liquidar")->assertSessionHasNoErrors();
        $this->assertEquals(5500, (float) $conta->fresh()->balance);

        // --- transferência interna ---
        $this->post('/contas', ['name' => 'Nubank', 'kind' => 'digital', 'initial_balance' => '0'])->assertSessionHasNoErrors();
        $nu = \App\Models\Account::where('name', 'Nubank')->first();
        $this->post('/contas/transferir', [
            'from_account_id' => $conta->id, 'to_account_id' => $nu->id,
            'amount' => '500', 'occurred_on' => now()->toDateString(), 'user_id' => $admin->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(5000, (float) $conta->fresh()->balance);
        $this->assertEquals(500, (float) $nu->fresh()->balance);

        // --- cartão + item + liquidação ---
        $this->post('/cartoes', ['name' => 'Nubank UV', 'credit_limit' => '10000', 'closing_day' => 3, 'due_day' => 10, 'holder_user_id' => $admin->id])->assertSessionHasNoErrors();
        $card = \App\Models\CreditCard::where('name', 'Nubank UV')->first();
        $this->post('/cartoes/itens', [
            'credit_card_id' => $card->id, 'description' => 'Mercado cartão', 'amount' => '200',
            'occurred_on' => now()->toDateString(), 'user_id' => $admin->id, 'category_id' => $catDesp->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(200, (float) $card->fresh()->open_invoice);
        $item = \App\Models\CardTransaction::first();
        $this->post("/cartoes/itens/{$item->id}/liquidar")->assertSessionHasNoErrors();
        $this->assertEquals(0, (float) $card->fresh()->open_invoice);

        // --- investimentos: carteira + ativo + aporte (abate da conta) ---
        $this->post('/investimentos/carteiras', ['name' => 'Reserva', 'kind' => 'reserva', 'target_amount' => '50000'])->assertSessionHasNoErrors();
        $cart = \App\Models\Portfolio::where('name', 'Reserva')->first();
        $this->post('/investimentos/ativos', [
            'portfolio_id' => $cart->id, 'code' => 'SELIC', 'name' => 'Tesouro Selic',
            'kind' => 'renda_fixa', 'current_value' => '10000',
        ])->assertSessionHasNoErrors();
        $this->post('/investimentos/aportes', [
            'portfolio_id' => $cart->id, 'account_id' => $conta->id, 'kind' => 'aporte',
            'amount' => '1000', 'occurred_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertEquals(4000, (float) $conta->fresh()->balance);
        $this->get('/investimentos')->assertOk();

        // --- convite + primeiro acesso (convidado abre o link deslogado) ---
        \Illuminate\Support\Facades\Mail::fake();
        $this->post('/familia/convites', ['name' => 'Lucas Silva', 'email' => 'lucas@email.com', 'role' => 'dependente'])->assertSessionHasNoErrors();
        $convite = Invitation::where('email', 'lucas@email.com')->first();
        $this->assertNotNull($convite);
        // e-mail de boas-vindas enfileirado com o link de primeiro acesso
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\WelcomeEmail::class, function ($mail) use ($convite) {
            return $mail->hasTo('lucas@email.com')
                && str_contains($mail->render(), '/primeiro-acesso/'.$convite->token);
        });
        $this->post('/logout')->assertRedirect(route('login'));
        $this->get("/primeiro-acesso/{$convite->token}")->assertOk();
        $this->post("/primeiro-acesso/{$convite->token}", ['password' => 'Filho@123', 'password_confirmation' => 'Filho@123'])->assertRedirect(route('dashboard'));
        $lucas = User::where('email', 'lucas@email.com')->first();
        $this->assertEquals('dependente', $lucas->role);

        // --- mesada ---
        $this->actingAs($admin)->post('/familia/mesadas', ['user_id' => $lucas->id, 'amount' => '600', 'frequency' => 'mensal', 'payday' => 5])->assertSessionHasNoErrors();

        // --- dependente: vê dashboard mas não investimentos/config ---
        $this->actingAs($lucas);
        $this->get('/')->assertOk();
        $this->get('/despesas')->assertOk();
        $this->get('/investimentos')->assertForbidden();
        $this->get('/configuracoes')->assertForbidden();

        // --- despesa acima do limiar vira pendente p/ dependente ---
        $this->actingAs($admin)->patch('/configuracoes', [
            'name' => 'Família Silva', 'currency' => 'BRL', 'timezone' => 'America/Sao_Paulo',
            'closing_day' => 1, 'approval_threshold' => '500', 'privacy_hide_under' => '50',
        ])->assertSessionHasNoErrors();
        $this->actingAs($lucas)->post('/despesas', [
            'description' => 'Videogame', 'amount' => '2000', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $lucas->id, 'category_id' => $catDesp->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals('pendente', \App\Models\Transaction::where('description', 'Videogame')->first()->status);

        // --- logout + login ---
        $this->actingAs($admin)->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'carlos@email.com', 'password' => 'Senha@123'])->assertRedirect(route('dashboard'));

        // --- reuniões finais de render ---
        $this->get('/')->assertOk();
        $this->get("/despesas?mes={$mes}&status=pendente")->assertOk();
        $this->get('/familia')->assertOk();
    }

    public function test_login_invalido(): void
    {
        User::factory()->create(['email' => 'x@email.com', 'password' => Hash::make('Senha@123')]);
        $this->post('/login', ['email' => 'x@email.com', 'password' => 'errada'])->assertSessionHasErrors('email');
    }

    public function test_login_funciona_com_lembrar_marcado(): void
    {
        // Reproduz o envio real do formulário (checkbox "lembrar" vem marcado).
        $this->post('/cadastro', [
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
            if ($errors instanceof \Illuminate\Support\ViewErrorBag) {
                return $errors->getBag('default')->get($field);
            }
            if ($errors instanceof \Illuminate\Contracts\Support\MessageBag) {
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
        $this->post('/cadastro', [
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
        $this->post('/cadastro', [
            'manager_name' => 'Teste',
            'email' => 'outro@email.com',
            'family_name' => 'Família Teste',
            'password' => 'Senha@123',
            'password_confirmation' => 'Diferente@123',
            'terms' => '1',
        ])->assertSessionHasErrors('password');
        $assertFriendly($bag('password'));

        // --- e-mail duplicado usa texto próprio ---
        $this->post('/cadastro', [
            'manager_name' => 'Base',
            'email' => 'base@email.com',
            'family_name' => 'Família Base',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/cadastro', [
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
        $this->post('/cadastro', [
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
        $this->post('/cadastro', [
            'manager_name' => 'Dono',
            'email' => 'dono@email.com',
            'family_name' => 'Família Dona',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'terms' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->post('/despesas', [])->assertSessionHasErrors(['description', 'amount', 'occurred_on', 'status', 'user_id']);
        $assertFriendly($bag('description'));
        $assertFriendly($bag('amount'));
        $this->post('/despesas', [
            'description' => 'Teste', 'amount' => '0', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => User::where('email', 'dono@email.com')->first()->id,
        ])->assertSessionHasErrors('amount');
        $this->assertStringContainsString('maior que zero', $bag('amount')[0]);

        // --- categoria sem nome ---
        $this->post('/categorias', ['type' => 'despesa'])->assertSessionHasErrors('name');
        $assertFriendly($bag('name'));
    }

    public function test_mvp_pages_profile_and_card_account_link(): void
    {
        $this->post('/cadastro', [
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
            '/despesas/criar', '/receitas/criar', '/contas/criar', '/contas/transferir',
            '/cartoes/criar', '/cartoes/itens/criar', '/categorias/criar',
            '/investimentos/carteiras/criar', '/investimentos/ativos/criar', '/investimentos/aportes/criar',
            '/familia/convites/criar', '/familia/mesadas/criar', '/configuracoes/bancos/criar',
            '/perfil', '/perfil/editar', '/perfil/senha',
        ] as $uri) {
            $this->get($uri)->assertOk($uri);
        }

        // --- conta com cor + cartão vinculado herda a cor ---
        $this->post('/contas', [
            'name' => 'Inter', 'kind' => 'digital', 'initial_balance' => '0', 'color' => '#EA580C',
        ])->assertRedirect(route('contas'));
        $conta = \App\Models\Account::where('name', 'Inter')->first();
        $this->assertEquals('#EA580C', $conta->color);

        $this->post('/cartoes', [
            'name' => 'Inter Gold', 'credit_limit' => '5000', 'closing_day' => 5, 'due_day' => 15,
            'holder_user_id' => $admin->id, 'account_id' => $conta->id,
        ])->assertRedirect(route('cartoes'));
        $cartao = \App\Models\CreditCard::where('name', 'Inter Gold')->first();
        $this->assertEquals($conta->id, $cartao->account_id);
        $this->assertEquals('#EA580C', $cartao->display_color);
        $this->get('/cartoes/'.$cartao->id.'/editar')->assertOk();
        $this->get('/cartoes')->assertSee('#EA580C', false);

        // --- cor inválida é rejeitada com mensagem amigável ---
        $this->post('/contas', [
            'name' => 'Ruim', 'kind' => 'digital', 'initial_balance' => '0', 'color' => 'laranja',
        ])->assertSessionHasErrors('color');

        // --- perfil: ver, editar e trocar senha ---
        $this->get('/perfil')->assertSee('Dona');
        $this->patch('/perfil', [
            'name' => 'Dona Silva', 'email' => 'dona@email.com', 'phone' => '11999998888',
        ])->assertRedirect(route('perfil'));
        $this->assertEquals('Dona Silva', $admin->fresh()->name);
        $this->assertEquals('11999998888', $admin->fresh()->phone);

        $this->patch('/perfil/senha', [
            'current_password' => 'Senha@123',
            'password' => 'Nova@123',
            'password_confirmation' => 'Nova@123',
        ])->assertRedirect(route('perfil'));
        $this->post('/logout')->assertRedirect(route('login'));
        $this->post('/login', ['email' => 'dona@email.com', 'password' => 'Nova@123'])->assertRedirect(route('dashboard'));

        // --- toast de sucesso aparece com timeout de 10s ---
        $res = $this->post('/categorias', ['name' => 'Teste Toast', 'type' => 'despesa']);
        $res->assertRedirect(route('categorias'));
        $this->followRedirects($res)->assertSee('data-toast', false)->assertSee('data-toast-timeout="10000"', false);
    }
}
