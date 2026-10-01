<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Invitation;
use App\Models\User;
use App\Support\CategoryCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Regras de domínio da autenticação e do cadastro.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
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
    }

    /** Ativa o acesso de um convite pendente (primeiro acesso). */
    public function acceptInvite(Invitation $invitation, string $password): User
    {
        abort_if(User::where('email', $invitation->email)->exists(), 422, 'Este e-mail já possui acesso. Use o login.');

        return DB::transaction(function () use ($invitation, $password) {
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'family_id' => $invitation->family_id,
                'role' => $invitation->role,
            ]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }
}
