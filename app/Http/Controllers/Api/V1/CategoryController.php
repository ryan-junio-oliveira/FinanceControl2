<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Categories', description: 'Categorias de despesas e receitas')]
class CategoryController extends Controller
{
    #[OA\Get(
        path: '/api/v1/categories',
        summary: 'Lista categorias',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tipo', in: 'query', schema: new OA\Schema(type: 'string', enum: ['despesa', 'receita'])),
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'arquivadas', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [new OA\Response(response: 200, description: 'Categorias')]
    )]
    public function index(Request $request, CategoryService $service): AnonymousResourceCollection
    {
        return CategoryResource::collection($service->list(Fin::family(), [
            'tipo' => $request->query('tipo'),
            'q' => $request->query('q'),
            'arquivadas' => $request->boolean('arquivadas'),
        ]));
    }

    #[OA\Post(
        path: '/api/v1/categories',
        summary: 'Cria categoria',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Alimentação'),
                    new OA\Property(property: 'type', type: 'string', enum: ['despesa', 'receita']),
                    new OA\Property(property: 'icon', type: 'string', example: 'restaurant'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Categoria criada')]
    )]
    public function store(CategoryRequest $request, CategoryService $service): JsonResponse
    {
        $categoria = $service->create(Fin::family(), $request->validated());

        return (new CategoryResource($categoria))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/categories/{category}',
        summary: 'Detalha categoria',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Categoria')]
    )]
    public function show(Category $category): CategoryResource
    {
        abort_unless($category->family_id === Fin::familyId(), 404);

        return new CategoryResource($category);
    }

    #[OA\Patch(
        path: '/api/v1/categories/{category}',
        summary: 'Atualiza categoria',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'icon', type: 'string'),
                    new OA\Property(property: 'archived', type: 'boolean'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Categoria atualizada')]
    )]
    public function update(CategoryRequest $request, Category $category, CategoryService $service): CategoryResource
    {
        abort_unless($category->family_id === Fin::familyId(), 404);
        $this->authorize('manage', $category);

        return new CategoryResource($service->update($category, $request->validated(), $request->boolean('archived')));
    }

    #[OA\Delete(
        path: '/api/v1/categories/{category}',
        summary: 'Exclui categoria',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 204, description: 'Excluída')]
    )]
    public function destroy(Category $category, CategoryService $service): JsonResponse
    {
        abort_unless($category->family_id === Fin::familyId(), 404);
        $this->authorize('manage', $category);
        $service->destroy($category);

        return response()->json(null, 204);
    }
}
