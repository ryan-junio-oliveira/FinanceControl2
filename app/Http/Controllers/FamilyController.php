<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteRequest;
use App\Http\Requests\MemberRoleRequest;
use App\Mail\WelcomeEmail;
use App\Models\Invitation;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $mes = Fin::month();

        $membros = $family->users()->orderBy('name')->get()->map(function ($u) use ($family, $mes) {
            $gasto = (float) Transaction::where('family_id', $family->id)->where('user_id', $u->id)
                ->where('type', 'despesa')->whereIn('status', ['pago', 'pendente'])
                ->whereYear('occurred_on', substr($mes, 0, 4))->whereMonth('occurred_on', substr($mes, 5, 2))->sum('amount');
            $u->gasto_mes = $gasto;

            return $u;
        });

        $convites = $family->invitations()->whereNull('accepted_at')->orderByDesc('created_at')->get();

        return view('pages.familia', compact('mes', 'membros', 'convites'));
    }

    public function createInvite(): View
    {
        return view('pages.family.invite-form');
    }

    /** Convida membro: cria convite com token de primeiro acesso e envia e-mail. */
    public function invite(InviteRequest $request): RedirectResponse
    {
        $family = Fin::family();
        $data = $request->validated();

        abort_if($family->users()->where('email', $data['email'])->exists(), 422, 'Este e-mail já pertence à sua conta.');
        abort_if($family->invitations()->where('email', $data['email'])->whereNull('accepted_at')->exists(), 422, 'Já existe um convite pendente para este e-mail.');

        $invitation = $family->invitations()->create($data + ['token' => Str::random(48)]);

        try {
            Mail::to($data['email'])->queue(new WelcomeEmail($invitation));
            $status = 'Convite criado e e-mail enfileirado para '.$data['email'].'.';
        } catch (\Throwable $e) {
            $status = 'Convite criado, mas não foi possível enviar o e-mail agora. Compartilhe o link de primeiro acesso com '.$data['name'].'.';
        }

        return redirect()->route('familia')->with('status', $status);
    }

    public function revokeInvite(Invitation $invite): RedirectResponse
    {
        abort_if($invite->family_id !== Fin::familyId(), 404);
        $invite->delete();

        return back()->with('status', 'Convite revogado.');
    }

    public function removeMember(User $member): RedirectResponse
    {
        $family = Fin::family();
        abort_if($member->family_id !== $family->id, 404);
        abort_if($member->id === request()->user()->id, 422, 'Você não pode remover a si mesmo.');
        abort_if($member->role === 'admin', 422, 'O administrador principal não pode ser removido.');
        abort_if($member->transactions()->exists(), 422, 'Membro com lançamentos não pode ser removido.');
        abort_if($member->cardTransactions()->exists(), 422, 'Membro com compras no cartão não pode ser removido.');

        $member->delete();

        return back()->with('status', 'Membro removido.');
    }

    public function updateRole(MemberRoleRequest $request, User $member): RedirectResponse
    {
        $family = Fin::family();
        abort_if($member->family_id !== $family->id, 404);
        abort_if($member->role === 'admin', 422, 'O papel do administrador principal não pode mudar.');

        $data = $request->validated();
        $member->update($data);

        return back()->with('status', 'Papel atualizado.');
    }
}
