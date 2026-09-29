<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $fid = $family->id;

        $contas = $family->accounts()->orderBy('name')->get();
        $saldoTotal = $contas->sum(fn ($a) => $a->balance);

        $extrato = Transaction::ofFamily($fid)
            ->with(['member', 'category', 'account'])
            ->orderByDesc('occurred_on')->orderByDesc('id')
            ->paginate(15);

        return view('pages.contas', compact('contas', 'saldoTotal', 'extrato'));
    }

    public function create(): View
    {
        return view('pages.accounts.form', ['conta' => null]);
    }

    public function edit(Account $conta): View
    {
        abort_if($conta->family_id !== Fin::familyId(), 404);

        return view('pages.accounts.form', ['conta' => $conta]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $family->accounts()->create($data);

        return redirect()->route('contas')->with('status', 'Conta criada.');
    }

    public function update(AccountRequest $request, Account $conta): RedirectResponse
    {
        $family = Fin::family();
        abort_if($conta->family_id !== $family->id, 404);

        $conta->update($request->validated() + ['active' => $request->boolean('active')]);

        return redirect()->route('contas')->with('status', 'Conta atualizada.');
    }

    public function destroy(Account $conta): RedirectResponse
    {
        $family = Fin::family();
        abort_if($conta->family_id !== $family->id, 404);
        abort_if($conta->transactions()->exists(), 422, 'Conta com movimentações não pode ser excluída. Desative-a.');
        $conta->delete();

        return redirect()->route('contas')->with('status', 'Conta excluída.');
    }

    /** Transferência interna entre contas da família. */
    public function createTransfer(): View
    {
        $family = Fin::family();

        return view('pages.accounts.transfer-form', [
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
        ]);
    }

    /** Transferência interna entre contas da família. */
    public function transfer(TransferRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $from = $family->accounts()->findOrFail($data['from_account_id']);
        $to = $family->accounts()->findOrFail($data['to_account_id']);
        $family->users()->findOrFail($data['user_id']);

        Transaction::create([
            'family_id' => $family->id,
            'user_id' => $data['user_id'],
            'account_id' => $from->id,
            'type' => 'transferencia',
            'description' => $data['description'] ?? "Transferência {$from->name} → {$to->name}",
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'status' => 'pago',
            'transfer_to_account_id' => $to->id,
        ]);

        return redirect()->route('contas')->with('status', 'Transferência registrada.');
    }
}
