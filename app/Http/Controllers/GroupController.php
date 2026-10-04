<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteRequest;
use App\Http\Requests\MemberRoleRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\GroupService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(GroupService $service): View
    {
        return view('pages.grupo', $service->dashboard(Fin::group(), Fin::month()));
    }

    public function createInvite(): View
    {
        return view('pages.group.invite-form');
    }

    /** Convida membro: cria convite com token de primeiro acesso e envia e-mail. */
    public function invite(InviteRequest $request, GroupService $service): RedirectResponse
    {
        $group = Fin::group();
        $data = $request->validated();

        ['invitation' => $invitation, 'mailed' => $mailed] = $service->invite($group, $data);

        $status = $mailed
            ? 'Convite criado e e-mail enfileirado para '.$data['email'].'.'
            : 'Convite criado, mas não foi possível enviar o e-mail agora. Compartilhe o link de primeiro acesso com '.$data['name'].'.';

        return redirect()->route('grupo')->with('status', $status);
    }

    public function revokeInvite(Invitation $invite, GroupService $service): RedirectResponse
    {
        abort_if($invite->group_id !== Fin::groupId(), 404);
        $service->revokeInvite($invite);

        return back()->with('status', 'Convite revogado.');
    }

    public function removeMember(User $member, GroupService $service): RedirectResponse
    {
        $group = Fin::group();
        abort_if($member->group_id !== $group->id, 404);
        $service->removeMember($member, request()->user()->id);

        return back()->with('status', 'Membro removido.');
    }

    public function updateRole(MemberRoleRequest $request, User $member, GroupService $service): RedirectResponse
    {
        $group = Fin::group();
        abort_if($member->group_id !== $group->id, 404);

        $service->updateRole($member, $request->validated());

        return back()->with('status', 'Papel atualizado.');
    }

    /** Define a palavra-chave do grupo (somente gestor). */
    public function updateSecret(Request $request): RedirectResponse
    {
        $phrase = trim((string) $request->input('secret_phrase', ''));
        abort_if(mb_strlen($phrase) > 80, 422, 'Palavra-chave muito longa (máx. 80 caracteres).');

        Fin::group()->setting()->update(['secret_phrase' => $phrase !== '' ? $phrase : null]);

        return back()->with('status', 'Palavra-chave do grupo atualizada.');
    }
}
