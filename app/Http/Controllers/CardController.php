<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardItemRequest;
use App\Http\Requests\CreditCardRequest;
use App\Models\CardTransaction;
use App\Models\CreditCard;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();

        $cartoes = $family->creditCards()->with(['holder', 'account', 'items' => fn ($q) => $q->where('status', 'pendente')])->where('active', true)->get();

        $totalFaturas = $cartoes->sum(fn ($c) => $c->open_invoice);
        $limiteTotal = (float) $cartoes->sum('credit_limit');
        $usoGlobal = $limiteTotal > 0 ? round($totalFaturas / $limiteTotal * 100, 1) : 0;

        $fatura = CardTransaction::where('family_id', $family->id)
            ->with(['member', 'category', 'card'])
            ->orderByDesc('occurred_on')->paginate(12);

        $porMembro = CardTransaction::where('family_id', $family->id)->where('status', 'pendente')
            ->selectRaw('user_id, SUM(amount) as total')->groupBy('user_id')
            ->orderByDesc('total')->with('member')->get();

        $categorias = $family->categories()->where('type', 'despesa')->where('archived', false)->orderBy('name')->get();
        $membros = $family->users()->orderBy('name')->get();
        $contas = $family->accounts()->where('active', true)->orderBy('name')->get();

        return view('pages.cartoes', compact('cartoes', 'totalFaturas', 'limiteTotal', 'usoGlobal', 'fatura', 'porMembro', 'categorias', 'membros', 'contas'));
    }

    public function create(): View
    {
        $family = Fin::family();

        return view('pages.cards.form', [
            'cartao' => null,
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(CreditCard $cartao): View
    {
        $family = Fin::family();
        abort_if($cartao->family_id !== $family->id, 404);

        return view('pages.cards.form', [
            'cartao' => $cartao->load('account'),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
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

    public function update(CreditCardRequest $request, CreditCard $cartao): RedirectResponse
    {
        $family = Fin::family();
        abort_if($cartao->family_id !== $family->id, 404);

        $data = $request->validated();
        $data['active'] = $request->boolean('active');
        if (! empty($data['holder_user_id'])) {
            $family->users()->findOrFail($data['holder_user_id']);
        }
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        $cartao->update($data);

        return redirect()->route('cartoes')->with('status', 'Cartão atualizado.');
    }

    public function destroy(CreditCard $cartao): RedirectResponse
    {
        $family = Fin::family();
        abort_if($cartao->family_id !== $family->id, 404);
        abort_if($cartao->items()->exists(), 422, 'Cartão com lançamentos não pode ser excluído. Desative-o.');
        $cartao->delete();

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
        $family->users()->findOrFail($data['user_id']);

        $card->items()->create($data + ['family_id' => $family->id, 'status' => 'pendente']);

        return redirect()->route('cartoes')->with('status', 'Compra lançada na fatura.');
    }

    public function settleItem(CardTransaction $item): RedirectResponse
    {
        $family = Fin::family();
        abort_if($item->family_id !== $family->id, 404);
        $item->update(['status' => 'pago']);

        return back()->with('status', 'Item da fatura liquidado.');
    }
}
