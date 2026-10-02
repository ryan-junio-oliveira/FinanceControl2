<?php

namespace App\Http\Controllers;

use App\Bot\BotManager;
use App\Bot\BotRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BotWebhookController extends Controller
{
    /** Recebe atualizações do Telegram e despacha para o roteador do bot. */
    public function telegram(Request $request): JsonResponse
    {
        $driver = BotManager::driver();
        if ($driver->name() === 'telegram' && config('bot.telegram.secret') === '' && ! Cache::has('bot:secret-warned')) {
            Cache::put('bot:secret-warned', true, now()->addDay());
            Log::warning('[bot] Webhook do Telegram sem BOT_TELEGRAM_SECRET: chamadas forjadas seriam aceitas.');
        }
        $msg = $driver->parseWebhook($request);

        if ($msg) {
            BotRouter::handle($driver, $msg);
        }

        return response()->json(['ok' => true]);
    }
}
