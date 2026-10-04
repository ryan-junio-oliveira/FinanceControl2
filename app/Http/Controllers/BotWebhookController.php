<?php

namespace App\Http\Controllers;

use App\Bot\BotManager;
use App\Jobs\ProcessTelegramUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BotWebhookController extends Controller
{
    /** Recebe atualizações do Telegram e enfileira (responde <200ms, evita retry). */
    public function telegram(Request $request): JsonResponse
    {
        $driver = BotManager::driver();
        if ($driver->name() === 'telegram' && config('bot.telegram.secret') === '') {
            if (! Cache::has('bot:secret-warned')) {
                Cache::put('bot:secret-warned', true, now()->addDay());
                Log::warning('[bot] Webhook do Telegram sem BOT_TELEGRAM_SECRET.');
            }
            // Fail-closed em produção: sem secret qualquer terceiro forja updates.
            abort_if(app()->isProduction(), 503, 'Bot indisponível.');
        }
        // Validação de secret do canal (quando configurado) antes de enfileirar.
        $secret = (string) config('bot.telegram.secret');
        if ($driver->name() === 'telegram' && $secret !== ''
            && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->json(['error' => 'invalid signature'], 401);
        }

        ProcessTelegramUpdate::dispatch($request->all())->onQueue('bot');

        return response()->json(['ok' => true]);
    }
}
