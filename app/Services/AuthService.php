<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Invitation;
use App\Models\TrialBlacklist;
use App\Models\User;
use App\Support\CategoryCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Regras de domínio da autenticação e do cadastro.
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class AuthService
{
    public function register(array $data): User
    {
        $ip = (string) request()->ip();
        $this->assertTrialAllowed($data['email'], $ip);

        $user = DB::transaction(function () use ($data) {
            $group = Group::create([
                'name' => $data['group_name'],
                'plan' => 'pro_trial',
                'trial_ends_at' => now()->addDays((int) config('billing.trial_days', 14)),
            ]);
            $group->settings()->create([]);

            // Catálogo padrão: o grupo já nasce com os temas.
            CategoryCatalog::seedForGroup($group);

            return User::create([
                'name' => $data['manager_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'group_id' => $group->id,
                'role' => 'admin',
                'terms_accepted_at' => now(),
            ]);
        });

        $this->rememberTrial($data['email'], $ip);

        return $user;
    }

    /** Ativa o acesso de um convite pendente (primeiro acesso). */
    public function acceptInvite(Invitation $invitation, string $password): User
    {
        abort_if(User::where('email', $invitation->email)->exists(), 422, 'Este e-mail já possui acesso. Use o login.');

        $user = DB::transaction(function () use ($invitation, $password) {
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'group_id' => $invitation->group_id,
                'role' => $invitation->role,
                'terms_accepted_at' => now(),
            ]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        // Membro do grupo também fica marcado: não pode criar trial próprio depois.
        TrialBlacklist::firstOrCreate(
            ['identifier' => $invitation->email, 'type' => 'email'],
            ['created_at' => now()]
        );

        return $user;
    }

    /** Bloqueia cadastro se o e-mail (ou IP) já usou o período de teste. */
    private function assertTrialAllowed(string $email, string $ip): void
    {
        $blocked = TrialBlacklist::where('type', 'email')->where('identifier', $email)->exists();
        if (! $blocked && config('billing.blacklist_ip')) {
            $blocked = TrialBlacklist::where('type', 'ip')->where('identifier', $ip)->exists();
        }
        if ($blocked) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já utilizou o período de teste gratuito do Prumo. É preciso assinar para continuar.',
            ]);
        }
    }

    private function rememberTrial(string $email, string $ip): void
    {
        TrialBlacklist::firstOrCreate(
            ['identifier' => $email, 'type' => 'email'],
            ['created_at' => now()]
        );
        if (config('billing.blacklist_ip')) {
            TrialBlacklist::firstOrCreate(
                ['identifier' => $ip, 'type' => 'ip'],
                ['created_at' => now()]
            );
        }
    }
}
