<?php

namespace App\Jobs;

use App\Bot\BotManager;
use App\Bot\BotRouter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        // Reconstrói um Request JSON para reaproveitar parseWebhook() do driver (SRP).
        // Secret já validado no controller; repassa para o parse não rejeitar.
        $secret = (string) config('bot.telegram.secret');
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($secret !== '') {
            $server['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] = $secret;
        }
        $request = Request::create(
            '/api/bot/telegram', 'POST', [], [], [],
            $server,
            json_encode($this->payload)
        );
        $driver = BotManager::driver();
        $msg = $driver->parseWebhook($request);
        if ($msg) {
            BotRouter::handle($driver, $msg);
        }
    }
}
