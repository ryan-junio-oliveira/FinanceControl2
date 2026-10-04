<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Support\Fin;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request, AuditService $service): View
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Somente o administrador acessa os logs.');

        $group = Fin::group();

        $logs = $service->list($group, [
            'q' => $request->query('q'),
            'acao' => $request->query('acao'),
            'membro' => $request->query('membro'),
            'de' => $request->query('de'),
            'ate' => $request->query('ate'),
        ])->withQueryString();

        return view('pages.admin.logs', [
            'logs' => $logs,
            'membros' => $group->users()->orderBy('name')->get(),
            'acoes' => $service->actionLabels(),
        ]);
    }
}
