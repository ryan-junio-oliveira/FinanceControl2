<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Http\Requests\ContributionRequest;
use App\Http\Requests\PortfolioRequest;
use App\Models\Asset;
use App\Models\Contribution;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $mes = Fin::month();
        $fid = $family->id;

        $portfolios = $family->portfolios()->with('assets')->get();
        $patrimonio = $portfolios->sum(fn ($p) => $p->total);

        $aportesMes = Contribution::where('family_id', $fid)->where('kind', 'aporte')
            ->whereYear('occurred_on', substr($mes, 0, 4))->whereMonth('occurred_on', substr($mes, 5, 2))->sum('amount');
        $rendimentos = Contribution::where('family_id', $fid)->where('kind', 'rendimento')
            ->whereYear('occurred_on', substr($mes, 0, 4))->whereMonth('occurred_on', substr($mes, 5, 2))->sum('amount');

        $reserva = $portfolios->where('kind', 'reserva');
        $reservaTotal = $reserva->sum(fn ($p) => $p->total);
        $reservaMeta = (float) $reserva->sum('target_amount');

        $ativos = Asset::where('family_id', $fid)->with('portfolio')->orderBy('name')->paginate(12);
        $porClasse = Asset::where('family_id', $fid)->selectRaw('kind, SUM(current_value) as total')->groupBy('kind')->pluck('total', 'kind');

        $contas = $family->accounts()->where('active', true)->orderBy('name')->get();

        return view('pages.investimentos', compact(
            'mes', 'portfolios', 'patrimonio', 'aportesMes', 'rendimentos',
            'reservaTotal', 'reservaMeta', 'ativos', 'porClasse', 'contas'
        ));
    }

    public function createPortfolio(): View
    {
        return view('pages.investments.portfolio-form', ['portfolio' => null]);
    }

    public function createAsset(): View
    {
        $family = Fin::family();

        return view('pages.investments.asset-form', [
            'portfolios' => $family->portfolios()->orderBy('name')->get(),
            'selected' => request()->query('carteira'),
        ]);
    }

    public function createContribution(): View
    {
        $family = Fin::family();

        return view('pages.investments.contribution-form', [
            'portfolios' => $family->portfolios()->orderBy('name')->get(),
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
            'selected' => request()->query('carteira'),
        ]);
    }

    public function storePortfolio(PortfolioRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();
        $family->portfolios()->create($data);

        return redirect()->route('investimentos')->with('status', 'Carteira criada.');
    }

    public function storeAsset(AssetRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();
        $family->portfolios()->findOrFail($data['portfolio_id']);
        Asset::create($data + ['family_id' => $family->id]);

        return redirect()->route('investimentos')->with('status', 'Ativo adicionado.');
    }

    /** Novo aporte: cria contribuição + saída da conta (tipo aporte). */
    public function storeContribution(ContributionRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $portfolio = $family->portfolios()->findOrFail($data['portfolio_id']);

        if ($data['kind'] === 'aporte') {
            $account = $family->accounts()->findOrFail($data['account_id']);
            Transaction::create([
                'family_id' => $family->id,
                'user_id' => $request->user()->id,
                'account_id' => $account->id,
                'portfolio_id' => $portfolio->id,
                'type' => 'aporte',
                'description' => $data['note'] ?? "Aporte — {$portfolio->name}",
                'amount' => $data['amount'],
                'occurred_on' => $data['occurred_on'],
                'status' => 'pago',
            ]);
        }

        $portfolio->contributions()->create([
            'family_id' => $family->id,
            'account_id' => $data['kind'] === 'aporte' ? $data['account_id'] : null,
            'kind' => $data['kind'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('investimentos')->with('status', $data['kind'] === 'aporte' ? 'Aporte registrado.' : 'Rendimento registrado.');
    }

    public function destroyAsset(Asset $ativo): RedirectResponse
    {
        abort_if($ativo->family_id !== Fin::familyId(), 404);
        $ativo->delete();

        return redirect()->route('investimentos')->with('status', 'Ativo removido.');
    }
}
