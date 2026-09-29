<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FirstAccessRequest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function store(FirstAccessRequest $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->whereNull('accepted_at')->firstOrFail();

        $data = $request->validated();

        if (User::where('email', $invitation->email)->exists()) {
            return back()->withErrors(['email' => 'Este e-mail já possui acesso. Use o login.']);
        }

        $user = DB::transaction(function () use ($invitation, $data) {
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
                'family_id' => $invitation->family_id,
                'role' => $invitation->role,
            ]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Senha definida! Bem-vindo à '.$invitation->family->name.'.');
    }
}
