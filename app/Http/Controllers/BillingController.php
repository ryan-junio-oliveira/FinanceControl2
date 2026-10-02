<?php

namespace App\Http\Controllers;

use App\Services\BillingService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function plans(BillingService $billing): View
    {
        $family = Fin::family();

        return view('pages.plans', [
            'billing' => $billing,
            'status' => $billing->status($family),
        ]);
    }

    public function checkout(Request $request, BillingService $billing): RedirectResponse
    {
        $url = $billing->checkoutUrl(Fin::family(), (string) $request->input('plano', 'mensal'));
        abort_if($url === '', 400, 'Não foi possível gerar o pagamento.');

        return redirect()->away($url);
    }

    public function cancel(BillingService $billing): RedirectResponse
    {
        $billing->cancel(Fin::family());

        return redirect()->route('plans')->with('status', 'Assinatura cancelada.');
    }

    public function webhook(Request $request, BillingService $billing): JsonResponse
    {
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
        if ($secret === '') {
            return true; // sem secret configurado, aceita (desenvolvimento)
        }

        $header = (string) $request->header('x-signature');
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
