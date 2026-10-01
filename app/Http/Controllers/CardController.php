<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardItemRequest;
use App\Http\Requests\CreditCardRequest;
use App\Models\CardTransaction;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Support\Fin;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();

        $cartoes = $family->creditCards()->with(['holder', 'account.bank'])
            ->withSum(['items as open_invoice_sum' => fn ($q) => $q->where('status', 'pendente')], 'amount')
            ->where('active', true)->get();

        // Fatura atual (período corrente) x próxima fatura por cartão.
        $hoje = Carbon::today();
        $cartoes->each(function ($c) use ($hoje) {
            [$ini, $fim] = $c->currentInvoiceRange($hoje);
            $c->setAttribute('fatura_atual', (float) $c->items()->where('status', 'pendente')
                ->whereBetween('occurred_on', [$ini->toDateString(), $fim->toDateString()])->sum('amount'));
            $c->setAttribute('proxima_fatura', (float) $c->items()->where('status', 'pendente')
                ->whereDate('occurred_on', '>', $fim->toDateString())->sum('amount'));
            $c->setAttribute('prox_fechamento', $c->nextClosingDate($hoje));
            $c->setAttribute('prox_vencimento', $c->nextDueDate($hoje));
        });

        $fatura = CardTransaction::where('family_id', $family->id)
            ->with(['member', 'category', 'card'])
            ->orderByDesc('occurred_on')->paginate(12);

        $porMembro = CardTransaction::where('family_id', $family->id)->where('status', 'pendente')
            ->selectRaw('user_id, SUM(amount) as total')->groupBy('user_id')
            ->orderByDesc('total')->with('member')->get();

        $categorias = $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get();
        $membros = $family->users()->orderBy('name')->get();
        $contas = $family->accounts()->where('active', true)->orderBy('name')->get();

        return view('pages.cartoes', compact('cartoes', 'fatura', 'porMembro', 'categorias', 'membros', 'contas'));
    }

    public function create(): View
    {
        $family = Fin::family();

        return view('pages.cards.form', [
            'card' => null,
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->with('bank')->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(CreditCard $card): View
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);

        return view('pages.cards.form', [
            'card' => $card->load('account.bank'),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->with('bank')->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(CreditCardRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        if (! empty($data['holder_user_id'])) {
            $family->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        $family->creditCards()->create($data);

        return redirect()->route('cartoes')->with('status', 'Cartão adicionado.');
    }

    public function update(CreditCardRequest $request, CreditCard $card): RedirectResponse
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);

        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        if (! empty($data['holder_user_id'])) {
            $family->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        $card->update($data);

        return redirect()->route('cartoes')->with('status', 'Cartão atualizado.');
    }

    public function destroy(CreditCard $card): RedirectResponse
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);
        abort_if($card->items()->exists(), 422, 'Cartão com lançamentos não pode ser excluído. Desative-o.');
        $card->delete();

        return redirect()->route('cartoes')->with('status', 'Cartão excluído.');
    }

    public function createItem(): View
    {
        $family = Fin::family();

        return view('pages.cards.item-form', [
            'cartoes' => $family->creditCards()->where('active', true)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
            'categorias' => $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get(),
        ]);
    }

    public function storeItem(CardItemRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $card = $family->creditCards()->findOrFail($data['credit_card_id']);
        $member = $family->users()->findOrFail($data['user_id']);

        // Estorno entra com valor negativo e sem parcelamento.
        $isEstorno = ($data['kind'] ?? 'compra') === 'estorno';
        if ($isEstorno) {
            $data['amount'] = -abs((float) $data['amount']);
            $parcelas = 1;
        } else {
            $parcelas = max(1, min(48, (int) ($data['installments_total'] ?? 1)));
        }
        unset($data['installments_total']);

        DB::transaction(function () use ($card, $family, $data, $parcelas) {
            if ($parcelas === 1) {
                $card->items()->create($data + ['family_id' => $family->id, 'status' => 'pendente']);

                return;
            }
            $group = (string) Str::uuid();
            $totalCents = (int) round((float) $data['amount'] * 100);
            $base = intdiv($totalCents, $parcelas);
            $resto = $totalCents % $parcelas;
            $baseDate = Carbon::parse($data['occurred_on']);

            for ($i = 1; $i <= $parcelas; $i++) {
                $cents = $base + ($i <= $resto ? 1 : 0);
                $card->items()->create($data + [
                    'family_id' => $family->id,
                    'status' => 'pendente',
                    'description' => "{$data['description']} ({$i}/{$parcelas})",
                    'amount' => $cents / 100,
                    'occurred_on' => $baseDate->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                    'installment_group_id' => $group,
                    'installment_number' => $i,
                    'installments_total' => $parcelas,
                ]);
            }
        });

        return redirect()->route('cartoes')->with('status',
            ($data['kind'] ?? 'compra') === 'estorno' ? 'Estorno lançado na fatura.'
                : ($parcelas > 1 ? "Compra parcelada em {$parcelas}x." : 'Compra lançada na fatura.'));
    }

    public function settleItem(CardTransaction $item): RedirectResponse
    {
        $family = Fin::family();
        abort_if($item->family_id !== $family->id, 404);

        if ($item->status === 'pago') {
            return back()->with('status', 'Item já estava liquidado.');
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

        return back()->with('status', 'Item da fatura liquidado.');
    }

    /** Paga a fatura cheia: liquida todos os pendentes e gera a despesa na conta vinculada. */
    public function payInvoice(CreditCard $card): RedirectResponse
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);

        $pendentes = $card->items()->where('status', 'pendente')->orderBy('occurred_on')->get();
        if ($pendentes->isEmpty()) {
            return back()->with('status', 'Nenhum item pendente nesta fatura.');
        }

        $total = (float) $pendentes->sum('amount');

        DB::transaction(function () use ($family, $card, $pendentes, $total) {
            $card->items()->where('status', 'pendente')->update(['status' => 'pago']);

            // Só gera despesa se o líquido for positivo e houver conta vinculada.
            if ($card->account_id && $total > 0) {
                $family->accounts()->findOrFail($card->account_id);
                Transaction::create([
                    'family_id' => $family->id,
                    'user_id' => $card->holder_user_id ?? request()->user()->id,
                    'account_id' => $card->account_id,
                    'category_id' => $pendentes->first()->category_id,
                    'type' => 'despesa',
                    'description' => "Pagamento fatura {$card->name}",
                    'amount' => $total,
                    'occurred_on' => Fin::today(),
                    'due_on' => Fin::today(),
                    'status' => 'pago',
                ]);
            }
        });

        return back()->with('status', 'Fatura paga ('.Fin::money($total).').');
    }
}
