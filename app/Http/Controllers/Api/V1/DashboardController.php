<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Dashboard;
use App\Support\Fin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard', description: 'KPIs e gráficos da dashboard')]
class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dashboard',
        summary: 'Dados da dashboard',
        description: 'KPIs do mês, patrimônio, contas a pagar, cartões, investimentos e configs dos gráficos.',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'mes', in: 'query', schema: new OA\Schema(type: 'string', example: '2026-10')),
        ],
        responses: [new OA\Response(response: 200, description: 'Dados agregados')]
    )]
    public function index(Request $request): JsonResponse
    {
        $dados = Dashboard::data($request->query('mes', Fin::month()));

        return response()->json([
            'kpi' => $dados['kpi'],
            'patrimonio' => [
                'saldo_contas' => $dados['saldoContas'],
                'investido' => $dados['investido'],
                'faturas_aberto' => $dados['faturaAberto'],
                'liquido' => $dados['patrimonio'],
            ],
            'a_pagar' => $dados['aPagar'],
            'cartoes' => $dados['cartoes'],
            'investimentos' => $dados['inv'],
            'charts' => $dados['charts'],
        ]);
    }
}
