<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;
use App\Services\CardService;
use App\Services\CategoryService;

class CardHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $low = mb_strtolower(trim($msg->text));
        $step = $state['step'] ?? '';
        $data = $state['data'] ?? [];

        if ($step === 'menu') {
            if (str_ends_with($low, ':faturas') || $low === 'faturas' || $low === 'fatura') {
                $this->faturas($driver, $msg, $user);

                return;
            }
            if (str_ends_with($low, ':new') || $low === 'lançar' || $low === 'lancar' || $low === 'novo' || $low === 'comprar') {
                $this->startFlow($driver, $msg, $user);

                return;
            }
            $this->menu($driver, $msg, $user);

            return;
        }

        match ($step) {
            'card' => $this->stepDesc($driver, $msg, $data, $msg->text),
            'desc' => $this->stepAmount($driver, $msg, $data, $msg->text),
            'amount' => $this->stepCategory($driver, $msg, $data, $msg->text, $user),
            'category' => $this->stepConfirm($driver, $msg, $data, $msg->text, $user),
            'confirm' => $this->stepSave($driver, $msg, $data, $msg->text, $user),
            default => $this->menu($driver, $msg, $user),
        };
    }

    public function menu(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        ConversationState::clear($msg->channel, $msg->chatId);
        $driver->sendText(
            $msg->chatId,
            '💳 <b>Cartões — o que deseja?</b>',
            MenuHandler::submenuKeyboard([
                '🧾 Ver faturas' => 'cartoes:faturas',
                '➕ Lançar compra' => 'cartoes:new',
            ])
        );
    }

    public function faturas(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $cards = app(CardService::class)->list($user->family);
        $lines = [BotPresenter::header('Faturas em aberto'), ''];
        foreach ($cards as $c) {
            $open = (float) $c->open_invoice;
            $lines[] = '💳 <b>'.$c->name.'</b>';
            $lines[] = '   '.BotPresenter::money($open).' · vence '.$c->nextDueDate()->format('d/m');
        }
        if ($cards->isEmpty()) {
            $lines[] = 'Nenhum cartão ativo.';
        }
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }

    private function startFlow(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $cards = app(CardService::class)->list($user->family);
        if ($cards->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Você ainda não tem cartões. Crie um no sistema primeiro.');

            return;
        }
        $lines = $cards->map(fn ($c) => $c->name)->all();
        $this->ask($driver, $msg, 'card', ['_cards' => $cards->pluck('id')->all()], "💳 Em qual cartão?\n".$this->numberedList($lines), $this->cancelKeyboard());
    }

    private function stepDesc(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $ids = $data['_cards'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'card', $data, '❌ Escolha o número do cartão:', $this->cancelKeyboard());

            return;
        }
        $data['credit_card_id'] = $ids[$num - 1];
        unset($data['_cards']);
        $this->ask($driver, $msg, 'desc', $data, '🛒 O que comprou?', $this->cancelKeyboard());
    }

    private function stepAmount(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $desc = trim($text);
        if ($desc === '') {
            $this->ask($driver, $msg, 'desc', $data, 'Informe uma descrição válida:', $this->cancelKeyboard());

            return;
        }
        $data['description'] = mb_substr($desc, 0, 255);
        $this->ask($driver, $msg, 'amount', $data, '💵 Qual o valor? (ex.: 120,00)', $this->cancelKeyboard());
    }

    private function stepCategory(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $amount = BotPresenter::parseAmount($text);
        if ($amount === null) {
            $this->ask($driver, $msg, 'amount', $data, '❌ Valor inválido. Digite como 120,00:', $this->cancelKeyboard());

            return;
        }
        $data['amount'] = $amount;

        $cats = app(CategoryService::class)->list($user->family, ['tipo' => 'despesa'], 100);
        if ($cats->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Sem categorias cadastradas.');

            return;
        }
        $lines = $cats->map(fn ($c) => $c->name)->all();
        $this->ask($driver, $msg, 'category', $data + ['_categories' => $cats->pluck('id')->all()], "🏷️ Qual a categoria?\n".$this->numberedList($lines), $this->cancelKeyboard());
    }

    private function stepConfirm(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_categories'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'category', $data, '❌ Escolha o número da categoria:', $this->cancelKeyboard());

            return;
        }
        $data['category_id'] = $ids[$num - 1];
        unset($data['_categories']);

        $card = $user->family->creditCards()->find($data['credit_card_id']);
        $cat = $user->family->categories()->find($data['category_id']);
        $summary = "🧾 <b>Confirmar compra?</b>\n"
            .BotPresenter::divider()."\n"
            .'💳 '.($card->name ?? '—')."\n"
            .'🛒 '.$data['description']."\n"
            .'💵 <b>'.BotPresenter::money($data['amount']).'</b>'."\n"
            .'🏷️ '.($cat->name ?? '—');
        $this->ask($driver, $msg, 'confirm', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSave(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->done($driver, $msg, $user, '🚫 Compra cancelada.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirm', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        app(CardService::class)->createItem($user->family, [
            'credit_card_id' => $data['credit_card_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'occurred_on' => date('Y-m-d'),
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
        ]);

        $this->done($driver, $msg, $user, '✅ Compra lançada na fatura: <b>'.$data['description'].'</b> ('.BotPresenter::money($data['amount']).').');
    }
}
