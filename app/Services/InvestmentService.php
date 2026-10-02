<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Contribution;
use App\Models\Family;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Support\Fin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Regras de domínio dos investimentos (carteiras, ativos, aportes).
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class InvestmentService
{
    /** Dados completos da tela de investimentos. */
    public function dashboard(Family $family, array $filters = [], int $perPage = 12, ?string $mes = null): array
    {
        $mes ??= Fin::month();
        [$ano, $m] = array_map('intval', explode('-', $mes));

        $patrimonio = (float) Asset::where('family_id', $family->id)->sum('current_value');

        $metas = $family->portfolios()->whereNotNull('target_amount')
            ->withSum('assets as total', 'current_value')
            ->orderBy('deadline')->orderBy('name')->get();

        $ativosQuery = Asset::where('family_id', $family->id)->with('portfolio');
        if (! empty($filters['q'])) {
            $q = '%'.$filters['q'].'%';
            $ativosQuery->where(fn ($w) => $w->where('name', 'like', $q)->orWhere('code', 'like', $q));
        }
        $ativos = $ativosQuery->orderBy('name')->paginate($perPage);
        $porClasse = Asset::where('family_id', $family->id)->selectRaw('kind, SUM(current_value) as total')->groupBy('kind')->pluck('total', 'kind');

        $mesQuery = fn ($q) => $q->whereYear('occurred_on', $ano)->whereMonth('occurred_on', $m);
        $aportesMes = (float) $mesQuery(Contribution::where('family_id', $family->id)->where('kind', 'aporte'))->sum('amount');
        $rendMes = (float) $mesQuery(Contribution::where('family_id', $family->id)->where('kind', 'rendimento'))->sum('amount');

        return [
            'mes' => $mes,
            'patrimonio' => $patrimonio,
            'aportesMes' => $aportesMes,
            'rendMes' => $rendMes,
            'metas' => $metas,
            'ativos' => $ativos,
            'porClasse' => $porClasse,
            'contas' => $family->accounts()->where('active', true)->orderBy('name')->get(),
        ];
    }

    /** Ativos da família (para API). */
    public function listAssets(Family $family, array $filters = []): LengthAwarePaginator
    {
        $q = Asset::where('family_id', $family->id)->with('portfolio');
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('code', 'like', $term));
        }

        return $q->orderBy('name')->paginate(12);
    }

    public function createPortfolio(Family $family, array $data): Portfolio
    {
        return $family->portfolios()->create($data);
    }

    public function createAsset(Family $family, array $data): Asset
    {
        if (! empty($data['portfolio_id'])) {
            $family->portfolios()->findOrFail($data['portfolio_id']);
        }

        return Asset::create($data + ['family_id' => $family->id]);
    }

    public function updateAsset(Asset $asset, Family $family, array $data): Asset
    {
        if (! empty($data['portfolio_id'])) {
            $family->portfolios()->findOrFail($data['portfolio_id']);
        }
        $asset->update($data);

        return $asset->refresh();
    }

    public function destroyAsset(Asset $asset): void
    {
        $asset->delete();
    }

    /** Novo aporte: cria contribuição + saída da conta (tipo aporte). Rendimento atualiza o ativo. */
    public function createContribution(Family $family, array $data, int $actorId): void
    {
        DB::transaction(function () use ($family, $data, $actorId) {
            $asset = null;
            if (! empty($data['asset_id'])) {
                $asset = Asset::where('family_id', $family->id)->findOrFail($data['asset_id']);
            }
            // Carteira opcional: usa a informada, a do ativo ou a "Geral" da família.
            $portfolio = null;
            if (! empty($data['portfolio_id'])) {
                $portfolio = $family->portfolios()->findOrFail($data['portfolio_id']);
            } elseif ($asset?->portfolio_id) {
                $portfolio = $family->portfolios()->findOrFail($asset->portfolio_id);
            } else {
                $portfolio = $family->portfolios()->firstOrCreate(
                    ['name' => 'Geral'],
                    ['kind' => 'livre', 'objective' => 'Carteira automática da família']
                );
            }
            if ($asset && $asset->portfolio_id && $asset->portfolio_id !== $portfolio->id) {
                abort(422, 'O ativo não pertence a esta carteira.');
            }

            if ($data['kind'] === 'aporte') {
                $account = $family->accounts()->findOrFail($data['account_id']);
                Transaction::create([
                    'family_id' => $family->id,
                    'user_id' => $actorId,
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
    }
}
