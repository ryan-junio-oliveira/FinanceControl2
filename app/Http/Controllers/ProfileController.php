<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Models\Asset;
use App\Models\CardTransaction;
use App\Models\Contribution;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('pages.profile.show', ['user' => request()->user()->load('family')]);
    }

    public function edit(): View
    {
        return view('pages.profile.form', ['user' => request()->user()]);
    }

    public function update(ProfileRequest $request, ProfileService $service): RedirectResponse
    {
        $service->update($request->user(), $request->validated());

        return redirect()->route('perfil')->with('status', 'Perfil atualizado.');
    }

    public function editPassword(): View
    {
        return view('pages.profile.password');
    }

    public function updatePassword(ProfilePasswordRequest $request, ProfileService $service): RedirectResponse
    {
        $service->updatePassword($request->user(), $request->validated()['password']);

        return redirect()->route('perfil')->with('status', 'Senha alterada com sucesso.');
    }

    /** Gera (ou troca) o código de vínculo com o bot no celular. */
    public function regenerateBotCode(): RedirectResponse
    {
        $user = request()->user();
        do {
            $code = (string) random_int(100000, 999999);
        } while (User::where('bot_code', $code)->exists());
        // Código de uso único, válido por 15 minutos.
        $user->update(['bot_code' => $code, 'bot_code_expires_at' => now()->addMinutes(15)]);

        return redirect()->route('perfil')->with('status', 'Código do bot gerado (vale por 15 minutos).');
    }

    /** Portabilidade (LGPD, art. 18): baixa todos os dados da família em JSON. */
    public function export(): Response
    {
        $user = request()->user();
        $fid = $user->family_id;

        $dados = [
            'exportado_em' => now()->toIso8601String(),
            'usuario' => $user->only(['name', 'email', 'role', 'created_at']),
            'familia' => ['nome' => $user->family->name, 'plano' => $user->family->plan],
            'membros' => $user->family->users()->get(['name', 'email', 'role']),
            'categorias' => $user->family->categories()->get(['name', 'type']),
            'contas' => $user->family->accounts()->get(['name', 'kind', 'initial_balance']),
            'cartoes' => $user->family->creditCards()->get(['name', 'brand', 'credit_limit', 'closing_day', 'due_day']),
            'lancamentos' => $user->family->transactions()->get(['type', 'description', 'amount', 'occurred_on', 'due_on', 'status', 'payment_method']),
            'itens_de_fatura' => CardTransaction::where('family_id', $fid)->get(['description', 'amount', 'occurred_on', 'status']),
            'ativos' => Asset::where('family_id', $fid)->get(['name', 'code', 'kind', 'current_value']),
            'aportes_e_rendimentos' => Contribution::where('family_id', $fid)->get(['kind', 'amount', 'occurred_on', 'note']),
        ];

        return response(json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="prumo-dados-'.now()->format('Y-m-d').'.json"');
    }

    /** Encerra o cadastro: só o administrador principal pode.
     * Apaga a conta da família inteira (todos os membros e registros).
     */
    public function destroy(ProfileService $service): RedirectResponse
    {
        $user = request()->user();
        abort_if($user->role !== 'admin', 403, 'Somente o administrador pode encerrar o cadastro.');

        request()->validate(
            ['password' => ['required', 'current_password']],
            ['password.required' => 'Informe sua senha para confirmar.', 'password.current_password' => 'Essa senha não confere. Tente de novo.']
        );

        $service->destroy($user);
        $service->logoutWeb();

        return redirect()->route('login')->with('status', 'Cadastro encerrado. Até logo!');
    }
}
