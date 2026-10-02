<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.cadastro');
    }

    public function store(RegisterRequest $request, AuthService $service): RedirectResponse
    {
        $user = $service->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Conta criada! Bem-vindo ao Prumo.');
    }
}
