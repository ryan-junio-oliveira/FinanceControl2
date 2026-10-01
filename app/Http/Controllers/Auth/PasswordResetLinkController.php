<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Exibe a tela de solicitação do link de recuperação.
     */
    public function create(): View
    {
        return view('pages.auth.recuperar');
    }

    /**
     * Lida com o envio do link de redefinição de senha.
     */
    public function store(SendResetLinkRequest $request): RedirectResponse
    {
        $request->validated();

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Enviamos o link de recuperação para '.$request->email.'. Verifique sua caixa de entrada e o spam.')
            : back()->withErrors(['email' => 'Não encontramos uma conta com este e-mail. Confira e tente de novo.'])->onlyInput('email');
    }
}
