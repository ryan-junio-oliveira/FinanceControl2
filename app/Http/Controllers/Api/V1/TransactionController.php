<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Transactions', description: 'Lançamentos de despesas e receitas')]
class TransactionController extends Controller
{
    #[OA\Get(
        path: '/api/v1/transactions/{type}',
        summary: 'Lista lançamentos',
        description: 'Lista paginada de despesas ou receitas do mês de referência.',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pago', 'pendente', 'agendado'])),
            new OA\Parameter(name: 'categoria', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'membro', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'mes', in: 'query', schema: new OA\Schema(type: 'string', example: '2026-10')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de lançamentos')]
    )]
    public function index(Request $request, string $type, TransactionService $service): AnonymousResourceCollection
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);

        return TransactionResource::collection(
            $service->list(Fin::group(), $type, [
                'q' => $request->query('q'),
                'status' => $request->query('status'),
                'fixa' => $request->query('fixa'),
                'categoria' => $request->query('categoria'),
                'membro' => $request->query('membro'),
                'mes' => Fin::month(),
            ], (int) $request->query('per_page', 15))
        );
    }

    #[OA\Post(
        path: '/api/v1/transactions/{type}',
        summary: 'Cria lançamento',
        description: 'Cria um lançamento (ou parcelas) de despesa/receita.',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['description', 'amount', 'occurred_on', 'status', 'user_id'],
                properties: [
                    new OA\Property(property: 'description', type: 'string', example: 'Mercado'),
                    new OA\Property(property: 'amount', type: 'string', example: '150,50'),
                    new OA\Property(property: 'occurred_on', type: 'string', format: 'date'),
                    new OA\Property(property: 'due_on', type: 'string', format: 'date'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pago', 'pendente', 'agendado']),
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'account_id', type: 'integer'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'is_fixed', type: 'boolean'),
                    new OA\Property(property: 'installments_total', type: 'integer', example: 1),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Lançamento criado')]
    )]
    public function store(TransactionRequest $request, string $type, TransactionService $service): JsonResponse
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);

        $result = $service->createForGroup(
            Fin::group(),
            $type,
            $request->validated(),
            $request->hasFile('anexo') ? $request->file('anexo') : null,
            $request->user()->id,
        );

        if ($result['kind'] === 'card') {
            /** @var array{items: array} $items */
            $items = $result['items'];

            return response()->json([
                'message' => $result['message'],
                'parcelas' => $items['parcelas'],
            ], 201);
        }

        return (new TransactionResource($result['transactions']->first()))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/transactions/{type}/{transaction}',
        summary: 'Detalha lançamento',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
            new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lançamento')]
    )]
    public function show(string $type, Transaction $transaction): TransactionResource
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);
        abort_unless($transaction->group_id === Fin::groupId() && $transaction->type === $type, 404);
        $transaction->load(['member', 'category', 'account']);

        return new TransactionResource($transaction);
    }

    #[OA\Patch(
        path: '/api/v1/transactions/{type}/{transaction}',
        summary: 'Atualiza lançamento',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
            new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'amount', type: 'string'),
                    new OA\Property(property: 'occurred_on', type: 'string', format: 'date'),
                    new OA\Property(property: 'status', type: 'string', enum: ['pago', 'pendente', 'agendado']),
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'account_id', type: 'integer'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Lançamento atualizado')]
    )]
    public function update(string $type, TransactionRequest $request, Transaction $transaction, TransactionService $service): TransactionResource
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);
        abort_unless($transaction->group_id === Fin::groupId() && $transaction->type === $type, 404);
        $this->authorize('update', $transaction);

        return new TransactionResource(
            $service->update($transaction, Fin::group(), $request->validated(), $request->hasFile('anexo') ? $request->file('anexo') : null, $request->user()->id)
        );
    }

    #[OA\Delete(
        path: '/api/v1/transactions/{type}/{transaction}',
        summary: 'Exclui lançamento',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
            new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Excluído')]
    )]
    public function destroy(string $type, Transaction $transaction, TransactionService $service): JsonResponse
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);
        abort_unless($transaction->group_id === Fin::groupId() && $transaction->type === $type, 404);
        $this->authorize('delete', $transaction);
        $service->destroy($transaction);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/transactions/{type}/{transaction}/settle',
        summary: 'Marca como pago/recebido',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
            new OA\Parameter(name: 'transaction', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liquidado')]
    )]
    public function settle(string $type, Transaction $transaction, TransactionService $service): TransactionResource
    {
        abort_unless(in_array($type, ['despesa', 'receita'], true), 404);
        abort_unless($transaction->group_id === Fin::groupId() && $transaction->type === $type, 404);
        $this->authorize('settle', $transaction);

        return new TransactionResource($service->settle($transaction));
    }
}
