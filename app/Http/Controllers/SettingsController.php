<?php

namespace App\Http\Controllers;

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

        return view('pages.configuracoes', compact('settings'));
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $family->update(['name' => $data['name']]);
        $family->setting()->update([
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
            'consolidate_dependent_yield' => $request->boolean('consolidate_dependent_yield'),
        ]);

        return back()->with('status', 'Configurações salvas.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $family = Fin::family();
        $keys = ['fatura_vencimento', 'conta_vencimento'];
        $notifications = [];
        foreach ($keys as $k) {
            $notifications[$k] = $request->boolean("notifications.$k");
        }
        $family->setting()->update(['notifications' => $notifications]);

        return back()->with('status', 'Notificações atualizadas.');
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
