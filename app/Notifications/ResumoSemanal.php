<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResumoSemanal extends Notification
{
    use Queueable;

    public function __construct(
        public string $periodo,
        public float $receitas,
        public float $despesas,
        public int $vencidas,
        public float $vencidasTotal,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $resultado = $this->receitas - $this->despesas;
        $fmt = fn (float $v) => 'R$ '.number_format($v, 2, ',', '.');

        return [
            'icon' => 'insights',
            'title' => "Resumo semanal · {$this->periodo}",
            'body' => "Receitas {$fmt($this->receitas)} · despesas {$fmt($this->despesas)} · resultado {$fmt($resultado)}"
                .($this->vencidas > 0 ? " · {$this->vencidas} vencida(s) ({$fmt($this->vencidasTotal)})" : ''),
            'url' => route('dashboard'),
        ];
    }
}
