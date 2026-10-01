<?php

namespace App\Services;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Regras de domínio do perfil (dados, senha, encerramento da conta).
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class ProfileService
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }

    public function updatePassword(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);
    }

    /**
     * Encerra o cadastro: só o administrador principal.
     * Apaga a conta da família inteira (todos os membros e registros).
     */
    public function destroy(User $user): void
    {
        abort_if($user->role !== 'admin', 403, 'Somente o administrador pode encerrar o cadastro.');

        $family = $user->family()->firstOrFail();
        $memberIds = $family->users()->pluck('id')->all();

        Audit::silence(function () use ($family, $memberIds) {
            DB::transaction(function () use ($family, $memberIds) {
                // Notificações e sessões não têm FK: limpar manualmente.
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $memberIds)
                    ->delete();
                DB::table('sessions')->whereIn('user_id', $memberIds)->delete();

                // Membros primeiro (lançamentos deles caem por cascata), depois a família (cascata no resto).
                User::whereIn('id', $memberIds)->delete();
                $family->delete();
            });
        });
    }

    /** Finaliza a sessão web após o encerramento (logout + invalidação). */
    public function logoutWeb(): void
    {
        Audit::silence(function () {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        });
    }
}
