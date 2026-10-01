<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Support\Fin;
use Carbon\Carbon;

abstract class TransactionFlowHandler extends BotHandler
{
    abstract protected function type(): string;

    abstract protected function typeLabel(): string;

    public function menu(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        ConversationState::clear($msg->channel, $msg->chatId);
        $driver->sendText(
            $msg->chatId,
            "💰 <b>{$this->typeLabel()} — o que deseja?</b>",
            MenuHandler::submenuKeyboard([
                '🔍 Consultar mês' => $this->type().':list',
                '➕ Lançar' => $this->type().':new',
            ])
        );
    }

    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $text = trim($msg->text);
        $low = mb_strtolower($text);
        $step = $state['step'] ?? '';
        $data = $state['data'] ?? [];

        // Atalhos do submenu.
        if ($step === 'menu') {
            if (str_ends_with($low, ':list') || $low === 'consultar') {
                $this->list($driver, $msg, $user);

                return;
            }
            if (str_ends_with($low, ':new') || $low === 'lançar' || $low === 'lancar' || $low === 'novo') {
                $this->startFlow($driver, $msg);

                return;
            }
            $this->menu($driver, $msg, $user);

            return;
        }

        match ($step) {
            'desc' => $this->stepAmount($driver, $msg, $data, $text),
            'amount' => $this->stepDate($driver, $msg, $data, $text),
            'date' => $this->stepMember($driver, $msg, $data, $text, $user),
            'member' => $this->stepAccount($driver, $msg, $data, $text, $user),
            'account' => $this->stepCategory($driver, $msg, $data, $text, $user),
            'category' => $this->stepConfirm($driver, $msg, $data, $text, $user),
            'confirm' => $this->stepSave($driver, $msg, $data, $text, $user),
            default => $this->menu($driver, $msg, $user),
        };
    }

    public function startFlow(BotDriver $driver, IncomingMessage $msg): void
    {
        $this->ask($driver, $msg, 'desc', [], "➕ <b>Nova {$this->typeLabel(true)}</b>\n\nQual a descrição?", $this->cancelKeyboard());
    }

    private function stepAmount(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $desc = trim($text);
        if ($desc === '') {
            $this->ask($driver, $msg, 'desc', $data, 'Informe uma descrição válida:', $this->cancelKeyboard());

            return;
        }
        $data['description'] = mb_substr($desc, 0, 255);
        $this->ask($driver, $msg, 'amount', $data, '💵 Qual o valor? (ex.: 150,50)', $this->cancelKeyboard());
    }

    private function stepDate(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $amount = BotPresenter::parseAmount($text);
        if ($amount === null) {
            $this->ask($driver, $msg, 'amount', $data, '❌ Valor inválido. Digite como 150,50:', $this->cancelKeyboard());

            return;
        }
        $data['amount'] = $amount;
        $this->ask($driver, $msg, 'date', $data, '📅 Qual a data? Digite <b>hoje</b> ou DD/MM:', $this->cancelKeyboard());
    }

    private function stepMember(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $date = BotPresenter::parseDate($text);
        if ($date === null) {
            $this->ask($driver, $msg, 'date', $data, '❌ Data inválida. Digite <b>hoje</b> ou DD/MM:', $this->cancelKeyboard());

            return;
        }
        $data['occurred_on'] = $date;
        $data['due_on'] = $date;

        $members = $user->family->users()->orderBy('name')->get();
        if ($members->count() <= 1) {
            $data['user_id'] = $user->id;
            $this->askAccount($driver, $msg, $data, $user);

            return;
        }

        $lines = [];
        foreach ($members as $i => $m) {
            $lines[] = ($m->id === $user->id ? $m->name.' (você)' : $m->name);
        }
        $this->ask($driver, $msg, 'member', $data + ['_members' => $members->pluck('id')->all()], "👤 Responsável?\n".$this->numberedList($lines), $this->cancelKeyboard());
    }

    private function stepAccount(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_members'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'member', $data, '❌ Escolha o número do responsável:', $this->cancelKeyboard());

            return;
        }
        $data['user_id'] = $ids[$num - 1];
        unset($data['_members']);
        $this->askAccount($driver, $msg, $data, $user);
    }

    private function askAccount(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        $accounts = app(AccountService::class)->list($user->family)->values();
        if ($accounts->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Você ainda não tem contas. Crie uma no sistema primeiro.');

            return;
        }
        $lines = $accounts->map(fn ($a) => $a->name)->all();
        $this->ask($driver, $msg, 'account', $data + ['_accounts' => $accounts->pluck('id')->all()], "🏦 Qual a conta?\n".$this->numberedList($lines), $this->cancelKeyboard());
    }

    private function stepCategory(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_accounts'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'account', $data, '❌ Escolha o número da conta:', $this->cancelKeyboard());

            return;
        }
        $data['account_id'] = $ids[$num - 1];
        unset($data['_accounts']);

        $cats = app(CategoryService::class)->list($user->family, ['tipo' => $this->type()], 100);
        if ($cats->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Sem categorias cadastradas. Crie no sistema primeiro.');

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
        $data['status'] = 'pago';

        $member = $user->family->users()->find($data['user_id']);
        $account = $user->family->accounts()->find($data['account_id']);
        $cat = $user->family->categories()->find($data['category_id']);
        $summary = '🧾 <b>Confirmar '.strtolower($this->typeLabel(true)).'?</b>'."\n"
            .BotPresenter::divider()."\n"
            .'📝 '.$data['description']."\n"
            .'💵 <b>'.BotPresenter::money($data['amount']).'</b> · '.Carbon::parse($data['occurred_on'])->format('d/m/Y')."\n"
            .'👤 '.($member->name ?? '—').' · 🏦 '.($account->name ?? '—')."\n"
            .'🏷️ '.($cat->name ?? '—');
        $this->ask($driver, $msg, 'confirm', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSave(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->done($driver, $msg, $user, '🚫 Lançamento cancelado.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirm', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        app(TransactionService::class)->create($user->family, $this->type(), [
            'description' => $data['description'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'due_on' => $data['due_on'],
            'status' => 'pago',
            'user_id' => $data['user_id'],
            'account_id' => $data['account_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ], null, $user->id);

        $this->done($driver, $msg, $user, '✅ '.ucfirst($this->typeLabel(true)).' registrada: <b>'.$data['description'].'</b> ('.BotPresenter::money($data['amount']).').');
    }

    public function list(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $mes = Fin::month();
        $items = app(TransactionService::class)->list($user->family, $this->type(), ['mes' => $mes], 10);
        $total = (float) Transaction::where('family_id', $user->family_id)
            ->where('type', $this->type())->whereIn('status', ['pago', 'pendente'])
            ->whereYear('occurred_on', substr($mes, 0, 4))->whereMonth('occurred_on', substr($mes, 5, 2))->sum('amount');

        $lines = [
            BotPresenter::header($this->typeLabel().' — '.BotPresenter::mesLabel($mes)),
            'Total: <b>'.BotPresenter::money($total).'</b>',
            '',
        ];
        foreach ($items as $t) {
            $lines[] = '• '.$t->description.' — <b>'.BotPresenter::money((float) $t->amount).'</b> · '.Carbon::parse($t->occurred_on)->format('d/m');
        }
        if ($items->isEmpty()) {
            $lines[] = 'Nenhum lançamento no mês.';
        }
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }
}
