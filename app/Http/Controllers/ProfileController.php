<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function update(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('perfil')->with('status', 'Perfil atualizado.');
    }

    public function editPassword(): View
    {
        return view('pages.profile.password');
    }

    public function updatePassword(ProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated()['password'])]);

        return redirect()->route('perfil')->with('status', 'Senha alterada com sucesso.');
    }

    /**
     * Encerra o cadastro: só o administrador principal pode.
     * Apaga a conta da família inteira (todos os membros e registros).
     */
    public function destroy(): RedirectResponse
    {
        $user = request()->user();
        abort_if($user->role !== 'admin', 403, 'Somente o administrador pode encerrar o cadastro.');

        request()->validate(
            ['password' => ['required', 'current_password']],
            ['password.required' => 'Informe sua senha para confirmar.', 'password.current_password' => 'Essa senha não confere. Tente de novo.']
        );

        $family = $user->family()->firstOrFail();
        $memberIds = $family->users()->pluck('id')->all();

        Audit::silence(function () use ($family, $memberIds) {
            DB::transaction(function () use ($family, $memberIds) {
                // Notificações e sessões não têm FK: limpar manualmente.
                DB::table('notifications')
                    ->where('notifiable_type', \App\Models\User::class)
                    ->whereIn('notifiable_id', $memberIds)
                    ->delete();
                DB::table('sessions')->whereIn('user_id', $memberIds)->delete();

                // Membros primeiro (lançamentos deles caem por cascata), depois a família (cascata no resto).
                \App\Models\User::whereIn('id', $memberIds)->delete();
                $family->delete();
            });

            // Logout também fica silencioso (a família já não existe p/ gravar trilha).
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        });

        return redirect()->route('login')->with('status', 'Cadastro encerrado. Até logo!');
    }
}
