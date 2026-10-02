<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ConversationState;
use App\Bot\ValueObjects\BotKeyboard;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\Asset;
use App\Models\User;
use App\Services\AccountService;
use App\Services\InvestmentService;
use Carbon\Carbon;

class InvestmentHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $text = trim($msg->text);
        $low = mb_strtolower($text);
        $step = $state['step'] ?? '';
        $data = $state['data'] ?? [];

        if ($step === 'menu') {
            if (str_ends_with($low, ':list') || $low === 'resumo') {
                $this->show($driver, $msg, $user);

                return;
            }
            if (str_ends_with($low, ':new') || $low === 'lançar' || $low === 'lancar' || $low === 'novo' || $low === 'aporte') {
                $this->startFlow($driver, $msg);

                return;
            }
            $this->menu($driver, $msg, $user);

            return;
        }

        match ($step) {
            'kind' => $this->stepKind($driver, $msg, $data, $text, $user),
            'account' => $this->stepAccount($driver, $msg, $data, $text, $user),
            'asset' => $this->stepAsset($driver, $msg, $data, $text, $user),
            'amount' => $this->stepDate($driver, $msg, $data, $text),
            'date' => $this->stepConfirm($driver, $msg, $data, $text, $user),
            'confirm' => $this->stepSave($driver, $msg, $data, $text, $user),
            default => $this->menu($driver, $msg, $user),
        };
    }

    public function menu(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        ConversationState::clear($msg->channel, $msg->chatId);
        $driver->sendText(
            $msg->chatId,
            '📈 <b>Investimentos — o que deseja?</b>',
            MenuHandler::submenuKeyboard([
                '🔍 Ver resumo' => 'investimentos:list',
                '➕ Lançar aporte' => 'investimentos:new',
            ])
        );
    }

    public function show(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $dados = app(InvestmentService::class)->dashboard($user->family);
        $lines = [
            BotPresenter::header('Investimentos'),
            'Patrimônio: <b>'.BotPresenter::money((float) $dados['patrimonio']).'</b>',
            '',
            '💰 Aportes no mês: <b>'.BotPresenter::money((float) $dados['aportesMes']).'</b>',
            '📈 Rendimentos no mês: <b>'.BotPresenter::money((float) $dados['rendMes']).'</b>',
            '',
            '💡 Aportes e rendimentos pelo menu.',
        ];
        $driver->sendText($msg->chatId, implode("\n", $lines), $this->menuKeyboard());
    }

    public function startFlow(BotDriver $driver, IncomingMessage $msg): void
    {
        $this->ask(
            $driver,
            $msg,
            'kind',
            [],
            '📈 O que você quer registrar?',
            BotKeyboard::menu(['💰 Aporte (sai da conta)' => 'aporte', '📈 Rendimento' => 'rendimento'])
        );
    }

    private function stepKind(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $low = mb_strtolower(trim($text));
        if (in_array($low, ['aporte', '1'], true)) {
            $data['kind'] = 'aporte';
            $this->askAccount($driver, $msg, $data, $user);

            return;
        }
        if (in_array($low, ['rendimento', '2'], true)) {
            $data['kind'] = 'rendimento';
            $this->askAsset($driver, $msg, $data, $user);

            return;
        }
        $this->ask($driver, $msg, 'kind', $data, 'Escolha uma opção:', BotKeyboard::menu(['💰 Aporte (sai da conta)' => 'aporte', '📈 Rendimento' => 'rendimento']));
    }

    private function askAccount(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        $accounts = app(AccountService::class)->list($user->family)->values();
        if ($accounts->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Você ainda não tem contas. Crie uma no sistema primeiro.');

            return;
        }
        $this->ask($driver, $msg, 'account', $data + ['_accounts' => $accounts->pluck('id')->all()], '🏦 De qual conta sai o dinheiro?', $this->selectKeyboard($accounts->map(fn ($a) => $a->name)->all()));
    }

    private function stepAccount(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_accounts'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'account', $data, '❌ Escolha a conta:', $this->selectKeyboard([]));

            return;
        }
        $data['account_id'] = $ids[$num - 1];
        unset($data['_accounts']);
        $this->askAmount($driver, $msg, $data);
    }

    private function askAsset(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        $ativos = Asset::where('family_id', $user->family_id)->orderBy('name')->get();
        if ($ativos->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Nenhum ativo cadastrado. Adicione no sistema primeiro.');

            return;
        }
        $this->ask($driver, $msg, 'asset', $data + ['_assets' => $ativos->pluck('id')->all()], '🏷️ Qual ativo rendeu?', $this->selectKeyboard($ativos->map(fn ($a) => $a->name.' ('.$a->code.')')->all()));
    }

    private function stepAsset(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_assets'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'asset', $data, '❌ Escolha o ativo:', $this->selectKeyboard([]));

            return;
        }
        $data['asset_id'] = $ids[$num - 1];
        unset($data['_assets']);
        $this->askAmount($driver, $msg, $data);
    }

    private function askAmount(BotDriver $driver, IncomingMessage $msg, array $data): void
    {
        $this->ask($driver, $msg, 'amount', $data, '💵 Qual o valor? (ex.: 500)', $this->cancelKeyboard());
    }

    private function stepDate(BotDriver $driver, IncomingMessage $msg, array $data, string $text): void
    {
        $amount = BotPresenter::parseAmount($text);
        if ($amount === null) {
            $this->ask($driver, $msg, 'amount', $data, '❌ Valor inválido. Digite como 500:', $this->cancelKeyboard());

            return;
        }
        $data['amount'] = $amount;
        $this->ask($driver, $msg, 'date', $data, '📅 Qual a data? Digite <b>hoje</b> ou DD/MM:', $this->cancelKeyboard());
    }

    private function stepConfirm(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $date = BotPresenter::parseDate($text);
        if ($date === null) {
            $this->ask($driver, $msg, 'date', $data, '❌ Data inválida. Digite <b>hoje</b> ou DD/MM:', $this->cancelKeyboard());

            return;
        }
        $data['occurred_on'] = $date;

        $isAporte = $data['kind'] === 'aporte';
        $conta = $user->family->accounts()->find($data['account_id'] ?? null);
        $ativo = Asset::where('family_id', $user->family_id)->find($data['asset_id'] ?? 0);
        $summary = '🧾 <b>Confirmar '.($isAporte ? 'aporte' : 'rendimento').'?</b>'."\n"
            .BotPresenter::divider()."\n"
            .($isAporte ? '💰 Aporte' : '📈 Rendimento').' · <b>'.BotPresenter::money($data['amount']).'</b>'."\n"
            .'📅 '.Carbon::parse($date)->format('d/m/Y')."\n"
            .($isAporte ? '🏦 '.($conta->name ?? '—') : '🏷️ '.($ativo->name ?? '—'));
        $this->ask($driver, $msg, 'confirm', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSave(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->done($driver, $msg, $user, '🚫 Operação cancelada.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirm', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        app(InvestmentService::class)->createContribution($user->family, [
            'kind' => $data['kind'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'account_id' => $data['account_id'] ?? null,
            'asset_id' => $data['asset_id'] ?? null,
        ], $user->id);

        $isAporte = $data['kind'] === 'aporte';
        $this->done($driver, $msg, $user, '✅ '.($isAporte ? 'Aporte' : 'Rendimento').' registrado: <b>'.BotPresenter::money($data['amount']).'</b>.');
    }
}
