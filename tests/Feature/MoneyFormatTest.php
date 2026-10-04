<?php

namespace Tests\Feature;

use App\Http\Requests\FormRequest;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyFormatTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('formatosProvider')]
    public function test_parser_brasileiro(string $input, ?string $expected): void
    {
        // Classe anônima só para expor o método estático.
        $parser = new class extends FormRequest
        {
            public function rules(): array
            {
                return [];
            }
        };

        $this->assertSame($expected, $parser::parseBrazilianDecimal($input));
    }

    public static function formatosProvider(): array
    {
        return [
            'milhar sem decimal' => ['7.000', '7000'],
            'milhar com decimal' => ['7.000,00', '7000.00'],
            'milhão' => ['1.200.000', '1200000'],
            'milhão com decimal' => ['1.234,56', '1234.56'],
            'inteiro simples' => ['7000', '7000'],
            'percentual simples' => ['120', '120'],
            'decimal ponto' => ['7.5', '7.5'],
            'com símbolo' => ['R$ 1.234,56', '1234.56'],
            'negativo br' => ['-1.234,56', '-1234.56'],
            'negativo milhar' => ['-7.000', '-7000'],
            'vazio' => ['', null],
            'só espaços' => ['   ', null],
        ];
    }

    public function test_lancamento_aceita_milhar_br(): void
    {
        $this->post('/register', [
            'manager_name' => 'M', 'email' => 'm@email.com',
            'group_name' => 'Fam M', 'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123', 'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $admin = User::where('email', 'm@email.com')->first();
        $cat = Category::where('group_id', $admin->group_id)->where('type', 'despesa')->first();
        $conta = $admin->group->accounts()->create(['name' => 'Conta', 'kind' => 'corrente', 'initial_balance' => 0]);

        // "7.000" digitado = sete mil, nunca 7 reais.
        $this->post('/expenses', [
            'description' => 'Teste milhar', 'amount' => '7.000', 'occurred_on' => now()->toDateString(),
            'status' => 'pago', 'user_id' => $admin->id, 'account_id' => $conta->id, 'category_id' => $cat->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame(7000.0, (float) Transaction::where('description', 'Teste milhar')->first()->amount);

        // Percentual "120" = cento e vinte.
        $this->post('/investments/assets', [
            'code' => 'CDB', 'name' => 'CDB X', 'kind' => 'renda_fixa',
            'current_value' => '7000', 'yield_percent' => '120', 'yield_base' => 'cdi',
        ])->assertSessionHasNoErrors();
        $asset = Asset::where('code', 'CDB')->first();
        $this->assertSame(120.0, (float) $asset->yield_percent);
        $this->assertSame('120,00% do CDI', $asset->yield_label);
    }
}
