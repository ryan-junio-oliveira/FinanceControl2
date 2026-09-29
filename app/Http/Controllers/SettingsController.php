<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankRequest;
use App\Http\Requests\SettingsRequest;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $settings = $family->setting();
        $bancos = $family->bankConnections()->orderBy('bank')->get();

        return view('pages.configuracoes', compact('settings', 'bancos'));
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $family->update(['name' => $data['name']]);
        $family->setting()->update([
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
            'closing_day' => $data['closing_day'],
            'approval_threshold' => $data['approval_threshold'],
            'privacy_hide_under' => $data['privacy_hide_under'],
            'consolidate_dependent_yield' => $request->boolean('consolidate_dependent_yield'),
        ]);

        return back()->with('status', 'Configurações salvas.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $family = Fin::family();
        $keys = ['teto_85', 'compra_dependente', 'fatura_vencimento', 'resumo_semanal', 'dividendo'];
        $notifications = [];
        foreach ($keys as $k) {
            $notifications[$k] = $request->boolean("notifications.$k");
        }
        $family->setting()->update(['notifications' => $notifications]);

        return back()->with('status', 'Notificações atualizadas.');
    }

    public function createBank(): View
    {
        return view('pages.settings.bank-form');
    }

    public function storeBank(BankRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();
        $family->bankConnections()->create($data + ['status' => 'manual']);

        return redirect()->route('configuracoes')->with('status', 'Banco anotado como referência.');
    }

    public function destroyBank(Request $request, int $banco): RedirectResponse
    {
        $family = Fin::family();
        $family->bankConnections()->findOrFail($banco)->delete();

        return back()->with('status', 'Conexão removida.');
    }

    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate(
            ['password' => ['required', 'current_password']],
            ['password.required' => 'Informe sua senha atual para encerrar as outras sessões.', 'password.current_password' => 'Essa senha não confere. Tente de novo.']
        );
        Auth::logoutOtherDevices($request->password);

        return back()->with('status', 'Outras sessões encerradas.');
    }

    /** Sessão atual (dados reais da requisição). */
    public static function currentSession(Request $request): array
    {
        $row = DB::table('sessions')->where('id', $request->session()->getId())->first();

        return [
            'ip' => $row->ip_address ?? $request->ip(),
            'agent' => $row->user_agent ?? $request->userAgent(),
            'activity' => $row ? $row->last_activity : time(),
        ];
    }
}
