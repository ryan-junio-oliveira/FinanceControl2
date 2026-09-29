<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Models\Transaction;
use App\Support\Fin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = request()->user()->load('family');
        $mes = Fin::month();

        $gastoMes = (float) Transaction::where('family_id', $user->family_id)
            ->where('user_id', $user->id)
            ->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])
            ->whereYear('occurred_on', substr($mes, 0, 4))
            ->whereMonth('occurred_on', substr($mes, 5, 2))
            ->sum('amount');

        $mesada = $user->allowance()->where('active', true)->first();

        return view('pages.profile.show', compact('user', 'mes', 'gastoMes', 'mesada'));
    }

    public function edit(): View
    {
        return view('pages.profile.form', ['user' => request()->user()]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('perfil')->with('status', 'Perfil atualizado.');
    }

    public function editPassword(): View
    {
        return view('pages.profile.password');
    }

    public function updatePassword(ProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated()['password'])]);

        return redirect()->route('perfil')->with('status', 'Senha alterada com sucesso.');
    }
}
