<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardItemRequest;
use App\Http\Requests\CreditCardRequest;
use App\Http\Resources\CardTransactionResource;
use App\Http\Resources\CreditCardResource;
use App\Models\CardTransaction;
use App\Models\CreditCard;
use App\Services\CardService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Cards', description: 'Cartões de crédito e itens de fatura')]
class CardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/cards',
        summary: 'Lista cartões',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Cartões ativos')]
    )]
    public function index(CardService $service): AnonymousResourceCollection
    {
        return CreditCardResource::collection($service->list(Fin::family()));
    }

    #[OA\Post(
        path: '/api/v1/cards',
        summary: 'Cria cartão',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'credit_limit', 'closing_day', 'due_day'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Nubank'),
                    new OA\Property(property: 'credit_limit', type: 'string', example: '5000'),
                    new OA\Property(property: 'closing_day', type: 'integer', example: 1),
                    new OA\Property(property: 'due_day', type: 'integer', example: 10),
                    new OA\Property(property: 'holder_user_id', type: 'integer'),
                    new OA\Property(property: 'account_id', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Cartão criado')]
    )]
    public function store(CreditCardRequest $request, CardService $service): JsonResponse
    {
        $card = $service->createCard(Fin::family(), $request->validated());

        return (new CreditCardResource($card))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/cards/{card}',
        summary: 'Detalha cartão',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'card', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Cartão')]
    )]
    public function show(CreditCard $card): CreditCardResource
    {
        abort_unless($card->family_id === Fin::familyId(), 404);
        $card->load(['holder', 'account.bank']);

        return new CreditCardResource($card);
    }

    #[OA\Patch(
        path: '/api/v1/cards/{card}',
        summary: 'Atualiza cartão',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'card', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'credit_limit', type: 'string'),
                    new OA\Property(property: 'closing_day', type: 'integer'),
                    new OA\Property(property: 'due_day', type: 'integer'),
                    new OA\Property(property: 'active', type: 'boolean'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Cartão atualizado')]
    )]
    public function update(CreditCardRequest $request, CreditCard $card, CardService $service): CreditCardResource
    {
        abort_unless($card->family_id === Fin::familyId(), 404);
        $this->authorize('manage', $card);

        return new CreditCardResource($service->updateCard($card, Fin::family(), $request->validated(), $request->boolean('active')));
    }

    #[OA\Delete(
        path: '/api/v1/cards/{card}',
        summary: 'Exclui cartão',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'card', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Excluído')]
    )]
    public function destroy(CreditCard $card, CardService $service): JsonResponse
    {
        abort_unless($card->family_id === Fin::familyId(), 404);
        $this->authorize('manage', $card);
        $service->deleteCard($card);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/cards/items',
        summary: 'Lança compra/estorno',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['credit_card_id', 'description', 'amount', 'occurred_on', 'user_id'],
                properties: [
                    new OA\Property(property: 'credit_card_id', type: 'integer'),
                    new OA\Property(property: 'description', type: 'string', example: 'Mercado'),
                    new OA\Property(property: 'amount', type: 'string', example: '120,00'),
                    new OA\Property(property: 'occurred_on', type: 'string', format: 'date'),
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'kind', type: 'string', enum: ['compra', 'estorno']),
                    new OA\Property(property: 'installments_total', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Item criado')]
    )]
    public function storeItem(CardItemRequest $request, CardService $service): JsonResponse
    {
        ['items' => $items] = $service->createItem(Fin::family(), $request->validated());

        return (new CardTransactionResource($items->first()))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/v1/cards/items/{item}/settle',
        summary: 'Liquida item da fatura',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'item', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Item liquidado')]
    )]
    public function settleItem(CardTransaction $item, CardService $service): CardTransactionResource
    {
        abort_unless($item->family_id === Fin::familyId(), 404);

        return new CardTransactionResource($service->settleItem($item, Fin::family()));
    }

    #[OA\Post(
        path: '/api/v1/cards/{card}/pay-invoice',
        summary: 'Paga fatura do cartão',
        tags: ['Cards'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'card', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Fatura paga')]
    )]
    public function payInvoice(CreditCard $card, CardService $service): JsonResponse
    {
        abort_unless($card->family_id === Fin::familyId(), 404);
        $this->authorize('manage', $card);

        $total = $service->payInvoice($card, Fin::family(), request()->user()->id);
        if ($total === null) {
            return response()->json(['message' => 'Nenhum item pendente nesta fatura.'], 422);
        }

        return response()->json(['message' => 'Fatura paga.', 'total' => $total]);
    }
}
