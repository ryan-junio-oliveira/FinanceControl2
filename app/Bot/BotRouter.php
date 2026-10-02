<?php

namespace App\Bot;

use App\Bot\Contracts\BotDriver;
use App\Bot\Handlers\AccountHandler;
use App\Bot\Handlers\BotHandler;
use App\Bot\Handlers\CardHandler;
use App\Bot\Handlers\ExpenseHandler;
use App\Bot\Handlers\IncomeHandler;
use App\Bot\Handlers\InvestmentHandler;
use App\Bot\Handlers\LinkHandler;
use App\Bot\Handlers\MenuHandler;
use App\Bot\Handlers\ReceiptHandler;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\BotIdentity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** Direciona cada mensagem para o handler certo. */
final class BotRouter
{
    private const CANCEL = ['/cancelar', 'cancelar', 'sair', '/menu', 'menu', '0'];

    public static function handle(BotDriver $driver, IncomingMessage $msg): void
    {
        $text = trim($msg->text);
        $low = mb_strtolower($text);

        if (str_starts_with($low, '/start')) {
            $parts = preg_split('/\s+/', $text, 2);
            app(LinkHandler::class)->start($driver, $msg, $parts[1] ?? null);

            return;
        }

        $user = self::user($msg);

        // Sem vínculo: tenta código direto, senão pede o código.
        if (! $user) {
            if (preg_match('/^\d{6}$/', $text)) {
                app(LinkHandler::class)->handle($driver, $msg, ['step' => 'code'], null);

                return;
            }
            app(LinkHandler::class)->askCode($driver, $msg);
            ConversationState::put($msg->channel, $msg->chatId, LinkHandler::class, 'code');

            return;
        }

        Auth::setUser($user);

        $state = ConversationState::get($msg->channel, $msg->chatId);

        // Cancelar/voltar: exceto "0" quando há um fluxo ativo (ex.: saldo inicial).
        if (in_array($low, self::CANCEL, true) || $low === 'cancel') {
            if ($low !== '0' || ! $state) {
                app(MenuHandler::class)->show($driver, $msg, $user);

                return;
            }
        }

        // Foto/documento sempre inicia o fluxo de comprovante.
        if ($msg->hasFile()) {
            ConversationState::put($msg->channel, $msg->chatId, ReceiptHandler::class, 'download');
            app(ReceiptHandler::class)->handle($driver, $msg, ['step' => 'download', 'data' => []], $user);

            return;
        }

        // Atalhos de submenu vindos dos botões (ex.: despesa:new).
        if (preg_match('/^(despesa|receita):(list|new)$/', $low, $m)) {
            $handler = $m[1] === 'despesa' ? ExpenseHandler::class : IncomeHandler::class;
            ConversationState::put($msg->channel, $msg->chatId, $handler, 'menu');
            app($handler)->handle($driver, $msg, ['step' => 'menu', 'data' => []], $user);

            return;
        }
        if (preg_match('/^cartoes:(faturas|new|create)$/', $low, $m)) {
            ConversationState::put($msg->channel, $msg->chatId, CardHandler::class, 'menu');
            app(CardHandler::class)->handle($driver, $msg, ['step' => 'menu', 'data' => []], $user);

            return;
        }
        if (preg_match('/^contas:(list|new)$/', $low, $m)) {
            ConversationState::put($msg->channel, $msg->chatId, AccountHandler::class, 'menu');
            app(AccountHandler::class)->handle($driver, $msg, ['step' => 'menu', 'data' => []], $user);

            return;
        }
        if (preg_match('/^investimentos:(list|new)$/', $low, $m)) {
            ConversationState::put($msg->channel, $msg->chatId, InvestmentHandler::class, 'menu');
            app(InvestmentHandler::class)->handle($driver, $msg, ['step' => 'menu', 'data' => []], $user);

            return;
        }

        if ($state && class_exists($state['handler'])) {
            $handler = app($state['handler']);
            if ($handler instanceof BotHandler) {
                $handler->handle($driver, $msg, $state, $user);

                return;
            }
        }

        $option = self::matchMenu($low);
        if ($option !== null) {
            app(MenuHandler::class)->option($driver, $msg, $user, $option);

            return;
        }

        app(MenuHandler::class)->show($driver, $msg, $user, true);
    }

    private static function user(IncomingMessage $msg): ?User
    {
        return BotIdentity::where('channel', $msg->channel)
            ->where('external_id', $msg->chatId)
            ->first()?->user;
    }

    private static function matchMenu(string $low): ?string
    {
        $map = [
            'dados' => ['1', 'dados', 'dados financeiros', 'financeiro', 'dashboard', 'resumo', 'menu:dados'],
            'despesas' => ['2', 'despesa', 'despesas', 'menu:despesas'],
            'receitas' => ['3', 'receita', 'receitas', 'menu:receitas'],
            'cartoes' => ['4', 'cartao', 'cartão', 'cartoes', 'cartões', 'menu:cartoes'],
            'contas' => ['5', 'conta', 'contas', 'menu:contas'],
            'investimentos' => ['6', 'investimento', 'investimentos', 'menu:investimentos'],
            'mercado' => ['7', 'mercado', 'bolsa', 'acoes', 'ações', 'menu:mercado'],
        ];
        foreach ($map as $option => $keys) {
            if (in_array($low, $keys, true)) {
                return $option;
            }
        }

        return null;
    }
}
