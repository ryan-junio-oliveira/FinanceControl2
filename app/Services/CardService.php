<?php

namespace App\Services;

use App\Models\CardTransaction;
use App\Models\CreditCard;
use App\Models\Group;
use App\Models\Transaction;
use App\Support\Fin;
use App\Support\Installments;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Regras de domínio dos cartões e itens de fatura.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class CardService
{
    /** Cartões ativos do grupo (com banco da conta vinculada). */
    public function list(Group $group): Collection
    {
        return $group->creditCards()->with(['holder', 'account.bank'])->where('active', true)->orderBy('name')->get();
    }

    /** Membros + categorias de despesa para o formulário de item. */
    public function itemOptions(Group $group): array
    {
        return [
            'cartoes' => $group->creditCards()->where('active', true)->orderBy('name')->get(),
            'membros' => $group->users()->orderBy('name')->get(),
            'categorias' => $group->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get(),
        ];
    }

    /** Contas ativas com banco (para formulários de cartão). */
    public function activeAccounts(Group $group): Collection
    {
        return $group->accounts()->with('bank')->where('active', true)->orderBy('name')->get();
    }

    /** Dados completos da tela de cartões (faturas, por membro, opções). */
    public function dashboard(Group $group, ?string $mes = null): array
    {
        $hoje = Carbon::today();

        $cartoes = $group->creditCards()->with(['holder', 'account.bank'])
            ->withSum(['items as open_invoice_sum' => fn ($q) => $q->where('status', 'pendente')], 'amount')
            ->where('active', true)->get();

        // Fatura atual x próxima em 2 queries agrupadas (evita 2 SUM por cartão).
        $ranges = [];
        foreach ($cartoes as $c) {
            [$ini, $fim] = $c->currentInvoiceRange($hoje);
            $ranges[$c->id] = [$ini->toDateString(), $fim->toDateString()];
        }
        $atualMap = $ranges === [] ? collect() : CardTransaction::where('group_id', $group->id)
            ->where('status', 'pendente')
            ->where(function ($q) use ($ranges) {
                foreach ($ranges as $cardId => [$ini, $fim]) {
                    $q->orWhere(fn ($qq) => $qq->where('credit_card_id', $cardId)->whereBetween('occurred_on', [$ini, $fim]));
                }
            })->groupBy('credit_card_id')->selectRaw('credit_card_id, SUM(amount) as total')->pluck('total', 'credit_card_id');
        $proxMap = $ranges === [] ? collect() : CardTransaction::where('group_id', $group->id)
            ->where('status', 'pendente')
            ->where(function ($q) use ($ranges) {
                foreach ($ranges as $cardId => [$ini, $fim]) {
                    $q->orWhere(fn ($qq) => $qq->where('credit_card_id', $cardId)->whereDate('occurred_on', '>', $fim));
                }
            })->groupBy('credit_card_id')->selectRaw('credit_card_id, SUM(amount) as total')->pluck('total', 'credit_card_id');

        // Fatura atual (período corrente) x próxima fatura por cartão.
        $cartoes->each(function ($c) use ($hoje, $atualMap, $proxMap) {
            $c->setAttribute('fatura_atual', (float) ($atualMap[$c->id] ?? 0));
            $c->setAttribute('proxima_fatura', (float) ($proxMap[$c->id] ?? 0));
            $c->setAttribute('prox_fechamento', $c->nextClosingDate($hoje));
            $c->setAttribute('prox_vencimento', $c->nextDueDate($hoje));
        });

        $fatura = CardTransaction::where('group_id', $group->id)
            ->with(['member', 'category', 'card']);
        if ($mes) {
            [$y, $m] = array_map('intval', explode('-', $mes));
            $fatura->whereYear('occurred_on', $y)->whereMonth('occurred_on', $m);
        }

        return [
            'mes' => $mes ?? Fin::month(),
            'cartoes' => $cartoes,
            'fatura' => $fatura->orderByDesc('occurred_on')->paginate(12),
            'porMembro' => $this->gastoPorMembro($group),
            'categorias' => $group->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get(),
            'membros' => $group->users()->orderBy('name')->get(),
            'contas' => $group->accounts()->where('active', true)->orderBy('name')->get(),
        ];
    }

    /** Total pendente por membro com nome hidratado (1 agregação + 1 query de usuários). */
    private function gastoPorMembro(Group $group): Collection
    {
        $totais = CardTransaction::where('group_id', $group->id)->where('status', 'pendente')
            ->selectRaw('user_id, SUM(amount) as total')->groupBy('user_id')
            ->orderByDesc('total')->pluck('total', 'user_id');
        if ($totais->isEmpty()) {
            return collect();
        }
        $users = $group->users()->whereIn('id', $totais->keys())->get()->keyBy('id');

        return $totais->map(fn ($total, $uid) => (object) [
            'user_id' => $uid,
            'total' => (float) $total,
            'member' => $users[$uid] ?? null,
        ])->values();
    }

    public function createCard(Group $group, array $data): CreditCard
    {
        if (! empty($data['holder_user_id'])) {
            $group->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $group->accounts()->findOrFail($data['account_id']);
        }

        return $group->creditCards()->create($data);
    }

    public function updateCard(CreditCard $card, Group $group, array $data, bool $active): CreditCard
    {
        $data['active'] = $active;
        if (! empty($data['holder_user_id'])) {
            $group->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $group->accounts()->findOrFail($data['account_id']);
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
    public function createItem(Group $group, array $data): array
    {
        $card = $group->creditCards()->findOrFail($data['credit_card_id']);
        $group->users()->findOrFail($data['user_id']);

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
        DB::transaction(function () use ($card, $group, $data, $parcelas, $items) {
            if ($parcelas === 1) {
                $items->push($card->items()->create($data + ['group_id' => $group->id, 'status' => 'pendente']));

                return;
            }
            foreach (Installments::split((float) $data['amount'], $parcelas, $data['occurred_on'], null, $data['description']) as $p) {
                $items->push($card->items()->create($data + [
                    'group_id' => $group->id,
                    'status' => 'pendente',
                    'description' => $p['description'],
                    'amount' => $p['amount'],
                    'occurred_on' => $p['occurred_on'],
                    'installment_group_id' => $p['installment_group_id'],
                    'installment_number' => $p['installment_number'],
                    'installments_total' => $p['installments_total'],
                ]));
            }
        });

        return ['items' => $items, 'parcelas' => $parcelas];
    }

    public function settleItem(CardTransaction $item, Group $group): CardTransaction
    {
        if ($item->status === 'pago') {
            return $item;
        }

        DB::transaction(function () use ($group, $item) {
            $item->update(['status' => 'pago']);

            // Baixa financeira: se o cartão tem conta vinculada e o valor é positivo, gera a despesa.
            // Estornos e cartões sem conta vinculada mantêm só a marcação.
            $card = $item->card()->with('account')->first();
            if ($card?->account_id && (float) $item->amount > 0) {
                $group->accounts()->findOrFail($card->account_id);
                Transaction::create([
                    'group_id' => $group->id,
                    'user_id' => $item->user_id,
                    'account_id' => $card->account_id,
                    'category_id' => $item->category_id,
                    'type' => 'despesa',
                    'description' => "Fatura {$card->name} — {$item->description}",
                    'amount' => $item->amount,
                    'occurred_on' => $item->occurred_on,
                    'due_on' => $item->occurred_on,
                    'status' => 'pago',
                    'source' => 'invoice',
                ]);
            }
        });

        return $item->refresh();
    }

    /** Paga a fatura cheia. Retorna o total liquidado (null se não havia pendentes). */
    public function payInvoice(CreditCard $cartao, Group $group, int $actorId): ?float
    {
        $pendentes = $cartao->items()->where('status', 'pendente')->orderBy('occurred_on')->get();
        if ($pendentes->isEmpty()) {
            return null;
        }

        $total = (float) $pendentes->sum('amount');

        DB::transaction(function () use ($group, $cartao, $pendentes, $total, $actorId) {
            $cartao->items()->where('status', 'pendente')->update(['status' => 'pago']);

            // Só gera despesa se o líquido for positivo e houver conta vinculada.
            if ($cartao->account_id && $total > 0) {
                $group->accounts()->findOrFail($cartao->account_id);
                Transaction::create([
                    'group_id' => $group->id,
                    'user_id' => $cartao->holder_user_id ?? $actorId,
                    'account_id' => $cartao->account_id,
                    'category_id' => $pendentes->first()->category_id,
                    'type' => 'despesa',
                    'description' => "Pagamento fatura {$cartao->name}",
                    'amount' => $total,
                    'occurred_on' => Fin::today(),
                    'due_on' => Fin::today(),
                    'status' => 'pago',
                    'source' => 'invoice',
                ]);
            }
        });

        return $total;
    }
}
