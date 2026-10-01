<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FirstAccessRequest;
use App\Models\Invitation;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FirstAccessController extends Controller
{
    public function show(string $token): View
    {
        $invitation = Invitation::where('token', $token)->whereNull('accepted_at')->firstOrFail();

        return view('pages.auth.primeiro-acesso', [
            'invitation' => $invitation,
            'inviter' => $invitation->family->users()->where('role', 'admin')->first(),
        ]);
    }

    public function store(FirstAccessRequest $request, string $token, AuthService $service): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->whereNull('accepted_at')->firstOrFail();

        $user = $service->acceptInvite($invitation, $request->validated()['password']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Senha definida! Bem-vindo à '.$invitation->family->name.'.');
    }
}
