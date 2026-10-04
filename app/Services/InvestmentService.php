<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Contribution;
use App\Models\Group;
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
    public function dashboard(Group $group, array $filters = [], int $perPage = 12, ?string $mes = null): array
    {
        $mes ??= Fin::month();
        [$ano, $m] = array_map('intval', explode('-', $mes));

        $patrimonio = (float) Asset::where('group_id', $group->id)->sum('current_value');

        $ativosQuery = Asset::where('group_id', $group->id)->with('portfolio');
        if (! empty($filters['q'])) {
            $q = '%'.$filters['q'].'%';
            $ativosQuery->where(fn ($w) => $w->where('name', 'like', $q)->orWhere('code', 'like', $q));
        }
        $ativos = $ativosQuery->orderBy('name')->paginate($perPage);

        $mesQuery = fn ($q) => $q->whereYear('occurred_on', $ano)->whereMonth('occurred_on', $m);
        $aportesMes = (float) $mesQuery(Contribution::where('group_id', $group->id)->where('kind', 'aporte'))->sum('amount');
        $rendMes = (float) $mesQuery(Contribution::where('group_id', $group->id)->where('kind', 'rendimento'))->sum('amount');

        return [
            'mes' => $mes,
            'patrimonio' => $patrimonio,
            'aportesMes' => $aportesMes,
            'rendMes' => $rendMes,
            'ativos' => $ativos,
            'contas' => $group->accounts()->where('active', true)->orderBy('name')->get(),
        ];
    }

    /** Ativos do grupo (para API). */
    public function listAssets(Group $group, array $filters = []): LengthAwarePaginator
    {
        $q = Asset::where('group_id', $group->id)->with('portfolio');
        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('code', 'like', $term));
        }

        return $q->orderBy('name')->paginate(12);
    }

    public function createAsset(Group $group, array $data): Asset
    {
        if (! empty($data['portfolio_id'])) {
            $group->portfolios()->findOrFail($data['portfolio_id']);
        }

        return Asset::create($data + ['group_id' => $group->id]);
    }

    public function updateAsset(Asset $asset, Group $group, array $data): Asset
    {
        if (! empty($data['portfolio_id'])) {
            $group->portfolios()->findOrFail($data['portfolio_id']);
        }
        $asset->update($data);

        return $asset->refresh();
    }

    public function destroyAsset(Asset $asset): void
    {
        $asset->delete();
    }

    /** Novo aporte: cria contribuição + saída da conta (tipo aporte). Rendimento atualiza o ativo. */
    public function createContribution(Group $group, array $data, int $actorId): void
    {
        DB::transaction(function () use ($group, $data, $actorId) {
            $asset = null;
            if (! empty($data['asset_id'])) {
                $asset = Asset::where('group_id', $group->id)->findOrFail($data['asset_id']);
            }
            // Carteira opcional: usa a informada, a do ativo ou a "Geral" do grupo.
            $portfolio = null;
            if (! empty($data['portfolio_id'])) {
                $portfolio = $group->portfolios()->findOrFail($data['portfolio_id']);
            } elseif ($asset?->portfolio_id) {
                $portfolio = $group->portfolios()->findOrFail($asset->portfolio_id);
            } else {
                $portfolio = $group->portfolios()->firstOrCreate(
                    ['name' => 'Geral'],
                    ['kind' => 'livre', 'objective' => 'Carteira automática do grupo']
                );
            }
            if ($asset && $asset->portfolio_id && $asset->portfolio_id !== $portfolio->id) {
                abort(422, 'O ativo não pertence a esta carteira.');
            }

            if ($data['kind'] === 'aporte') {
                $account = $group->accounts()->findOrFail($data['account_id']);
                Transaction::create([
                    'group_id' => $group->id,
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
                'group_id' => $group->id,
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
