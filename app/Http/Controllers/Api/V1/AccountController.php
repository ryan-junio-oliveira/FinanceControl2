<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Services\AccountService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Accounts', description: 'Contas bancárias e transferências')]
class AccountController extends Controller
{
    #[OA\Get(
        path: '/api/v1/accounts',
        summary: 'Lista contas',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Contas com saldo')]
    )]
    public function index(AccountService $service): AnonymousResourceCollection
    {
        return AccountResource::collection($service->list(Fin::group()));
    }

    #[OA\Post(
        path: '/api/v1/accounts',
        summary: 'Cria conta',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'bank_id', 'kind', 'initial_balance'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Conta Itaú'),
                    new OA\Property(property: 'bank_id', type: 'integer'),
                    new OA\Property(property: 'kind', type: 'string', enum: ['corrente', 'poupanca', 'digital', 'investimento', 'carteira']),
                    new OA\Property(property: 'initial_balance', type: 'string', example: '0,00'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Conta criada')]
    )]
    public function store(AccountRequest $request, AccountService $service): JsonResponse
    {
        $conta = $service->create(Fin::group(), $request->validated());

        return (new AccountResource($conta))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/accounts/{account}',
        summary: 'Detalha conta',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'account', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Conta')]
    )]
    public function show(Account $account): AccountResource
    {
        abort_unless($account->group_id === Fin::groupId(), 404);
        $account->load('bank');

        return new AccountResource($account);
    }

    #[OA\Patch(
        path: '/api/v1/accounts/{account}',
        summary: 'Atualiza conta',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'account', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'bank_id', type: 'integer'),
                    new OA\Property(property: 'kind', type: 'string'),
                    new OA\Property(property: 'active', type: 'boolean'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Conta atualizada')]
    )]
    public function update(AccountRequest $request, Account $account, AccountService $service): AccountResource
    {
        abort_unless($account->group_id === Fin::groupId(), 404);
        $this->authorize('manage', $account);

        return new AccountResource($service->update($account, $request->validated(), $request->boolean('active')));
    }

    #[OA\Delete(
        path: '/api/v1/accounts/{account}',
        summary: 'Exclui conta',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'account', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Excluída')]
    )]
    public function destroy(Account $account, AccountService $service): JsonResponse
    {
        abort_unless($account->group_id === Fin::groupId(), 404);
        $this->authorize('manage', $account);
        $service->destroy($account);

        return response()->json(null, 204);
    }

    #[OA\Post(
        path: '/api/v1/accounts/transfer',
        summary: 'Transferência interna',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['from_account_id', 'to_account_id', 'amount', 'occurred_on', 'user_id'],
                properties: [
                    new OA\Property(property: 'from_account_id', type: 'integer'),
                    new OA\Property(property: 'to_account_id', type: 'integer'),
                    new OA\Property(property: 'amount', type: 'string', example: '250,00'),
                    new OA\Property(property: 'occurred_on', type: 'string', format: 'date'),
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'description', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Transferência registrada')]
    )]
    public function transfer(TransferRequest $request, AccountService $service): JsonResponse
    {
        $transaction = $service->transfer(Fin::group(), $request->validated());

        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }
}
