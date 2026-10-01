<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Exibe a tela de redefinição de senha.
     */
    public function create(Request $request, string $token): View
    {
        return view('pages.auth.redefinir', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Lida com a redefinição da senha.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Senha redefinida! Entre com a nova senha.')
            : back()->withErrors(['email' => 'Este link é inválido ou expirou. Solicite um novo abaixo.'])->onlyInput('email');
    }
}
