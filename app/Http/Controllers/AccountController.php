<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Account;
use App\Services\AccountService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(AccountService $service): View
    {
        $family = Fin::family();

        return view('pages.contas', [
            'contas' => $service->list($family),
        ]);
    }

    /** Extrato detalhado de uma conta específica. */
    public function show(Account $account, AccountService $service): View
    {
        abort_if($account->family_id !== Fin::familyId(), 404);

        return view('pages.accounts.extrato', [
            'conta' => $account->load('bank'),
            'movs' => $service->statementForAccount($account),
            'totais' => $service->totalsForAccount($account),
        ]);
    }

    public function create(AccountService $service): View
    {
        return view('pages.accounts.form', [
            'account' => null,
            'bancos' => $service->activeBanks(),
        ]);
    }

    public function edit(Account $account, AccountService $service): View
    {
        abort_if($account->family_id !== Fin::familyId(), 404);
        $this->authorize('manage', $account);

        return view('pages.accounts.form', [
            'account' => $account,
            'bancos' => $service->activeBanks(),
        ]);
    }

    public function store(AccountRequest $request, AccountService $service): RedirectResponse
    {
        $family = Fin::family();
        $service->create($family, $request->validated());

        return redirect()->route('contas')->with('status', 'Conta criada.');
    }

    public function update(AccountRequest $request, Account $account, AccountService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($account->family_id !== $family->id, 404);
        $this->authorize('manage', $account);

        $service->update($account, $request->validated(), $request->boolean('active'));

        return redirect()->route('contas')->with('status', 'Conta atualizada.');
    }

    public function destroy(Account $account, AccountService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($account->family_id !== $family->id, 404);
        $this->authorize('manage', $account);
        $service->destroy($account);

        return redirect()->route('contas')->with('status', 'Conta excluída.');
    }

    /** Transferência interna entre contas da família. */
    public function createTransfer(AccountService $service): View
    {
        return view('pages.accounts.transfer-form', $service->transferOptions(Fin::family()));
    }

    /** Transferência interna entre contas da família. */
    public function transfer(TransferRequest $request, AccountService $service): RedirectResponse
    {
        $family = Fin::family();
        $service->transfer($family, $request->validated());

        return redirect()->route('contas')->with('status', 'Transferência registrada.');
    }
}
