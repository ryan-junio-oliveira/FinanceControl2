<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Assinatura e cobrança via Mercado Pago (preapproval = recorrência).
 *
 * - checkoutUrl(): gera o link de pagamento da assinatura.
 * - handleWebhook(): eventos do MP ativam/desativam o plano Pro.
 * - isActive(): trial ativo OU plano Pro pago.
 */
final class BillingService
{
    /** Pagamentos ativos? (exige BILLING_ENABLED + access token). */
    public function enabled(): bool
    {
        return config('billing.enabled')
            && config('billing.mercado_pago.access_token') !== '';
    }

    /** Família com acesso liberado (trial em vigor ou Pro). */
    public function isActive(Family $family): bool
    {
        if (! config('billing.enabled')) {
            return true;
        }

        return $family->trial_ends_at?->isFuture() || $family->plan === 'pro';
    }

    /** Dados para a tela de planos. */
    public function status(Family $family): array
    {
        return [
            'trial_ativo' => (bool) $family->trial_ends_at?->isFuture(),
            'trial_ends_at' => $family->trial_ends_at?->toDateString(),
            'pro' => $family->plan === 'pro',
            'preapproval' => $family->mp_preapproval_id,
            'plans' => config('billing.plans'),
            'enabled' => $this->enabled(),
        ];
    }

    /** Gera o link de checkout da assinatura e guarda a preapproval na família. */
    public function checkoutUrl(Family $family, string $frequencia): string
    {
        abort_unless($this->enabled(), 400, 'Pagamentos indisponíveis no momento.');
        $plan = config("billing.plans.{$frequencia}") ?? abort(422, 'Plano inválido.');

        $admin = $family->users()->where('role', 'admin')->orderBy('id')->first();
        $resp = $this->http()->post('/preapproval', [
            'reason' => 'Prumo '.$plan['label'],
            'auto_recurring' => [
                'frequency' => (int) $plan['frequencia'],
                'frequency_type' => (string) $plan['tipo'],
                'transaction_amount' => (float) $plan['price'],
                'currency_id' => 'BRL',
            ],
            'payer' => ['email' => $admin?->email ?? ''],
            'external_reference' => (string) $family->id,
            'back_url' => config('billing.mercado_pago.back_url'),
        ]);
        abort_if(! $resp->successful(), 502, 'Erro ao gerar o pagamento. Tente novamente.');

        $data = $resp->json();
        $family->update(['mp_preapproval_id' => $data['id'] ?? null]);

        return $data['init_point'] ?? '';
    }

    /** Cancela a assinatura no MP e rebaixa a família. */
    public function cancel(Family $family): void
    {
        if ($id = $family->mp_preapproval_id) {
            $this->http()->put("/preapproval/{$id}", ['status' => 'cancelled']);
        }
        $family->update(['plan' => 'free']);
    }

    /**
     * Processa o evento do webhook (topic=preapproval).
     * 'authorized' => Pro ativo; qualquer outro estado => free.
     */
    public function handleWebhook(array $payload): void
    {
        $topic = $payload['type'] ?? $payload['topic'] ?? null;
        $id = $payload['data']['id'] ?? null;
        if ($topic !== 'preapproval' || ! $id) {
            return;
        }

        $resp = $this->http()->get("/preapproval/{$id}");
        if (! $resp->successful()) {
            return;
        }

        $family = Family::where('mp_preapproval_id', $id)->first();
        if (! $family) {
            return;
        }

        $family->update(['plan' => $resp->json('status') === 'authorized' ? 'pro' : 'free']);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('billing.mercado_pago.base_url'))
            ->withToken((string) config('billing.mercado_pago.access_token'))
            ->acceptJson()
            ->timeout(20);
    }
}
