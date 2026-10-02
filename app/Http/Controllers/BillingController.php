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
        $billing->handleWebhook($request->json()->all());

        return response()->json(['ok' => true]);
    }
}
