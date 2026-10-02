<?php

namespace App\Services;

use App\Models\Family;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Assinatura e cobrança via Mercado Pago (preapproval = recorrência).
 *
 * - checkoutUrl(): gera o link de pagamento da assinatura.
 * - handleWebhook(): eventos de preapproval e payment mantêm o plano em dia.
 * - isActive(): trial em vigor OU plano pago até plan_paid_until.
 */
final class BillingService
{
    /** Pagamentos ativos? (exige BILLING_ENABLED + access token). */
    public function enabled(): bool
    {
        return config('billing.enabled')
            && config('billing.mercado_pago.access_token') !== '';
    }

    /** Família com acesso liberado (trial em vigor ou Pro pago até hoje). */
    public function isActive(Family $family): bool
    {
        if (! config('billing.enabled')) {
            return true;
        }
        if ($family->trial_ends_at?->isFuture()) {
            return true;
        }

        return $family->plan === 'pro'
            && $family->plan_paid_until
            && now()->startOfDay()->lte(Carbon::parse($family->plan_paid_until));
    }

    /** Dados para a tela de planos. */
    public function status(Family $family): array
    {
        return [
            'trial_ativo' => (bool) $family->trial_ends_at?->isFuture(),
            'trial_ends_at' => $family->trial_ends_at?->toDateString(),
            'pro' => $this->isActive($family),
            'paid_until' => $family->plan_paid_until?->toDateString(),
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
        $payerEmail = (string) (config('billing.mercado_pago.test_payer_email') ?: $admin?->email ?? '');
        $resp = $this->http()->post('/preapproval', [
            'reason' => 'Prumo '.$plan['label'],
            'auto_recurring' => [
                'frequency' => (int) $plan['frequencia'],
                'frequency_type' => (string) $plan['tipo'],
                'transaction_amount' => (float) $plan['price'],
                'currency_id' => 'BRL',
            ],
            'payer_email' => $payerEmail,
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
        $family->update(['plan' => 'free', 'plan_paid_until' => null]);
    }

    /** Processa o evento do webhook: preapproval (status) ou payment (renovação). */
    public function handleWebhook(array $payload): void
    {
        $topic = $payload['type'] ?? $payload['topic'] ?? null;
        $entity = $payload['entity'] ?? null;
        $id = $payload['data']['id'] ?? null;
        if (! $id) {
            return;
        }

        // Tipos reais do MP: "subscription_preapproval" (assinatura) e "payment".
        $isPreapproval = in_array($topic, ['preapproval', 'subscription_preapproval'], true) || $entity === 'preapproval';
        if ($isPreapproval) {
            $this->handlePreapproval($id);

            return;
        }
        if ($topic === 'payment') {
            $this->handlePayment($id);
        }
    }

    private function handlePreapproval(string $id): void
    {
        $resp = $this->http()->get("/preapproval/{$id}");
        if (! $resp->successful()) {
            return;
        }
        $data = $resp->json();
        $family = Family::where('mp_preapproval_id', $id)->first();
        if (! $family) {
            return;
        }

        if (($data['status'] ?? '') === 'authorized') {
            $until = $data['next_payment_date'] ?? now()->addMonth()->toDateString();
            $family->update([
                'plan' => 'pro',
                'plan_paid_until' => Carbon::parse($until)->toDateString(),
            ]);
        } else {
            $family->update(['plan' => 'free', 'plan_paid_until' => null]);
        }
    }

    private function handlePayment(string $id): void
    {
        $resp = $this->http()->get("/v1/payments/{$id}");
        if (! $resp->successful()) {
            return;
        }
        $data = $resp->json();
        if (($data['status'] ?? '') !== 'approved') {
            return;
        }
        $preId = $data['preapproval_id'] ?? null;
        $family = $preId ? Family::where('mp_preapproval_id', $preId)->first() : null;
        if (! $family) {
            return;
        }

        $months = $this->monthsOfPreapproval($preId);
        $family->update([
            'plan' => 'pro',
            'plan_paid_until' => now()->addMonths($months)->toDateString(),
        ]);
    }

    /** Período da recorrência (1 = mensal, 12 = anual). */
    private function monthsOfPreapproval(string $id): int
    {
        $resp = $this->http()->get("/preapproval/{$id}");
        if ($resp->successful()) {
            return max(1, (int) ($resp->json('auto_recurring.frequency') ?? 1));
        }

        return 1;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('billing.mercado_pago.base_url'))
            ->withToken((string) config('billing.mercado_pago.access_token'))
            ->acceptJson()
            ->timeout(20);
    }
}
