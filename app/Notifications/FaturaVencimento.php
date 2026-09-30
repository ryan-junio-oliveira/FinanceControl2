<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FaturaVencimento extends Notification
{
    use Queueable;

    public function __construct(
        public string $descricao,
        public float $valor,
        public string $vencimento,
        public int $dias,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $quando = $this->dias < 0
            ? 'vence hoje'
            : ($this->dias === 0 ? 'vence hoje' : ($this->dias === 1 ? 'vence amanhã' : "vence em {$this->dias} dias"));

        return [
            'icon' => 'event_busy',
            'title' => "Conta a vencer: {$this->descricao}",
            'body' => 'R$ '.number_format($this->valor, 2, ',', '.')." · {$quando} ({$this->vencimento})",
            'url' => route('despesas', ['status' => 'pendente']),
        ];
    }
}
