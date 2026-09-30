<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompraDependente extends Notification
{
    use Queueable;

    public function __construct(
        public string $membro,
        public string $descricao,
        public float $valor,
        public string $origem,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'add_shopping_cart',
            'title' => "Compra de {$this->membro}",
            'body' => "{$this->descricao} — {$this->origem} · R$ ".number_format($this->valor, 2, ',', '.'),
            'url' => route('cartoes'),
        ];
    }
}
