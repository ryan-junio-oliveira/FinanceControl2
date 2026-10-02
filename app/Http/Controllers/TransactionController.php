<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Attachment;
use App\Models\Transaction;
use App\Services\AccountService;
use App\Services\CardService;
use App\Services\TransactionService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    private function assertType(string $type): string
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);

        return $type;
    }

    public function index(Request $request, string $type, TransactionService $service): View
    {
        $type = $this->assertType($type);
        $family = Fin::family();
        $mes = Fin::month();

        $ledger = $service->list($family, $type, [
            'q' => $request->query('q'),
            'status' => $request->query('status'),
            'fixa' => $request->query('fixa'),
            'categoria' => $request->query('categoria'),
            'membro' => $request->query('membro'),
            'mes' => $mes,
        ])->withQueryString();

        $categorias = $family->categories()->where('type', $type)->where('archived', false)->orderBy('name')->get();
        $membros = $family->users()->orderBy('name')->get();
        $contas = $family->accounts()->where('active', true)->orderBy('name')->get();

        $view = $type === 'despesa' ? 'pages.despesas' : 'pages.receitas';

        return view($view, compact('mes', 'type', 'ledger', 'categorias', 'membros', 'contas'));
    }

    public function create(string $type): View
    {
        $type = $this->assertType($type);
        $family = Fin::family();

        return view('pages.transactions.form', [
            'type' => $type,
            'transaction' => null,
            'mes' => Fin::month(),
            'categorias' => $family->categories()->where('type', $type)->where('archived', false)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
            'cartoes' => $family->creditCards()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Transaction $transaction): View
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        abort_if(! in_array($transaction->type, ['despesa', 'receita'], true), 404);
        $this->authorize('update', $transaction);
        $transaction->load(['attachments', 'auditLogs.member']);

        return view('pages.transactions.form', [
            'type' => $transaction->type,
            'transaction' => $transaction,
            'mes' => Fin::month(),
            'categorias' => $family->categories()->where('type', $transaction->type)->where('archived', false)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
            'cartoes' => $family->creditCards()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(TransactionRequest $request, string $type, TransactionService $service): RedirectResponse
    {
        $type = $this->assertType($type);
        $family = Fin::family();
        $dados = $request->validated();

        // Cartão: lança direto na fatura do cartão escolhido (item pendente).
        if ($type === 'despesa' && ($dados['payment_method'] ?? null) === 'cartao') {
            $family->creditCards()->findOrFail($dados['credit_card_id']);
            $item = app(CardService::class)->createItem($family, [
                'credit_card_id' => $dados['credit_card_id'],
                'description' => $dados['description'],
                'amount' => $dados['amount'],
                'occurred_on' => $dados['occurred_on'],
                'user_id' => $dados['user_id'] ?? $request->user()->id,
                'category_id' => $dados['category_id'] ?? null,
                'installments_total' => $dados['installments_total'] ?? 1,
            ]);

            return redirect()->route('cartoes', ['mes' => Fin::month()])->with('status', $item['parcelas'] > 1 ? "Compra parcelada em {$item['parcelas']}x na fatura." : 'Compra lançada na fatura do cartão.');
        }

        // Dinheiro físico: sempre na conta "carteira" (não se mistura com o digital).
        if (($dados['payment_method'] ?? null) === 'dinheiro_fisico') {
            $dados['account_id'] = app(AccountService::class)->dinheiroFisico($family)->id;
        }

        // Dinheiro digital: exige conta digital/corrente (não-carteira).
        if (($dados['payment_method'] ?? null) === 'dinheiro_digital') {
            $conta = $family->accounts()->where('active', true)->where('kind', '!=', 'carteira')->find($dados['account_id'] ?? null)
                ?? $family->accounts()->where('active', true)->where('kind', '!=', 'carteira')->orderBy('name')->first();
            abort_if(! $conta, 422, 'Crie uma conta corrente/digital para lançar dinheiro digital.');
            $dados['account_id'] = $conta->id;
        }

        // Depósito: dinheiro físico (carteira) entra numa conta bancária.
        if ($type === 'receita' && ($dados['payment_method'] ?? null) === 'deposito') {
            $conta = $family->accounts()->where('active', true)->where('kind', '!=', 'carteira')->find($dados['account_id'] ?? null)
                ?? $family->accounts()->where('active', true)->where('kind', '!=', 'carteira')->orderBy('name')->first();
            abort_if(! $conta, 422, 'Escolha a conta bancária de destino do depósito.');
            app(AccountService::class)->transfer($family, [
                'from_account_id' => app(AccountService::class)->dinheiroFisico($family)->id,
                'to_account_id' => $conta->id,
                'user_id' => $dados['user_id'] ?? $request->user()->id,
                'amount' => $dados['amount'],
                'occurred_on' => $dados['occurred_on'],
                'description' => $dados['description'] ?: 'Depósito (dinheiro físico → conta)',
            ]);

            return redirect()->route('receitas', ['mes' => Fin::month()])->with('status', 'Depósito registrado.');
        }

        $criados = $service->create(
            $family,
            $type,
            $dados,
            $request->hasFile('anexo') ? $request->file('anexo') : null,
            $request->user()->id,
        );
        $parcelas = $criados->first()->installments_total ?? 1;

        $rota = $type === 'despesa' ? 'despesas' : 'receitas';

        return redirect()->route($rota, ['mes' => Fin::month()])->with('status', $parcelas > 1 ? "Lançamento parcelado em {$parcelas}x." : 'Lançamento registrado.');
    }

    public function update(TransactionRequest $request, Transaction $transaction, TransactionService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        abort_if(! in_array($transaction->type, ['despesa', 'receita'], true), 404);
        $this->authorize('update', $transaction);

        $service->update(
            $transaction,
            $family,
            $request->validated(),
            $request->hasFile('anexo') ? $request->file('anexo') : null,
            $request->user()->id,
        );

        $rota = $transaction->type === 'despesa' ? 'despesas' : 'receitas';

        return redirect()->route($rota, ['mes' => Fin::month()])->with('status', 'Lançamento atualizado.');
    }

    public function destroy(Transaction $transaction, TransactionService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        $this->authorize('delete', $transaction);
        $rota = in_array($transaction->type, ['despesa', 'receita'], true) ? $transaction->type.'s' : 'dashboard';
        $service->destroy($transaction);

        return redirect()->route($rota === 'dashboard' ? 'dashboard' : $rota)->with('status', 'Lançamento excluído.');
    }

    /** Marca como pago/recebido. */
    public function settle(Transaction $transaction, TransactionService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        $this->authorize('settle', $transaction);
        $service->settle($transaction);

        return back()->with('status', 'Lançamento liquidado.');
    }

    /** Baixa o anexo (PDF/imagem do comprovante). */
    public function downloadAttachment(Attachment $attachment): BinaryFileResponse|StreamedResponse
    {
        abort_if($attachment->family_id !== Fin::familyId(), 404);
        abort_if(! Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    /** Remove o anexo (dono do envio ou gestor). */
    public function destroyAttachment(Attachment $attachment): RedirectResponse
    {
        $user = request()->user();
        abort_if($attachment->family_id !== Fin::familyId(), 404);
        abort_if($attachment->user_id !== $user->id && ! $user->isAdmin(), 403);
        $attachment->delete();

        return back()->with('status', 'Anexo removido.');
    }
}
