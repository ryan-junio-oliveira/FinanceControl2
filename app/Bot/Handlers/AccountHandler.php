<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\Bank;
use App\Models\User;
use App\Services\AccountService;

class AccountHandler extends BotHandler
{
    private const KINDS = ['corrente' => 'Corrente', 'poupanca' => 'Poupança', 'digital' => 'Digital', 'investimento' => 'Investimento', 'carteira' => 'Carteira'];

    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $text = trim($msg->text);
        $low = mb_strtolower($text);
        $step = $state['step'] ?? '';
        $data = $state['data'] ?? [];

        if ($step === 'menu') {
            if (str_ends_with($low, ':list') || $low === 'ver') {
                $this->show($driver, $msg, $user);

                return;
            }
            if (str_ends_with($low, ':new') || $low === 'cadastrar') {
                $this->startFlow($driver, $msg);

                return;
            }
            $this->menu($driver, $msg, $user);

            return;
        }

        match ($step) {
            'name' => $this->stepBank($driver, $msg, $data, $text),
            'bank' => $this->askBank($driver, $msg, $data, $text),
            'bankchoice' => $this->stepBankChoice($driver, $msg, $data, $text),
            'kind' => $this->stepBalance($driver, $msg, $data, $text),
            'balance' => $this->stepConfirm($driver, $msg, $data, $text, $user),
            'confirm' => $this->stepSave($driver, $msg, $data, $text, $user),
            default => $this->menu($driver, $msg, $user),
        };
    }

    public function menu(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        ConversationState::clear($msg->channel, $msg->chatId);
        $driver->sendText(
            $msg->chatId,
            '🏦 <b>Contas — o que deseja?</b>',
            MenuHandler::submenuKeyboard([
                '🔍 Ver contas' => 'contas:list',
                '➕ Cadastrar conta' => 'contas:new',
            ])
        );
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $contas = app(AccountService::class)->list($user->group);
        $total = 0.0;
        $lines = [BotPresenter::header('Contas'), ''];
        foreach ($contas as $c) {
            $total += (float) $c->balance;
            $lines[] = '🏦 <b>'.e($c->name).'</b>';
            $lines[] = '   '.($c->bank->name ?? '—').' · <b>'.BotPresenter::money((float) $c->balance).'</b>';
        }
        $lines[] = '';
        $lines[] = BotPresenter::divider();
        $lines[] = 'Total: <b>'.BotPresenter::money($total).'</b>';
        if ($contas->isEmpty()) {
            $lines[] = 'Nenhuma conta cadastrada.';
        }
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }

    public function startFlow(BotDriver $driver, IncomingMessage $msg): void
    {
        $this->ask($driver, $msg, 'name', [], '🏦 <b>Nova conta</b>'."\n\n".'Qual o nome da conta? (ex.: Nubank)', $this->cancelKeyboard());
    }

    private function stepBank(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $name = trim($text);
        if ($name === '') {
            $this->ask($driver, $msg, 'name', $data, 'Informe um nome válido:', $this->cancelKeyboard());

            return;
        }
        $data['name'] = mb_substr($name, 0, 255);
        $this->ask(
            $driver,
            $msg,
            'bank',
            $data,
            '🏦 Qual o banco? Digite parte do nome ou o código (ex.: itau, nubank, 341):',
            $this->cancelKeyboard()
        );
    }

    private function askBank(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $q = trim($text);
        $banks = app(AccountService::class)->activeBanks()
            ->filter(fn ($b) => str_contains(mb_strtolower($b->name), mb_strtolower($q)) || str_starts_with((string) $b->code, $q))
            ->values();

        if ($banks->count() === 1) {
            $data['bank_id'] = $banks->first()->id;
            $this->askKind($driver, $msg, $data);

            return;
        }
        if ($banks->count() > 1) {
            $this->ask(
                $driver,
                $msg,
                'bankchoice',
                $data + ['_banks' => $banks->pluck('id')->all()],
                'Encontrei vários bancos. Escolha:',
                $this->selectKeyboard($banks->pluck('name')->all())
            );

            return;
        }
        $this->ask(
            $driver,
            $msg,
            'bank',
            $data,
            '❌ Nenhum banco com "'.e($q).'". Tente outra parte do nome ou o código (ex.: 341):',
            $this->cancelKeyboard()
        );
    }

    private function stepBankChoice(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $ids = $data['_banks'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'bankchoice', $data, '❌ Escolha o número do banco:', $this->cancelKeyboard());

            return;
        }
        $data['bank_id'] = $ids[$num - 1];
        unset($data['_banks']);
        $this->askKind($driver, $msg, $data);
    }

    private function askKind(BotDriver $driver, IncomingMessage $msg, array $data): void
    {
        $this->ask(
            $driver,
            $msg,
            'kind',
            $data + ['_kinds' => array_keys(self::KINDS)],
            '📁 Qual o tipo da conta?',
            $this->selectKeyboard(array_values(self::KINDS))
        );
    }

    private function stepBalance(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $kinds = $data['_kinds'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($kinds[$num - 1])) {
            $this->ask($driver, $msg, 'kind', $data, '❌ Escolha o número do tipo:', $this->cancelKeyboard());

            return;
        }
        $data['kind'] = $kinds[$num - 1];
        unset($data['_kinds']);
        $this->ask($driver, $msg, 'balance', $data, '💵 Qual o saldo inicial? (ex.: 1500,75 ou 0)', $this->cancelKeyboard());
    }

    private function stepConfirm(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $raw = trim($text);
        $amount = preg_match('/^0([.,]0+)?$/', $raw) ? 0.0 : BotPresenter::parseAmount($raw);
        if ($amount === null) {
            $this->ask($driver, $msg, 'balance', $data, '❌ Valor inválido. Digite como 1500,75:', $this->cancelKeyboard());

            return;
        }
        $data['initial_balance'] = $amount;

        $bank = Bank::find($data['bank_id']);
        $summary = '🧾 <b>Confirmar nova conta?</b>'."\n"
            .BotPresenter::divider()."\n"
            .'🏦 '.e($data['name'])."\n"
            .'🏛️ '.($bank->name ?? '—').' · '.self::KINDS[$data['kind']]."\n"
            .'💵 Saldo inicial: <b>'.BotPresenter::money($amount).'</b>';
        $this->ask($driver, $msg, 'confirm', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSave(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->done($driver, $msg, $user, '🚫 Cadastro cancelado.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirm', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        app(AccountService::class)->create($user->group, [
            'name' => $data['name'],
            'bank_id' => $data['bank_id'],
            'kind' => $data['kind'],
            'initial_balance' => $data['initial_balance'],
            'active' => true,
        ]);

        $this->done($driver, $msg, $user, '✅ Conta <b>'.e($data['name']).'</b> cadastrada!');
    }
}
