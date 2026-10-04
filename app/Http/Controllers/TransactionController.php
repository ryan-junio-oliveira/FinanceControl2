<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Attachment;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $group = Fin::group();
        $mes = Fin::month();

        $ledger = $service->list($group, $type, [
            'q' => $request->query('q'),
            'status' => $request->query('status'),
            'fixa' => $request->query('fixa'),
            'categoria' => $request->query('categoria'),
            'membro' => $request->query('membro'),
            'mes' => $mes,
        ])->withQueryString();

        $categorias = $group->categories()->where('type', $type)->where('archived', false)->orderBy('name')->get();
        $membros = $group->users()->orderBy('name')->get();
        $contas = $group->accounts()->where('active', true)->orderBy('name')->get();

        $view = $type === 'despesa' ? 'pages.despesas' : 'pages.receitas';

        return view($view, compact('mes', 'type', 'ledger', 'categorias', 'membros', 'contas'));
    }

    public function create(string $type): View
    {
        $type = $this->assertType($type);
        $group = Fin::group();

        return view('pages.transactions.form', [
            'type' => $type,
            'transaction' => null,
            'mes' => Fin::month(),
            'categorias' => $group->categories()->where('type', $type)->where('archived', false)->orderBy('name')->get(),
            'membros' => $group->users()->orderBy('name')->get(),
            'contas' => $group->accounts()->where('active', true)->orderBy('name')->get(),
            'cartoes' => $group->creditCards()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Transaction $transaction): View
    {
        $group = Fin::group();
        abort_if($transaction->group_id !== $group->id, 404);
        abort_if(! in_array($transaction->type, ['despesa', 'receita'], true), 404);
        $this->authorize('update', $transaction);
        $transaction->load(['attachments', 'auditLogs.member']);

        return view('pages.transactions.form', [
            'type' => $transaction->type,
            'transaction' => $transaction,
            'mes' => Fin::month(),
            'categorias' => $group->categories()->where('type', $transaction->type)->where('archived', false)->orderBy('name')->get(),
            'membros' => $group->users()->orderBy('name')->get(),
            'contas' => $group->accounts()->where('active', true)->orderBy('name')->get(),
            'cartoes' => $group->creditCards()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(TransactionRequest $request, string $type, TransactionService $service): RedirectResponse
    {
        $type = $this->assertType($type);
        $group = Fin::group();

        $result = $service->createForGroup(
            $group,
            $type,
            $request->validated(),
            $request->hasFile('anexo') ? $request->file('anexo') : null,
            $request->user()->id,
        );

        if ($result['kind'] === 'card') {
            return redirect()->route('cartoes', ['mes' => Fin::month()])->with('status', $result['message']);
        }
        if ($result['kind'] === 'deposit') {
            return redirect()->route('receitas', ['mes' => Fin::month()])->with('status', $result['message']);
        }

        $rota = $type === 'despesa' ? 'despesas' : 'receitas';

        return redirect()->route($rota, ['mes' => Fin::month()])->with('status', $result['message']);
    }

    public function update(TransactionRequest $request, Transaction $transaction, TransactionService $service): RedirectResponse
    {
        $group = Fin::group();
        abort_if($transaction->group_id !== $group->id, 404);
        abort_if(! in_array($transaction->type, ['despesa', 'receita'], true), 404);
        $this->authorize('update', $transaction);

        $service->update(
            $transaction,
            $group,
            $request->validated(),
            $request->hasFile('anexo') ? $request->file('anexo') : null,
            $request->user()->id,
        );

        $rota = $transaction->type === 'despesa' ? 'despesas' : 'receitas';

        return redirect()->route($rota, ['mes' => Fin::month()])->with('status', 'Lançamento atualizado.');
    }

    public function destroy(Transaction $transaction, TransactionService $service): RedirectResponse
    {
        $group = Fin::group();
        abort_if($transaction->group_id !== $group->id, 404);
        $this->authorize('delete', $transaction);
        $rota = in_array($transaction->type, ['despesa', 'receita'], true) ? $transaction->type.'s' : 'dashboard';
        $service->destroy($transaction);

        return redirect()->route($rota === 'dashboard' ? 'dashboard' : $rota)->with('status', 'Lançamento excluído.');
    }

    /** Marca como pago/recebido. */
    public function settle(Transaction $transaction, TransactionService $service): RedirectResponse
    {
        $group = Fin::group();
        abort_if($transaction->group_id !== $group->id, 404);
        $this->authorize('settle', $transaction);
        $service->settle($transaction);

        return back()->with('status', 'Lançamento liquidado.');
    }

    /** Baixa o anexo (PDF/imagem do comprovante). */
    public function downloadAttachment(Attachment $attachment): BinaryFileResponse|StreamedResponse
    {
        abort_if($attachment->group_id !== Fin::groupId(), 404);
        abort_if(! Storage::disk('local')->exists($attachment->path), 404);

        // Nome original vem do usuário: higieniza para o Content-Disposition.
        $safe = Str::ascii($attachment->original_name);
        $safe = trim(preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $safe) ?? 'anexo', '._');
        if ($safe === '') {
            $safe = 'anexo';
        }

        return Storage::disk('local')->download($attachment->path, $safe);
    }

    /** Remove o anexo (dono do envio ou gestor). */
    public function destroyAttachment(Attachment $attachment): RedirectResponse
    {
        $user = request()->user();
        abort_if($attachment->group_id !== Fin::groupId(), 404);
        abort_if($attachment->user_id !== $user->id && ! $user->isAdmin(), 403);
        $attachment->delete();

        return back()->with('status', 'Anexo removido.');
    }
}
