<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditService;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Audit', description: 'Trilha de auditoria (somente admin)')]
class AuditController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/logs',
        summary: 'Lista logs de auditoria',
        description: 'Trilha de todas as ações na conta. Somente o administrador principal.',
        tags: ['Audit'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'acao', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'membro', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'de', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'ate', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 25)),
        ],
        responses: [new OA\Response(response: 200, description: 'Logs paginados')]
    )]
    public function index(Request $request, AuditService $service): AnonymousResourceCollection|JsonResponse
    {
        abort_unless(request()->user()->role === 'admin', 403, 'Somente o administrador acessa os logs.');

        return AuditLogResource::collection($service->list(Fin::group(), [
            'q' => $request->query('q'),
            'acao' => $request->query('acao'),
            'membro' => $request->query('membro'),
            'de' => $request->query('de'),
            'ate' => $request->query('ate'),
        ], (int) $request->query('per_page', 25)));
    }
}
