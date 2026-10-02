<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebhook extends Command
{
    protected $signature = 'bot:telegram-webhook {url? : URL pública do webhook (ex.: https://app.exemplo.com/api/bot/telegram)}';

    protected $description = 'Registra o webhook do bot no Telegram';

    public function handle(): int
    {
        $token = (string) config('bot.telegram.token');
        if ($token === '') {
            $this->error('Defina BOT_TELEGRAM_TOKEN no .env.');

            return self::FAILURE;
        }

        $url = $this->argument('url') ?? url('/api/bot/telegram');
        $payload = ['url' => $url, 'allowed_updates' => ['message', 'callback_query']];
        $secret = (string) config('bot.telegram.secret');
        if ($secret !== '') {
            $payload['secret_token'] = $secret;
        } else {
            $this->warn('BOT_TELEGRAM_SECRET vazio: qualquer um pode forjar chamadas ao webhook. Defina um segredo.');
        }

        $res = Http::baseUrl(config('bot.telegram.api')."/bot{$token}")->post('/setWebhook', $payload);

        if (! $res->successful() || ! ($res->json('ok') ?? false)) {
            $this->error('Falha ao registrar: '.$res->body());

            return self::FAILURE;
        }

        $this->info("Webhook registrado: {$url}");

        return self::SUCCESS;
    }
}
