<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $fid = $family->id;

        $contas = $family->accounts()->with('bank')->orderBy('name')->get();
        $balances = Account::balancesForFamily($fid);
        $contas->each(function ($a) use ($balances) {
            // Expõe saldo pré-calculado sem N+1; getBalanceAttribute segue disponível.
            $a->setAttribute('balance_cached', $balances[$a->id] ?? (float) $a->initial_balance);
        });

        $extrato = Transaction::ofFamily($fid)
            ->with(['member', 'category', 'account'])
            ->orderByDesc('occurred_on')->orderByDesc('id')
            ->paginate(15);

        return view('pages.contas', compact('contas', 'extrato'));
    }

    public function create(): View
    {
        return view('pages.accounts.form', [
            'account' => null,
            'bancos' => \App\Models\Bank::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Account $account): View
    {
        abort_if($account->family_id !== Fin::familyId(), 404);
        $this->authorize('manage', $account);

        return view('pages.accounts.form', [
            'account' => $account,
            'bancos' => \App\Models\Bank::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $family->accounts()->create($data);

        return redirect()->route('contas')->with('status', 'Conta criada.');
    }

    public function update(AccountRequest $request, Account $account): RedirectResponse
    {
        $family = Fin::family();
        abort_if($account->family_id !== $family->id, 404);
        $this->authorize('manage', $account);

        $account->update($request->validated() + ['active' => $request->boolean('active')]);

        return redirect()->route('contas')->with('status', 'Conta atualizada.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $family = Fin::family();
        abort_if($account->family_id !== $family->id, 404);
        $this->authorize('manage', $account);
        abort_if($account->transactions()->exists(), 422, 'Conta com movimentações não pode ser excluída. Desative-a.');
        $account->delete();

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

        DB::transaction(function () use ($family, $data) {
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
        });

        return redirect()->route('contas')->with('status', 'Transferência registrada.');
    }
}
