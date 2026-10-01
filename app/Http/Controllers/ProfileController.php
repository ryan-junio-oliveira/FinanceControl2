<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Encerra o cadastro: só o administrador principal pode.
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
