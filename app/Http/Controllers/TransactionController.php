<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    private function assertType(string $type): string
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);

        return $type;
    }

    public function index(Request $request, string $type): View
    {
        $type = $this->assertType($type);
        $family = Fin::family();
        $mes = Fin::month();
        $fid = $family->id;

        $q = Transaction::ofFamily($fid)->where('type', $type)->with(['member', 'category', 'account']);

        if ($request->filled('q')) {
            $q->where('description', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('status') && in_array($request->status, ['pago', 'pendente', 'agendado'], true)) {
            $q->where('status', $request->status);
        }
        if ($request->filled('fixa') && in_array($request->fixa, ['0', '1'], true)) {
            $q->where('is_fixed', $request->fixa);
        }
        if ($request->filled('categoria')) {
            $q->where('category_id', $request->categoria);
        }
        if ($request->filled('membro')) {
            $q->where('user_id', $request->membro);
        }

        $ledger = $q->inMonth($mes)->orderByDesc('occurred_on')->orderByDesc('id')->paginate(15)->withQueryString();

        $sumBase = Transaction::ofFamily($fid)->where('type', $type)->inMonth($mes);
        $total = (float) (clone $sumBase)->whereIn('status', ['pago', 'pendente'])->sum('amount');
        $fixas = (float) (clone $sumBase)->where('is_fixed', true)->whereIn('status', ['pago', 'pendente'])->sum('amount');
        $variaveis = $total - $fixas;
        $aVencer = (float) (clone $sumBase)->where('status', 'pendente')->sum('amount');

        $categorias = $family->categories()->where('type', $type)->where('archived', false)->orderBy('name')->get();
        $membros = $family->users()->orderBy('name')->get();
        $contas = $family->accounts()->where('active', true)->orderBy('name')->get();

        $view = $type === 'despesa' ? 'pages.despesas' : 'pages.receitas';

        return view($view, compact('mes', 'type', 'ledger', 'total', 'fixas', 'variaveis', 'aVencer', 'categorias', 'membros', 'contas'));
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
        ]);
    }

    public function edit(Transaction $transaction): View
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        abort_if(! in_array($transaction->type, ['despesa', 'receita'], true), 404);

        return view('pages.transactions.form', [
            'type' => $transaction->type,
            'transaction' => $transaction,
            'mes' => Fin::month(),
            'categorias' => $family->categories()->where('type', $transaction->type)->where('archived', false)->orderBy('name')->get(),
            'membros' => $family->users()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(TransactionRequest $request, string $type): RedirectResponse
    {
        $type = $this->assertType($type);
        $family = Fin::family();

        $data = $request->validated();

        $member = $family->users()->findOrFail($data['user_id']);
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        if (! empty($data['category_id'])) {
            $cat = $family->categories()->findOrFail($data['category_id']);
            abort_if($cat->type !== $type, 422, 'Categoria de outro tipo.');
            $data['subcategory_id'] = null;
        }

        // Dependentes/júnior acima do limiar exigem aprovação (registra como pendente)
        $threshold = (float) $family->setting()->approval_threshold;
        if (! $member->isAdmin() && (float) $data['amount'] > $threshold && $type === 'despesa') {
            $data['status'] = 'pendente';
        }

        Transaction::create($data + [
            'family_id' => $family->id,
            'type' => $type,
            'is_fixed' => $request->boolean('is_fixed'),
        ]);

        $rota = $type === 'despesa' ? 'despesas' : 'receitas';

        return redirect()->route($rota, ['mes' => Fin::month()])->with('status', 'Lançamento registrado.');
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        abort_if(! in_array($transaction->type, ['despesa', 'receita'], true), 404);

        $data = $request->validated();

        $family->users()->findOrFail($data['user_id']);
        if (! empty($data['category_id'])) {
            $cat = $family->categories()->findOrFail($data['category_id']);
            abort_if($cat->type !== $transaction->type, 422, 'Categoria de outro tipo.');
        }
        $data['is_fixed'] = $request->boolean('is_fixed');

        $transaction->update($data);

        $rota = $transaction->type === 'despesa' ? 'despesas' : 'receitas';

        return redirect()->route($rota, ['mes' => Fin::month()])->with('status', 'Lançamento atualizado.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        $rota = in_array($transaction->type, ['despesa', 'receita'], true) ? $transaction->type.'s' : 'dashboard';
        $transaction->delete();

        return redirect()->route($rota === 'dashboard' ? 'dashboard' : $rota)->with('status', 'Lançamento excluído.');
    }

    /** Marca como pago/recebido. */
    public function settle(Transaction $transaction): RedirectResponse
    {
        $family = Fin::family();
        abort_if($transaction->family_id !== $family->id, 404);
        $transaction->update(['status' => 'pago', 'due_on' => $transaction->due_on ?? $transaction->occurred_on]);

        return back()->with('status', 'Lançamento liquidado.');
    }
}
