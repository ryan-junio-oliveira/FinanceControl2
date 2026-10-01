<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\Fin;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Somente o administrador acessa os logs.');

        $family = Fin::family();
        $fid = $family->id;

        $q = AuditLog::where('family_id', $fid)->with('member');

        if ($request->filled('q')) {
            $q->where('description', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('acao')) {
            $q->where('action', $request->acao);
        }
        if ($request->filled('membro')) {
            $q->where('user_id', $request->membro);
        }
        if ($request->filled('de')) {
            $q->whereDate('created_at', '>=', $request->de);
        }
        if ($request->filled('ate')) {
            $q->whereDate('created_at', '<=', $request->ate);
        }

        $logs = $q->orderByDesc('id')->paginate(25)->withQueryString();

        $membros = $family->users()->orderBy('name')->get();
        $acoes = [
            'created' => 'Criação',
            'updated' => 'Alteração',
            'deleted' => 'Exclusão',
            'login' => 'Login',
            'logout' => 'Logout',
            'settings' => 'Configurações',
            'invite' => 'Convite',
            'role' => 'Papel',
            'member' => 'Membro',
            'info' => 'Ação',
        ];

        return view('pages.admin.logs', compact('logs', 'membros', 'acoes'));
    }
}