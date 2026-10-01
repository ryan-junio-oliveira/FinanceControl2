<?php

namespace App\Bot;

use App\Bot\ValueObjects\BotKeyboard;
use App\Models\BotIdentity;
use App\Models\Family;
use App\Models\Transaction;
use App\Support\Notify;
use Carbon\Carbon;

/**
 * Alertas proativos no chat (ex.: vencimentos próximos).
 * Uma mensagem agregada por usuário — nunca uma por conta.
 */
final class BotNotifier
{
    /** Envia os avisos de vencimento. Retorna quantas mensagens foram enviadas. */
    public static function vencimentos(): int
    {
        $driver = BotManager::driver();
        $today = Carbon::today();
        $limite = $today->copy()->addDays(3)->toDateString();
        $enviadas = 0;

        Family::with(['users', 'settings'])->each(function (Family $family) use ($driver, $today, $limite, &$enviadas) {
            /** @var array<int, array<int, string>> $itens */
            $itens = [];

            if (Notify::enabled($family, 'conta_vencimento')) {
                $pendentes = Transaction::ofFamily($family->id)
                    ->where('type', 'despesa')->where('status', 'pendente')
                    ->whereNotNull('due_on')->whereDate('due_on', '<=', $limite)
                    ->orderBy('due_on')->get();

                foreach ($pendentes as $t) {
                    $dias = $today->diffInDays(Carbon::parse($t->due_on)->startOfDay(), false);
                    $linha = self::linha(
                        $dias,
                        $t->description.' — <b>'.BotPresenter::money((float) $t->amount).'</b> · '.Carbon::parse($t->due_on)->format('d/m')
                    );
                    foreach (self::destinatarios($family, $t->user_id) as $uid) {
                        $itens[$uid][] = $linha;
                    }
                }
            }

            if (Notify::enabled($family, 'fatura_vencimento')) {
                $cartoes = $family->creditCards()->where('active', true)->get();
                foreach ($cartoes as $cartao) {
                    $venc = $cartao->nextDueDate($today)->startOfDay();
                    if ($venc->toDateString() > $limite) {
                        continue;
                    }
                    $aberto = (float) $cartao->open_invoice;
                    if ($aberto <= 0) {
                        continue;
                    }
                    $dias = $today->diffInDays($venc, false);
                    $linha = self::linha(
                        $dias,
                        'Fatura '.$cartao->name.' — <b>'.BotPresenter::money($aberto).'</b> · '.$venc->format('d/m')
                    );
                    foreach (self::destinatarios($family, $cartao->holder_user_id) as $uid) {
                        $itens[$uid][] = $linha;
                    }
                }
            }

            foreach ($itens as $userId => $linhas) {
                // Canal real do vínculo (o NullDriver só simula o envio nos testes).
                $chatId = BotIdentity::where('user_id', $userId)->where('channel', 'telegram')->value('external_id');
                if ($chatId === null) {
                    continue;
                }
                $texto = BotPresenter::header('Vencimentos próximos')."\n".implode("\n", $linhas);
                $driver->sendText((string) $chatId, $texto, BotKeyboard::menu(['🔙 Menu' => 'menu']));
                $enviadas++;
            }
        });

        return $enviadas;
    }

    /** Dono do item + gestores (sem duplicar), como nas notificações do sistema. */
    private static function destinatarios(Family $family, ?int $donoId): array
    {
        $ids = $donoId ? [$donoId] : [];
        foreach (Notify::gestores($family) as $gestor) {
            $ids[] = $gestor->id;
        }

        return array_values(array_unique($ids));
    }

    private static function linha(int $dias, string $texto): string
    {
        $quando = $dias < 0 ? 'venceu há '.abs($dias).'d' : ($dias === 0 ? 'vence hoje' : ($dias === 1 ? 'vence amanhã' : "vence em {$dias}d"));
        $icon = $dias <= 1 ? '🔴' : '🟡';

        return "{$icon} {$texto} · {$quando}";
    }
}
