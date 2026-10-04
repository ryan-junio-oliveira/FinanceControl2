<?php

namespace App\Http\Middleware;

use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Bloqueia o uso após o trial quando o grupo não tem o plano Pro ativo. */
class EnsureActivePlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || app(BillingService::class)->isActive($user->group)) {
            return $next($request);
        }

        // Pós-trial sem Pro: libera apenas perfil, pagamento e (admin) grupo.
        $route = (string) ($request->route()?->getName() ?? '');
        $isPerfil = str_starts_with($route, 'perfil');
        $isGrupoAdmin = str_starts_with($route, 'grupo') && $user->isAdmin();
        if ($isPerfil || $isGrupoAdmin) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Assinatura expirada. Renove o plano para continuar.');
        }

        return redirect()->route('plans');
    }
}
