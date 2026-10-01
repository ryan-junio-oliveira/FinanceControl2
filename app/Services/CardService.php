<?php

namespace App\Services;

use App\Models\CardTransaction;
use App\Models\CreditCard;
use App\Models\Family;
use App\Models\Transaction;
use App\Support\Fin;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Regras de domínio dos cartões e itens de fatura.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class CardService
{
    /** Cartões ativos da família (com banco da conta vinculada). */
    public function list(Family $family): Collection
    {
        return $family->creditCards()->with(['holder', 'account.bank'])->where('active', true)->orderBy('name')->get();
    }

    /** Membros + categorias de despesa para o formulário de item. */
    public function itemOptions(Family $family): array
    {
        return [
            'cartoes' => $family->creditCards()->where('active', true)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
            'categorias' => $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get(),
        ];
    }

    /** Contas ativas com banco (para formulários de cartão). */
    public function activeAccounts(Family $family): Collection
    {
        return $family->accounts()->with('bank')->where('active', true)->orderBy('name')->get();
    }

    /** Dados completos da tela de cartões (faturas, por membro, opções). */
    public function dashboard(Family $family): array
    {
        $hoje = Carbon::today();

        $cartoes = $family->creditCards()->with(['holder', 'account.bank'])
            ->withSum(['items as open_invoice_sum' => fn ($q) => $q->where('status', 'pendente')], 'amount')
            ->where('active', true)->get();

        // Fatura atual (período corrente) x próxima fatura por cartão.
        $cartoes->each(function ($c) use ($hoje) {
            [$ini, $fim] = $c->currentInvoiceRange($hoje);
            $c->setAttribute('fatura_atual', (float) $c->items()->where('status', 'pendente')
                ->whereBetween('occurred_on', [$ini->toDateString(), $fim->toDateString()])->sum('amount'));
            $c->setAttribute('proxima_fatura', (float) $c->items()->where('status', 'pendente')
                ->whereDate('occurred_on', '>', $fim->toDateString())->sum('amount'));
            $c->setAttribute('prox_fechamento', $c->nextClosingDate($hoje));
            $c->setAttribute('prox_vencimento', $c->nextDueDate($hoje));
        });

        return [
            'cartoes' => $cartoes,
            'fatura' => CardTransaction::where('family_id', $family->id)
                ->with(['member', 'category', 'card'])
                ->orderByDesc('occurred_on')->paginate(12),
            'porMembro' => CardTransaction::where('family_id', $family->id)->where('status', 'pendente')
                ->selectRaw('user_id, SUM(amount) as total')->groupBy('user_id')
                ->orderByDesc('total')->with('member')->get(),
            'categorias' => $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
        ];
    }

    public function createCard(Family $family, array $data): CreditCard
    {
        if (! empty($data['holder_user_id'])) {
            $family->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }

        return $family->creditCards()->create($data);
    }

    public function updateCard(CreditCard $card, Family $family, array $data, bool $active): CreditCard
    {
        $data['active'] = $active;
        if (! empty($data['holder_user_id'])) {
            $family->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        $card->update($data);

        return $card->refresh();
    }

    public function deleteCard(CreditCard $card): void
    {
        abort_if($card->items()->exists(), 422, 'Cartão com lançamentos não pode ser excluído. Desative-o.');
        $card->delete();
    }

    /**
     * Lança compra/estorno (ou parcelas). Retorna itens criados + nº de parcelas.
     *
     * @return array{items: Collection<int, CardTransaction>, parcelas: int}
     */
    public function createItem(Family $family, array $data): array
    {
        $card = $family->creditCards()->findOrFail($data['credit_card_id']);
        $family->users()->findOrFail($data['user_id']);

        // Estorno entra com valor negativo e sem parcelamento.
        $isEstorno = ($data['kind'] ?? 'compra') === 'estorno';
        if ($isEstorno) {
            $data['amount'] = -abs((float) $data['amount']);
            $parcelas = 1;
        } else {
            $parcelas = max(1, min(48, (int) ($data['installments_total'] ?? 1)));
        }
        unset($data['installments_total']);

        $items = collect();
        DB::transaction(function () use ($card, $family, $data, $parcelas, $items) {
            if ($parcelas === 1) {
                $items->push($card->items()->create($data + ['family_id' => $family->id, 'status' => 'pendente']));

                return;
            }
            $group = (string) Str::uuid();
            $totalCents = (int) round((float) $data['amount'] * 100);
            $base = intdiv($totalCents, $parcelas);
            $resto = $totalCents % $parcelas;
            $baseDate = Carbon::parse($data['occurred_on']);

            for ($i = 1; $i <= $parcelas; $i++) {
                $cents = $base + ($i <= $resto ? 1 : 0);
                $items->push($card->items()->create($data + [
                    'family_id' => $family->id,
                    'status' => 'pendente',
                    'description' => "{$data['description']} ({$i}/{$parcelas})",
                    'amount' => $cents / 100,
                    'occurred_on' => $baseDate->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                    'installment_group_id' => $group,
                    'installment_number' => $i,
                    'installments_total' => $parcelas,
                ]));
            }
        });

        return ['items' => $items, 'parcelas' => $parcelas];
    }

    public function settleItem(CardTransaction $item, Family $family): CardTransaction
    {
        if ($item->status === 'pago') {
            return $item;
        }

        DB::transaction(function () use ($family, $item) {
            $item->update(['status' => 'pago']);

            // Baixa financeira: se o cartão tem conta vinculada e o valor é positivo, gera a despesa.
            // Estornos e cartões sem conta vinculada mantêm só a marcação.
            $card = $item->card()->with('account')->first();
            if ($card?->account_id && (float) $item->amount > 0) {
                $family->accounts()->findOrFail($card->account_id);
                Transaction::create([
                    'family_id' => $family->id,
                    'user_id' => $item->user_id,
                    'account_id' => $card->account_id,
                    'category_id' => $item->category_id,
                    'type' => 'despesa',
                    'description' => "Fatura {$card->name} — {$item->description}",
                    'amount' => $item->amount,
                    'occurred_on' => $item->occurred_on,
                    'due_on' => $item->occurred_on,
                    'status' => 'pago',
                ]);
            }
        });

        return $item->refresh();
    }

    /** Paga a fatura cheia. Retorna o total liquidado (null se não havia pendentes). */
    public function payInvoice(CreditCard $cartao, Family $family, int $actorId): ?float
    {
        $pendentes = $cartao->items()->where('status', 'pendente')->orderBy('occurred_on')->get();
        if ($pendentes->isEmpty()) {
            return null;
        }

        $total = (float) $pendentes->sum('amount');

        DB::transaction(function () use ($family, $cartao, $pendentes, $total, $actorId) {
            $cartao->items()->where('status', 'pendente')->update(['status' => 'pago']);

            // Só gera despesa se o líquido for positivo e houver conta vinculada.
            if ($cartao->account_id && $total > 0) {
                $family->accounts()->findOrFail($cartao->account_id);
                Transaction::create([
                    'family_id' => $family->id,
                    'user_id' => $cartao->holder_user_id ?? $actorId,
                    'account_id' => $cartao->account_id,
                    'category_id' => $pendentes->first()->category_id,
                    'type' => 'despesa',
                    'description' => "Pagamento fatura {$cartao->name}",
                    'amount' => $total,
                    'occurred_on' => Fin::today(),
                    'due_on' => Fin::today(),
                    'status' => 'pago',
                ]);
            }
        });

        return $total;
    }
}
