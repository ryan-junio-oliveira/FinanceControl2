<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\IncomingMessage;
use App\Enums\CardBrand;
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
            if (str_ends_with($low, ':create') || $low === 'cadastrar') {
                $this->startCardFlow($driver, $msg);

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
            'cardname' => $this->stepBrand($driver, $msg, $data, $msg->text),
            'brand' => $this->stepHolder($driver, $msg, $data, $msg->text, $user),
            'holder' => $this->stepAccountLink($driver, $msg, $data, $msg->text, $user),
            'linkaccount' => $this->stepLimit($driver, $msg, $data, $msg->text),
            'limit' => $this->stepClosing($driver, $msg, $data, $msg->text),
            'closing' => $this->stepDue($driver, $msg, $data, $msg->text),
            'due' => $this->stepConfirmCard($driver, $msg, $data, $msg->text, $user),
            'confirmcard' => $this->stepSaveCard($driver, $msg, $data, $msg->text, $user),
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
                '➕ Cadastrar cartão' => 'cartoes:create',
            ])
        );
    }

    public function faturas(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $cards = app(CardService::class)->list($user->group);
        $lines = [BotPresenter::header('Faturas em aberto'), ''];
        foreach ($cards as $c) {
            $open = (float) $c->open_invoice;
            $lines[] = '💳 <b>'.e($c->name).'</b>';
            $lines[] = '   '.BotPresenter::money($open).' · vence '.$c->nextDueDate()->format('d/m');
        }
        if ($cards->isEmpty()) {
            $lines[] = 'Nenhum cartão ativo.';
        }
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }

    private function startFlow(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $cards = app(CardService::class)->list($user->group);
        if ($cards->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Você ainda não tem cartões. Crie um no sistema primeiro.');

            return;
        }
        $lines = $cards->map(fn ($c) => $c->name)->all();
        $this->ask($driver, $msg, 'card', ['_cards' => $cards->pluck('id')->all()], '💳 Em qual cartão?', $this->selectKeyboard($lines));
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

        $cats = app(CategoryService::class)->list($user->group, ['tipo' => 'despesa'], 100);
        if ($cats->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Sem categorias cadastradas.');

            return;
        }
        $lines = $cats->map(fn ($c) => $c->name)->all();
        $this->ask($driver, $msg, 'category', $data + ['_categories' => $cats->pluck('id')->all()], '🏷️ Qual a categoria?', $this->selectKeyboard($lines));
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

        $card = $user->group->creditCards()->find($data['credit_card_id']);
        $cat = $user->group->categories()->find($data['category_id']);
        $summary = "🧾 <b>Confirmar compra?</b>\n"
            .BotPresenter::divider()."\n"
            .'💳 '.($card->name ?? '—')."\n"
            .'🛒 '.e($data['description'])."\n"
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

        app(CardService::class)->createItem($user->group, [
            'credit_card_id' => $data['credit_card_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'occurred_on' => date('Y-m-d'),
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
        ]);

        $this->done($driver, $msg, $user, '✅ Compra lançada na fatura: <b>'.e($data['description']).'</b> ('.BotPresenter::money($data['amount']).').');
    }

    public function startCardFlow(BotDriver $driver, IncomingMessage $msg): void
    {
        $this->ask($driver, $msg, 'cardname', [], '💳 <b>Novo cartão</b>'."\n\n".'Qual o nome? (ex.: Nubank Ultravioleta)', $this->cancelKeyboard());
    }

    private function stepBrand(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $name = trim($text);
        if ($name === '') {
            $this->ask($driver, $msg, 'cardname', $data, 'Informe um nome válido:', $this->cancelKeyboard());

            return;
        }
        $data['name'] = mb_substr($name, 0, 255);

        $brands = CardBrand::options();
        $this->ask($driver, $msg, 'brand', $data + ['_brands' => array_keys($brands)], '💳 Qual a bandeira?', $this->selectKeyboard(array_values($brands)));
    }

    private function stepHolder(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $brands = $data['_brands'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($brands[$num - 1])) {
            $this->ask($driver, $msg, 'brand', $data, '❌ Escolha o número da bandeira:', $this->cancelKeyboard());

            return;
        }
        $data['brand'] = $brands[$num - 1];
        unset($data['_brands']);

        $members = $user->group->users()->orderBy('name')->get();
        if ($members->count() <= 1) {
            $data['holder_user_id'] = $user->id;
            $this->askAccountLink($driver, $msg, $data, $user);

            return;
        }
        $lines = [];
        foreach ($members as $i => $m) {
            $lines[] = ($m->id === $user->id ? $m->name.' (você)' : $m->name);
        }
        $this->ask($driver, $msg, 'holder', $data + ['_members' => $members->pluck('id')->all()], '👤 Quem é o titular?', $this->selectKeyboard($lines));
    }

    private function stepAccountLink(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_members'] ?? [];
        if ($ids !== []) {
            $num = self::num($text);
            if ($num === null || ! isset($ids[$num - 1])) {
                $this->ask($driver, $msg, 'holder', $data, '❌ Escolha o número do titular:', $this->cancelKeyboard());

                return;
            }
            $data['holder_user_id'] = $ids[$num - 1];
            unset($data['_members']);
        }
        $this->askAccountLink($driver, $msg, $data, $user);
    }

    private function askAccountLink(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        $accounts = app(CardService::class)->activeAccounts($user->group);
        if ($accounts->isEmpty()) {
            $data['account_id'] = null;
            $this->stepLimit($driver, $msg, $data, '');

            return;
        }
        $lines = $accounts->map(fn ($a) => $a->name.' ('.($a->bank->name ?? '—').')')->all();
        $lines[] = 'Sem conta vinculada';
        $this->ask(
            $driver,
            $msg,
            'linkaccount',
            $data + ['_accounts' => $accounts->pluck('id')->all()],
            '🏦 Conta para pagar a fatura?',
            $this->selectKeyboard($lines)
        );
    }

    private function stepLimit(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $ids = $data['_accounts'] ?? [];
        if ($ids !== []) {
            $count = count($ids);
            $num = self::num($text);
            if ($num === null || $num < 1 || $num > $count + 1) {
                $this->ask($driver, $msg, 'linkaccount', $data, '❌ Escolha o número da conta:', $this->cancelKeyboard());

                return;
            }
            $data['account_id'] = $num === $count + 1 ? null : $ids[$num - 1];
            unset($data['_accounts']);
        }
        $this->ask($driver, $msg, 'limit', $data, '💵 Qual o limite do cartão? (ex.: 5000 ou 0)', $this->cancelKeyboard());
    }

    private function stepClosing(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $raw = trim($text);
        $limit = preg_match('/^0([.,]0+)?$/', $raw) ? 0.0 : BotPresenter::parseAmount($raw);
        if ($limit === null) {
            $this->ask($driver, $msg, 'limit', $data, '❌ Valor inválido. Digite como 5000:', $this->cancelKeyboard());

            return;
        }
        $data['credit_limit'] = $limit;
        $this->ask($driver, $msg, 'closing', $data, '📅 Dia do fechamento da fatura? (1-28)', $this->cancelKeyboard());
    }

    private function stepDue(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $day = self::parseDay($text);
        if ($day === null) {
            $this->ask($driver, $msg, 'closing', $data, '❌ Dia inválido. Digite de 1 a 28:', $this->cancelKeyboard());

            return;
        }
        $data['closing_day'] = $day;
        $this->ask($driver, $msg, 'due', $data, '📅 Dia do vencimento da fatura? (1-28)', $this->cancelKeyboard());
    }

    private function stepConfirmCard(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $day = self::parseDay($text);
        if ($day === null) {
            $this->ask($driver, $msg, 'due', $data, '❌ Dia inválido. Digite de 1 a 28:', $this->cancelKeyboard());

            return;
        }
        $data['due_day'] = $day;

        $holder = $user->group->users()->find($data['holder_user_id']);
        $account = $user->group->accounts()->find($data['account_id']);
        $brand = CardBrand::tryFrom($data['brand'])->label();
        $summary = '🧾 <b>Confirmar novo cartão?</b>'."\n"
            .BotPresenter::divider()."\n"
            .'💳 '.e($data['name'])."\n"
            .'🏷️ '.$brand.' · 👤 '.e($holder->name ?? '—')."\n"
            .'🏦 '.e($account->name ?? 'Sem conta vinculada')."\n"
            .'💵 Limite: <b>'.BotPresenter::money($data['credit_limit']).'</b>'."\n"
            .'📅 Fecha dia '.$data['closing_day'].' · vence dia '.$data['due_day'];
        $this->ask($driver, $msg, 'confirmcard', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSaveCard(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->done($driver, $msg, $user, '🚫 Cadastro cancelado.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirmcard', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        app(CardService::class)->createCard($user->group, [
            'name' => $data['name'],
            'brand' => $data['brand'],
            'holder_user_id' => $data['holder_user_id'],
            'account_id' => $data['account_id'],
            'credit_limit' => $data['credit_limit'],
            'closing_day' => $data['closing_day'],
            'due_day' => $data['due_day'],
            'active' => true,
        ]);

        $this->done($driver, $msg, $user, '✅ Cartão <b>'.e($data['name']).'</b> cadastrado!');
    }

    private static function parseDay(string $text): ?int
    {
        $text = trim($text);
        if (! preg_match('/^\d{1,2}$/', $text)) {
            return null;
        }
        $day = (int) $text;

        return $day >= 1 && $day <= 28 ? $day : null;
    }
}
