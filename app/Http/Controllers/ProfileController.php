<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Models\Asset;
use App\Models\CardTransaction;
use App\Models\Contribution;
use App\Models\User;
use App\Services\ProfileService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('pages.profile.show', ['user' => request()->user()->load('group')]);
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

    /** Portabilidade (LGPD, art. 18): baixa todos os dados do grupo em JSON (stream, sem OOM). */
    public function export(): StreamedResponse
    {
        $user = request()->user();
        $fid = $user->group_id;
        Audit::log('Exportação LGPD dos dados do grupo.', 'export');

        $filename = 'prumo-dados-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($user, $fid) {
            echo '{"exportado_em":"'.now()->toIso8601String().'",';
            echo '"usuario":'.json_encode($user->only(['name', 'email', 'role', 'created_at']), JSON_UNESCAPED_UNICODE).',';
            echo '"grupo":'.json_encode(['nome' => $user->group->name, 'plano' => $user->group->plan], JSON_UNESCAPED_UNICODE).',';
            $this->streamCursor('membros', $user->group->users()->select(['name', 'email', 'role'])->cursor());
            echo ',';
            $this->streamCursor('categorias', $user->group->categories()->select(['name', 'type'])->cursor());
            echo ',';
            $this->streamCursor('contas', $user->group->accounts()->select(['name', 'kind', 'initial_balance'])->cursor());
            echo ',';
            $this->streamCursor('cartoes', $user->group->creditCards()->select(['name', 'brand', 'credit_limit', 'closing_day', 'due_day'])->cursor());
            echo ',';
            $this->streamCursor('lancamentos', $user->group->transactions()->select(['type', 'description', 'amount', 'occurred_on', 'due_on', 'status', 'payment_method'])->cursor());
            echo ',';
            $this->streamCursor('itens_de_fatura', CardTransaction::where('group_id', $fid)->select(['description', 'amount', 'occurred_on', 'status'])->cursor());
            echo ',';
            $this->streamCursor('ativos', Asset::where('group_id', $fid)->select(['name', 'code', 'kind', 'current_value'])->cursor());
            echo ',';
            $this->streamCursor('aportes_e_rendimentos', Contribution::where('group_id', $fid)->select(['kind', 'amount', 'occurred_on', 'note'])->cursor());
            echo '}';
        }, $filename, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    /** Serializa um cursor como array JSON sem carregar tudo em memória. */
    private function streamCursor(string $key, iterable $cursor): void
    {
        echo json_encode($key).':[';
        $first = true;
        foreach ($cursor as $row) {
            echo ($first ? '' : ',').json_encode($row->toArray(), JSON_UNESCAPED_UNICODE);
            $first = false;
        }
        echo ']';
    }

    /** Encerra o cadastro: só o administrador principal pode.
     * Apaga a conta do grupo inteira (todos os membros e registros).
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
