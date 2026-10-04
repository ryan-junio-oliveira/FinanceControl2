<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function plans(BillingService $billing): View
    {
        $group = Fin::group();

        return view('pages.plans', [
            'billing' => $billing,
            'status' => $billing->status($group),
        ]);
    }

    public function checkout(Request $request, BillingService $billing): RedirectResponse
    {
        $url = $billing->checkoutUrl(Fin::group(), (string) $request->input('plano', 'mensal'));
        abort_if($url === '', 400, 'Não foi possível gerar o pagamento.');

        return redirect()->away($url);
    }

    public function cancel(BillingService $billing): RedirectResponse
    {
        $billing->cancel(Fin::group());

        return redirect()->route('plans')->with('status', 'Assinatura cancelada.');
    }

    public function webhook(Request $request, BillingService $billing): JsonResponse
    {
        if ($billing->enabled() && config('billing.mercado_pago.webhook_secret') === '') {
            Log::warning('[billing] Webhook do Mercado Pago sem secret configurado.');
            // Fail-closed em produção: sem secret não há como autenticar o emissor.
            abort_if(app()->isProduction(), 503, 'Webhook indisponível.');
        }
        if (! $this->validSignature($request)) {
            return response()->json(['error' => 'invalid signature'], 401);
        }

        $billing->handleWebhook($request->json()->all());

        return response()->json(['ok' => true]);
    }

    /** Valida o header x-signature do MP (HMAC-SHA256 com o secret do webhook). */
    private function validSignature(Request $request): bool
    {
        $secret = (string) config('billing.mercado_pago.webhook_secret');
        $header = (string) $request->header('x-signature');

        // Sem secret: só aceita em ambiente local/teste (simulação no painel do MP).
        if ($secret === '') {
            return ! app()->isProduction();
        }
        // Com secret configurado, a assinatura é obrigatória.
        if ($header === '') {
            return false;
        }

        if (! preg_match('/ts=(\d+),v1=([a-f0-9]{64})/', $header, $m)) {
            return false;
        }
        [, $ts, $v1] = $m;
        $id = (string) $request->json('data.id');
        $manifest = 'id:'.$id.':'.$ts;
        $hash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($hash, $v1);
    }
}
