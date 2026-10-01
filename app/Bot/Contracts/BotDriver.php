<?php

namespace App\Bot\Contracts;

use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use Illuminate\Http\Request;

/**
 * Contrato de canal (Strategy).
 *
 * Todo driver (Telegram, WhatsApp, Null) implementa a mesma interface,
 * então trocar de canal é só mudar `BOT_DRIVER` — as conversas não mudam.
 */
interface BotDriver
{
    /** Nome do canal: 'telegram' | 'whatsapp' | 'null'. */
    public function name(): string;

    /** Envia texto, opcionalmente com teclado de botões. */
    public function sendText(string $chatId, string $text, ?BotKeyboard $keyboard = null): void;

    /**
     * Converte o payload do webhook em mensagem normalizada.
     * Retorna null quando o payload é inválido (ex.: verificação/segredo).
     */
    public function parseWebhook(Request $request): ?IncomingMessage;
}
