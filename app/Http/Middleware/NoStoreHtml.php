<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Em desenvolvimento, evita cache de páginas HTML para que o navegador
 * sempre revalide e busque os assets recém-buildados (novo hash).
 */
class NoStoreHtml
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('build/*') && ! $request->is('storage/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return $response;
    }
}
