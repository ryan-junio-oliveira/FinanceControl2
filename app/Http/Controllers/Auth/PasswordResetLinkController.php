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

        // Mensagem única exista ou não a conta: não revela quais e-mails têm cadastro.
        return back()->with('status', 'Se este e-mail estiver cadastrado, enviamos o link de recuperação. Verifique sua caixa de entrada e o spam.');
    }
}
