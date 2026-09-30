<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Http\Requests\ContributionRequest;
use App\Http\Requests\PortfolioRequest;
use App\Models\Asset;
use App\Models\Transaction;
use App\Support\Fin;
use App\Support\MarketData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $mes = Fin::month();
        $fid = $family->id;

        $portfolios = $family->portfolios()->with('assets')->withSum('assets as total', 'current_value')->get();

        $ativos = Asset::where('family_id', $fid)->with('portfolio')->orderBy('name')->paginate(12);
        $porClasse = Asset::where('family_id', $fid)->selectRaw('kind, SUM(current_value) as total')->groupBy('kind')->pluck('total', 'kind');

        $contas = $family->accounts()->where('active', true)->orderBy('name')->get();

        $market = MarketData::snapshot();

        return view('pages.investimentos', compact(
            'mes', 'portfolios', 'ativos', 'porClasse', 'contas', 'market'
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
            'ativos' => Asset::where('family_id', $family->id)->with('portfolio')->orderBy('name')->get(),
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

    /** Novo aporte: cria contribuição + saída da conta (tipo aporte). Rendimento atualiza o ativo. */
    public function storeContribution(ContributionRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        DB::transaction(function () use ($family, $data, $request) {
            $portfolio = $family->portfolios()->findOrFail($data['portfolio_id']);
            $asset = null;
            if (! empty($data['asset_id'])) {
                $asset = Asset::where('family_id', $family->id)->findOrFail($data['asset_id']);
                abort_if($asset->portfolio_id !== $portfolio->id, 422, 'O ativo não pertence a esta carteira.');
            }

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
                'asset_id' => $asset?->id,
                'account_id' => $data['kind'] === 'aporte' ? $data['account_id'] : null,
                'kind' => $data['kind'],
                'amount' => $data['amount'],
                'occurred_on' => $data['occurred_on'],
                'note' => $data['note'] ?? null,
            ]);

            // Patrimônio anda junto: aporte alocado e todo rendimento movem o ativo.
            if ($asset) {
                $asset->increment('current_value', (float) $data['amount']);
            }
        });

        return redirect()->route('investimentos')->with('status', $data['kind'] === 'aporte' ? 'Aporte registrado.' : 'Rendimento registrado.');
    }

    public function destroyAsset(Asset $ativo): RedirectResponse
    {
        abort_if($ativo->family_id !== Fin::familyId(), 404);
        $ativo->delete();

        return redirect()->route('investimentos')->with('status', 'Ativo removido.');
    }
}
