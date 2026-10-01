<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRequest;
use App\Http\Requests\ContributionRequest;
use App\Http\Requests\PortfolioRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\PortfolioResource;
use App\Models\Asset;
use App\Services\InvestmentService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Investments', description: 'Carteiras, ativos e aportes')]
class InvestmentController extends Controller
{
    #[OA\Get(
        path: '/api/v1/portfolios',
        summary: 'Lista carteiras',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Carteiras com progresso')]
    )]
    public function portfolios(): AnonymousResourceCollection
    {
        return PortfolioResource::collection(Fin::family()->portfolios()->orderBy('name')->get());
    }

    #[OA\Post(
        path: '/api/v1/portfolios',
        summary: 'Cria carteira',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'kind'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Reserva'),
                    new OA\Property(property: 'kind', type: 'string', enum: ['reserva', 'estudos', 'futuro', 'livre']),
                    new OA\Property(property: 'target_amount', type: 'string', example: '30000'),
                    new OA\Property(property: 'deadline', type: 'string', format: 'date'),
                    new OA\Property(property: 'objective', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Carteira criada')]
    )]
    public function storePortfolio(PortfolioRequest $request, InvestmentService $service): JsonResponse
    {
        $portfolio = $service->createPortfolio(Fin::family(), $request->validated());

        return (new PortfolioResource($portfolio))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/assets',
        summary: 'Lista ativos',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Ativos')]
    )]
    public function assets(Request $request, InvestmentService $service): AnonymousResourceCollection
    {
        return AssetResource::collection($service->listAssets(Fin::family(), [
            'q' => $request->query('q'),
        ]));
    }

    #[OA\Post(
        path: '/api/v1/assets',
        summary: 'Cria ativo',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'kind', 'current_value'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'CDB'),
                    new OA\Property(property: 'name', type: 'string', example: 'CDB Inter'),
                    new OA\Property(property: 'kind', type: 'string', enum: ['renda_fixa', 'fii', 'acao', 'etf', 'previdencia']),
                    new OA\Property(property: 'current_value', type: 'string', example: '10000'),
                    new OA\Property(property: 'institution', type: 'string'),
                    new OA\Property(property: 'yield_percent', type: 'string', example: '120'),
                    new OA\Property(property: 'yield_base', type: 'string', enum: ['cdi', 'selic', 'ipca', 'prefixado']),
                    new OA\Property(property: 'portfolio_id', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Ativo criado')]
    )]
    public function storeAsset(AssetRequest $request, InvestmentService $service): JsonResponse
    {
        $asset = $service->createAsset(Fin::family(), $request->validated());

        return (new AssetResource($asset))->response()->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/v1/assets/{asset}',
        summary: 'Atualiza ativo',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'asset', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'current_value', type: 'string'),
                    new OA\Property(property: 'yield_percent', type: 'string'),
                    new OA\Property(property: 'portfolio_id', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Ativo atualizado')]
    )]
    public function updateAsset(AssetRequest $request, Asset $asset, InvestmentService $service): AssetResource
    {
        abort_unless($asset->family_id === Fin::familyId(), 404);

        return new AssetResource($service->updateAsset($asset, Fin::family(), $request->validated()));
    }

    #[OA\Delete(
        path: '/api/v1/assets/{asset}',
        summary: 'Exclui ativo',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'asset', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Excluído')]
    )]
    public function destroyAsset(Asset $asset, InvestmentService $service): JsonResponse
    {
        abort_unless($asset->family_id === Fin::familyId(), 404);
        $service->destroyAsset($asset);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/contributions',
        summary: 'Registra aporte/rendimento',
        tags: ['Investments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kind', 'amount', 'occurred_on'],
                properties: [
                    new OA\Property(property: 'kind', type: 'string', enum: ['aporte', 'rendimento']),
                    new OA\Property(property: 'amount', type: 'string', example: '1000'),
                    new OA\Property(property: 'occurred_on', type: 'string', format: 'date'),
                    new OA\Property(property: 'asset_id', type: 'integer'),
                    new OA\Property(property: 'account_id', type: 'integer'),
                    new OA\Property(property: 'portfolio_id', type: 'integer'),
                    new OA\Property(property: 'note', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Registrado')]
    )]
    public function storeContribution(ContributionRequest $request, InvestmentService $service): JsonResponse
    {
        $data = $request->validated();
        $service->createContribution(Fin::family(), $data, $request->user()->id);

        return response()->json(['message' => $data['kind'] === 'aporte' ? 'Aporte registrado.' : 'Rendimento registrado.'], 201);
    }
}
