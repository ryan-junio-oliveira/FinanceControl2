<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CardService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Support\Fin;
use Carbon\Carbon;

abstract class TransactionFlowHandler extends BotHandler
{
    abstract protected function type(): string;

    abstract protected function typeLabel(): string;

    /** Despesas perguntam a forma de pagamento (Pix/TED/Dinheiro/Cartão). */
    protected function asksPaymentMethod(): bool
    {
        return false;
    }

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
            'payment' => $this->stepPayment($driver, $msg, $data, $text, $user),
            'paymentcard' => $this->stepPaymentCard($driver, $msg, $data, $text, $user),
            'account' => $this->stepCategory($driver, $msg, $data, $text, $user),
            'category' => $this->stepCategoryChosen($driver, $msg, $data, $text, $user),
            'fixed' => $this->stepFixedAnswer($driver, $msg, $data, $text, $user),
            'dueday' => $this->stepDueDay($driver, $msg, $data, $text, $user),
            'confirm' => $this->stepSave($driver, $msg, $data, $text, $user),
            'confirmcard' => $this->stepSaveCard($driver, $msg, $data, $text, $user),
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

        $members = $user->group->users()->orderBy('name')->get();
        if ($members->count() <= 1) {
            $data['user_id'] = $user->id;
            $this->afterMember($driver, $msg, $data, $user);

            return;
        }

