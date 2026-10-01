<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteRequest;
use App\Http\Requests\MemberRoleRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\FamilyService;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(FamilyService $service): View
    {
        return view('pages.familia', $service->dashboard(Fin::family(), Fin::month()));
    }

    public function createInvite(): View
    {
        return view('pages.family.invite-form');
    }

    /** Convida membro: cria convite com token de primeiro acesso e envia e-mail. */
    public function invite(InviteRequest $request, FamilyService $service): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        ['invitation' => $invitation, 'mailed' => $mailed] = $service->invite($family, $data);

        $status = $mailed
            ? 'Convite criado e e-mail enfileirado para '.$data['email'].'.'
            : 'Convite criado, mas não foi possível enviar o e-mail agora. Compartilhe o link de primeiro acesso com '.$data['name'].'.';

        return redirect()->route('familia')->with('status', $status);
    }

    public function revokeInvite(Invitation $invite, FamilyService $service): RedirectResponse
    {
        abort_if($invite->family_id !== Fin::familyId(), 404);
        $service->revokeInvite($invite);

        return back()->with('status', 'Convite revogado.');
    }

    public function removeMember(User $member, FamilyService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($member->family_id !== $family->id, 404);
        $service->removeMember($member, request()->user()->id);

        return back()->with('status', 'Membro removido.');
    }

    public function updateRole(MemberRoleRequest $request, User $member, FamilyService $service): RedirectResponse
    {
        $family = Fin::family();
        abort_if($member->family_id !== $family->id, 404);

        $service->updateRole($member, $request->validated());

        return back()->with('status', 'Papel atualizado.');
    }
}
