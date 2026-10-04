<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupRole
{
    /**
     * @param  string  ...$roles  Papéis permitidos (admin, co_admin, dependente, junior).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $api = $request->is('api/*') || $request->expectsJson();

        // Sem usuário autenticado: JSON na API, login na web.
        if (! $user) {
            return $api
                ? response()->json(['message' => 'Não autenticado.'], 401)
                : redirect()->route('login');
        }

        // Usuário sem conta vinculada: sai daqui com mensagem clara,
        // em vez de um loop silencioso login → dashboard → login.
        if (! $user->group_id) {
            if ($api) {
                return response()->json(['message' => 'Sua conta não está vinculada a nenhum grupo.'], 422);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Sua conta não está vinculada a nenhum grupo. Cadastre-se novamente para criar sua conta.',
            ]);
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            abort(403, 'Seu papel não permite esta ação.');
        }

        return $next($request);
    }
}
