<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Family;
use App\Models\User;
use App\Support\CategoryCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.cadastro');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $family = Family::create([
                'name' => $data['family_name'],
                'plan' => 'pro_trial',
                'trial_ends_at' => now()->addDays(14),
            ]);
            $family->settings()->create([]);

            // Catálogo padrão: a família já nasce com ~100 categorias.
            CategoryCatalog::seedForFamily($family);

            return User::create([
                'name' => $data['manager_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'family_id' => $family->id,
                'role' => 'admin',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Conta criada! Bem-vindo ao FinFamília.');
    }
}