        $lines = [];
        foreach ($members as $i => $m) {
            $lines[] = ($m->id === $user->id ? $m->name.' (você)' : $m->name);
        }
        $this->ask($driver, $msg, 'member', $data + ['_members' => $members->pluck('id')->all()], '👤 Responsável?', $this->selectKeyboard($lines));
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
        $this->afterMember($driver, $msg, $data, $user);
    }

    /** Depois do responsável: despesa pergunta a forma de pagamento. */
    private function afterMember(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        if ($this->asksPaymentMethod()) {
            $this->askPayment($driver, $msg, $data);

            return;
        }
        $this->askAccount($driver, $msg, $data, $user);
    }

    private function askPayment(BotDriver $driver, IncomingMessage $msg, array $data): void
    {
        $pergunta = $this->type() === 'despesa' ? '💳 Como você pagou?' : '💵 Como você recebeu?';
        $this->ask($driver, $msg, 'payment', $data, $pergunta, $this->paymentKeyboard());
    }

    private function paymentKeyboard(): BotKeyboard
    {
        $options = [
            '💸 Pix' => 'pix',
            '🏦 TED' => 'ted',
            '💵 Dinheiro físico' => 'dinheiro_fisico',
        ];
        if ($this->type() === 'despesa') {
            $options['💳 Cartão'] = 'cartao';
        } else {
            $options['🏦 Depósito (carteira → conta)'] = 'deposito';
        }

        return BotKeyboard::menu($options);
    }

    private function stepPayment(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $low = mb_strtolower(trim($text));

        if ($this->type() === 'despesa' && in_array($low, ['cartao', 'cartão', 'credito', 'crédito'], true)) {
            $data['payment_method'] = 'cartao';
            $cards = app(CardService::class)->list($user->group);
            if ($cards->isEmpty()) {
                $this->done($driver, $msg, $user, '❌ Você ainda não tem cartões. Cadastre um no menu Cartões.');

                return;
            }
            $this->ask($driver, $msg, 'paymentcard', $data + ['_cards' => $cards->pluck('id')->all()], '💳 Em qual cartão?', $this->selectKeyboard($cards->map(fn ($c) => $c->name)->all()));

            return;
        }

        // "Dinheiro" genérico: pede para escolher físico ou digital.
        if (in_array($low, ['dinheiro', 'cash'], true)) {
            $this->ask($driver, $msg, 'payment', $data, '💵 Físico (espécie) ou digital (Pix/TED)?', $this->paymentKeyboard());

            return;
        }

        $method = match ($low) {
            'pix' => 'pix',
            'ted', 'doc', 'transferencia', 'transferência' => 'ted',
            'dinheiro_fisico', 'fisico', 'físico', 'especie', 'espécie', 'carteira' => 'dinheiro_fisico',
            'deposito', 'depósito' => $this->type() === 'receita' ? 'deposito' : null,
            default => null,
        };
        if ($method === null) {
            $this->ask($driver, $msg, 'payment', $data, 'Escolha uma opção:', $this->paymentKeyboard());

            return;
        }
        $data['payment_method'] = $method;

        // Dinheiro físico: vai para a conta "carteira" (espécie), sem escolher conta.
        if ($method === 'dinheiro_fisico') {
            $data['account_id'] = app(AccountService::class)->dinheiroFisico($user->group)->id;
            $this->askCategory($driver, $msg, $data, $user);

            return;
        }
        $this->askAccount($driver, $msg, $data, $user);
    }

    private function stepPaymentCard(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_cards'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'paymentcard', $data, '❌ Escolha o número do cartão:', $this->cancelKeyboard());

            return;
        }
        $data['credit_card_id'] = $ids[$num - 1];
        unset($data['_cards']);
        $data['_via_cartao'] = true;
        $this->askCategory($driver, $msg, $data, $user);
    }

    private function askAccount(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        $accounts = app(AccountService::class)->list($user->group)->values();
        if (in_array($data['payment_method'] ?? null, ['dinheiro_digital', 'deposito'], true)) {
            $accounts = $accounts->filter(fn ($a) => $a->kind !== 'carteira')->values();
        }
        if ($accounts->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Você ainda não tem contas compatíveis. Crie uma no sistema primeiro.');

            return;
        }
        $lines = $accounts->map(fn ($a) => $a->name)->all();
        $this->ask($driver, $msg, 'account', $data + ['_accounts' => $accounts->pluck('id')->all()], '🏦 Qual a conta?', $this->selectKeyboard($lines));
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
        $this->askCategory($driver, $msg, $data, $user);
    }

    private function askCategory(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        $cats = app(CategoryService::class)->list($user->group, ['tipo' => $this->type()], 100);
        if ($cats->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Sem categorias cadastradas. Crie no sistema primeiro.');

            return;
        }
        $this->ask($driver, $msg, 'category', $data + ['_categories' => $cats->pluck('id')->all()], '🏷️ Qual a categoria?', $this->selectKeyboard($cats->map(fn ($c) => $c->name)->all()));
    }

    private function stepCategoryChosen(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
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

        if (! empty($data['_via_cartao'])) {
            $this->stepConfirmCard($driver, $msg, $data, $text, $user);

            return;
        }

        $this->ask(
            $driver,
            $msg,
            'fixed',
            $data,
            '🔁 É '.$this->typeLabel().' fixa (se repete todo mês)?',
            $this->fixedKeyboard()
        );
    }

    private function stepConfirmCard(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $card = $user->group->creditCards()->find($data['credit_card_id']);
        $cat = $user->group->categories()->find($data['category_id']);
        $member = $user->group->users()->find($data['user_id']);
        $summary = '🧾 <b>Confirmar compra no cartão?</b>'."\n"
            .BotPresenter::divider()."\n"
            .'💳 '.($card->name ?? '—')."\n"
            .'🛒 '.e($data['description'])."\n"
            .'💵 <b>'.BotPresenter::money($data['amount']).'</b> · '.Carbon::parse($data['occurred_on'])->format('d/m/Y')."\n"
            .'🏷️ '.($cat->name ?? '—').' · 👤 '.($member->name ?? '—');
        $this->ask($driver, $msg, 'confirmcard', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSaveCard(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->done($driver, $msg, $user, '🚫 Lançamento cancelado.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirmcard', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        app(CardService::class)->createItem($user->group, [
            'credit_card_id' => $data['credit_card_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'] ?? null,
        ]);

        $card = $user->group->creditCards()->find($data['credit_card_id']);
        $this->done($driver, $msg, $user, '✅ Compra lançada na fatura do <b>'.e($card->name ?? 'cartão').'</b>: <b>'.e($data['description']).'</b> ('.BotPresenter::money($data['amount']).').');
    }

    private function stepFixedAnswer(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $data['is_fixed'] = false;
            $this->stepConfirm($driver, $msg, $data, $text, $user);

            return;
        }
        if (self::isYes($text)) {
            $this->ask(
                $driver,
                $msg,
                'dueday',
                $data,
                '📅 Qual o dia do '.$this->paymentLabel().'? (1-31)',
                $this->cancelKeyboard()
            );

            return;
        }
        $this->ask($driver, $msg, 'fixed', $data, 'Responda ✅ Sim ou ❌ Não:', $this->fixedKeyboard());
    }

    private function stepDueDay(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $text = trim($text);
        if (! preg_match('/^\d{1,2}$/', $text) || (int) $text < 1 || (int) $text > 31) {
            $this->ask($driver, $msg, 'dueday', $data, '❌ Dia inválido. Digite um número de 1 a 31:', $this->cancelKeyboard());

            return;
        }
        $day = (int) $text;
        $base = Carbon::parse($data['occurred_on']);
        $data['is_fixed'] = true;
        $data['due_on'] = $base->copy()->setDay(min($day, $base->daysInMonth))->toDateString();
        $this->stepConfirm($driver, $msg, $data, $text, $user);
    }

    private function stepConfirm(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $member = $user->group->users()->find($data['user_id']);
        $account = $user->group->accounts()->find($data['account_id']);
        $cat = $user->group->categories()->find($data['category_id']);
        $isDeposito = ($data['payment_method'] ?? null) === 'deposito';
        $titulo = $isDeposito ? 'Depósito' : strtolower($this->typeLabel(true));
        $contaLinha = $isDeposito
            ? '💵 Carteira → 🏦 '.e($account->name ?? '—')
            : '👤 '.e($member->name ?? '—').' · 🏦 '.e($account->name ?? '—');
        $fixedLine = ! empty($data['is_fixed'])
            ? "\n🔁 Fixa · ".$this->paymentLabel().' todo dia '.Carbon::parse($data['due_on'])->format('j')
            : "\n🔁 Eventual";
        $summary = '🧾 <b>Confirmar '.$titulo.'?</b>'."\n"
            .BotPresenter::divider()."\n"
            .'📝 '.e($data['description'])."\n"
            .'💵 <b>'.BotPresenter::money($data['amount']).'</b> · '.Carbon::parse($data['occurred_on'])->format('d/m/Y')."\n"
            .$contaLinha."\n"
            .'🏷️ '.e($cat->name ?? '—')
            .$fixedLine;
        $this->ask($driver, $msg, 'confirm', $data, $summary, $this->confirmKeyboard());
    }

    private function fixedKeyboard(): BotKeyboard
    {
        return BotKeyboard::menu(['✅ Sim' => 'sim', '❌ Não' => 'nao']);
    }

    private function paymentLabel(): string
    {
        return $this->type() === 'despesa' ? 'pagamento' : 'recebimento';
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

        // Depósito: dinheiro físico (carteira) entra numa conta bancária.
        if (($data['payment_method'] ?? null) === 'deposito') {
            app(AccountService::class)->transfer($user->group, [
                'from_account_id' => app(AccountService::class)->dinheiroFisico($user->group)->id,
                'to_account_id' => $data['account_id'],
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'occurred_on' => $data['occurred_on'],
                'description' => $data['description'] ?: 'Depósito (dinheiro físico → conta)',
            ]);
            $this->done($driver, $msg, $user, '✅ Depósito registrado: <b>'.e($data['description']).'</b> ('.BotPresenter::money($data['amount']).').');

            return;
        }

        app(TransactionService::class)->create($user->group, $this->type(), [
            'description' => $data['description'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'due_on' => $data['due_on'],
            'is_fixed' => $data['is_fixed'] ?? false,
            'payment_method' => $data['payment_method'] ?? null,
            'status' => 'pago',
            'user_id' => $data['user_id'],
            'account_id' => $data['account_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ], null, $user->id);

        $this->done($driver, $msg, $user, '✅ '.ucfirst($this->typeLabel(true)).' registrada: <b>'.e($data['description']).'</b> ('.BotPresenter::money($data['amount']).').');
    }

    public function list(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $mes = Fin::month();
        $items = app(TransactionService::class)->list($user->group, $this->type(), ['mes' => $mes], 10);
        $total = (float) Transaction::where('group_id', $user->group_id)
            ->where('type', $this->type())->whereIn('status', ['pago', 'pendente'])
            ->whereYear('occurred_on', substr($mes, 0, 4))->whereMonth('occurred_on', substr($mes, 5, 2))->sum('amount');

        $lines = [
            BotPresenter::header($this->typeLabel().' — '.BotPresenter::mesLabel($mes)),
            'Total: <b>'.BotPresenter::money($total).'</b>',
            '',
        ];
        foreach ($items as $t) {
            $lines[] = '• '.e($t->description).' — <b>'.BotPresenter::money((float) $t->amount).'</b> · '.Carbon::parse($t->occurred_on)->format('d/m');
        }
        if ($items->isEmpty()) {
            $lines[] = 'Nenhum lançamento no mês.';
        }
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }
}
