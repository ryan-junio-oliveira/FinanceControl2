<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DividendoRegistrado extends Notification
{
    use Queueable;

    public function __construct(
        public string $ativo,
        public float $valor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'trending_up',
            'title' => 'Rendimento creditado',
            'body' => "{$this->ativo} · R$ ".number_format($this->valor, 2, ',', '.'),
            'url' => route('investimentos'),
        ];
    }
}
