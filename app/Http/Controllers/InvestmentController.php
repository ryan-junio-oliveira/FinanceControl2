<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetRequest;
use App\Http\Requests\ContributionRequest;
use App\Models\Asset;
use App\Services\InvestmentService;
use App\Support\Fin;
use App\Support\MarketData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    public function index(Request $request, InvestmentService $service): View
    {
        return view('pages.investimentos', $service->dashboard(
            Fin::family(),
            ['q' => $request->query('q')],
            (int) $request->query('per_page', 12),
            $request->query('mes'),
        ));
    }

    /** Snapshot de mercado em JSON (a view busca via fetch com skeleton). */
    public function market(): JsonResponse
    {
        return response()->json(MarketData::snapshot());
    }

    public function createAsset(): View
    {
        $family = Fin::family();

        return view('pages.investments.asset-form', [
            'asset' => null,
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

    public function storeAsset(AssetRequest $request, InvestmentService $service): RedirectResponse
    {
        $family = Fin::family();
        $service->createAsset($family, $request->validated());

        return redirect()->route('investimentos')->with('status', 'Ativo adicionado.');
    }

    /** Novo aporte: cria contribuição + saída da conta (tipo aporte). Rendimento atualiza o ativo. */
    public function storeContribution(ContributionRequest $request, InvestmentService $service): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        $service->createContribution($family, $data, $request->user()->id);

        return redirect()->route('investimentos')->with('status', $data['kind'] === 'aporte' ? 'Aporte registrado.' : 'Rendimento registrado.');
    }

    public function editAsset(Asset $asset): View
    {
        abort_if($asset->family_id !== Fin::familyId(), 404);
        $family = Fin::family();

        return view('pages.investments.asset-form', [
            'asset' => $asset,
            'portfolios' => $family->portfolios()->orderBy('name')->get(),
            'selected' => $asset->portfolio_id,
        ]);
    }

    /** Corrige o cadastro do ativo (valor inicial errado, rentabilidade, etc.). */
    public function updateAsset(AssetRequest $request, Asset $asset, InvestmentService $service): RedirectResponse
    {
        abort_if($asset->family_id !== Fin::familyId(), 404);
        $family = Fin::family();
        $service->updateAsset($asset, $family, $request->validated());

        return redirect()->route('investimentos')->with('status', 'Ativo atualizado.');
    }

    public function destroyAsset(Asset $asset, InvestmentService $service): RedirectResponse
    {
        abort_if($asset->family_id !== Fin::familyId(), 404);
        $service->destroyAsset($asset);

        return redirect()->route('investimentos')->with('status', 'Ativo removido.');
    }
}
