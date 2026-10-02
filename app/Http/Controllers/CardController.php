<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardItemRequest;
use App\Http\Requests\CreditCardRequest;
use App\Models\CardTransaction;
use App\Models\CreditCard;
use App\Services\CardService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(Request $request, CardService $service): View
    {
        return view('pages.cartoes', $service->dashboard(Fin::family(), $request->query('mes')));
    }

    public function create(CardService $service): View
    {
        return view('pages.cards.form', [
            'card' => null,
            'membros' => Fin::family()->users()->orderBy('name')->get(),
            'contas' => $service->activeAccounts(Fin::family()),
        ]);
    }

    public function edit(CreditCard $card, CardService $service): View
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);

        return view('pages.cards.form', [
            'card' => $card->load('account.bank'),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $service->activeAccounts($family),
        ]);
    }

    public function store(CreditCardRequest $request, CardService $service): RedirectResponse
    {
        $family = Fin::family();
        $service->createCard($family, $request->validated());

        return redirect()->route('cartoes')->with('status', 'Cartão adicionado.');
    }

    public function update(CreditCardRequest $request, CreditCard $card, CardService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);

        $service->updateCard($card, $family, $request->validated(), $request->boolean('active'));

        return redirect()->route('cartoes')->with('status', 'Cartão atualizado.');
    }

    public function destroy(CreditCard $card, CardService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);
        $service->deleteCard($card);

        return redirect()->route('cartoes')->with('status', 'Cartão excluído.');
    }

    public function createItem(CardService $service): View
    {
        return view('pages.cards.item-form', $service->itemOptions(Fin::family()));
    }

    public function storeItem(CardItemRequest $request, CardService $service): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        ['parcelas' => $parcelas] = $service->createItem($family, $data);

        return redirect()->route('cartoes')->with('status',
            ($data['kind'] ?? 'compra') === 'estorno' ? 'Estorno lançado na fatura.'
                : ($parcelas > 1 ? "Compra parcelada em {$parcelas}x." : 'Compra lançada na fatura.'));
    }

    public function settleItem(CardTransaction $item, CardService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($item->family_id !== $family->id, 404);

        if ($item->status === 'pago') {
            return back()->with('status', 'Item já estava liquidado.');
        }

        $service->settleItem($item, $family);

        return back()->with('status', 'Item da fatura liquidado.');
    }

    /** Paga a fatura cheia: liquida todos os pendentes e gera a despesa na conta vinculada. */
    public function payInvoice(CreditCard $card, CardService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($card->family_id !== $family->id, 404);
        $this->authorize('manage', $card);

        $total = $service->payInvoice($card, $family, request()->user()->id);
        if ($total === null) {
            return back()->with('status', 'Nenhum item pendente nesta fatura.');
        }

        return back()->with('status', 'Fatura paga ('.Fin::money($total).').');
    }
}
