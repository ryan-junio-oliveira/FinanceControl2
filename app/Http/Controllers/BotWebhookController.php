<?php

namespace App\Http\Controllers;

use App\Bot\BotManager;
use App\Bot\BotRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotWebhookController extends Controller
{
    /** Recebe atualizações do Telegram e despacha para o roteador do bot. */
    public function telegram(Request $request): JsonResponse
    {
        $driver = BotManager::driver();
        $msg = $driver->parseWebhook($request);

        if ($msg) {
            BotRouter::handle($driver, $msg);
        }

        return response()->json(['ok' => true]);
    }
}
