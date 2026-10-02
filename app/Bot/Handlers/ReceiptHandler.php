<?php

namespace App\Bot\Handlers;

use App\Bot\BotPresenter;
use App\Bot\Contracts\BotDriver;
use App\Bot\ValueObjects\IncomingMessage;
use App\Models\User;
use App\Receipts\ReceiptManager;
use App\Receipts\ReceiptParser;
use App\Services\AccountService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Support\CategoryKeywords;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

/**
 * Fluxo de comprovante: foto/PDF → leitura → pergunta o que faltar → confirma → cria com anexo.
 */
class ReceiptHandler extends BotHandler
{
    public function handle(BotDriver $driver, IncomingMessage $msg, ?array $state, ?User $user): void
    {
        $step = $state['step'] ?? 'download';
        $data = $state['data'] ?? [];

        match ($step) {
            'download' => $this->stepDownload($driver, $msg, $user),
            'manual', 'kind' => $this->stepKind($driver, $msg, $data, $msg->text, $user),
            'amount' => $this->stepAmount($driver, $msg, $data, $msg->text, $user),
            'date' => $this->stepDate($driver, $msg, $data, $msg->text, $user),
            'kind' => $this->stepKind($driver, $msg, $data, $msg->text, $user),
            'desc' => $this->stepDesc($driver, $msg, $data, $msg->text, $user),
            'account' => $this->stepAccount($driver, $msg, $data, $msg->text, $user),
            'category' => $this->stepCategory($driver, $msg, $data, $msg->text, $user),
            'confirm' => $this->stepSave($driver, $msg, $data, $msg->text, $user),
            default => $this->showMenu($driver, $msg, $user),
        };
    }

    private function stepDownload(BotDriver $driver, IncomingMessage $msg, ?User $user): void
    {
        $driver->sendText($msg->chatId, '🔍 Lendo o comprovante, um instante...');
        $path = $msg->fileId ? $driver->downloadFile($msg->fileId) : null;
        $mime = $msg->fileMime ?? ($path ? (string) mime_content_type($path) : null);

        $text = '';
        if ($path && $mime && ReceiptManager::reader()->supports($mime)) {
            $text = ReceiptManager::reader()->read($path);
        }

        if ($path && trim($text) === '') {
            $this->askManual($driver, $msg, '❌ Não consegui ler esse arquivo. Quer lançar manualmente?', $path, $mime);

            return;
        }
        if (! $path) {
            $this->askManual($driver, $msg, '❌ Não consegui baixar o arquivo. Quer lançar manualmente?', null, null);

            return;
        }

        $draft = ReceiptParser::parse($text);
        $data = [
            'path' => $path,
            'mime' => $mime,
            'bank' => $draft['bank'],
            'channel' => $draft['channel'],
            'amount' => $draft['amount'],
            'date' => $draft['date'],
            'type' => $draft['type'],
            'description' => $draft['description'],
        ];

        $this->nextMissing($driver, $msg, $data, $user);
    }

    private function askManual(BotDriver $driver, IncomingMessage $msg, string $text, ?string $path, ?string $mime): void
    {
        if ($path) {
            @unlink($path);
        }
        $this->ask($driver, $msg, 'manual', [], $text.' (despesa ou receita?)', MenuHandler::submenuKeyboard([
            '💸 Despesa' => 'manual:despesa',
            '💰 Receita' => 'manual:receita',
        ]));
    }

    /** Avança para o primeiro campo faltante. */
    private function nextMissing(BotDriver $driver, IncomingMessage $msg, array $data, ?User $user): void
    {
        if (empty($data['amount'])) {
            $this->ask($driver, $msg, 'amount', $data, '💵 Qual o valor? (ex.: 150,50)', $this->cancelKeyboard());

            return;
        }
        if (empty($data['date'])) {
            $this->ask($driver, $msg, 'date', $data, '📅 Qual a data? Digite <b>hoje</b> ou DD/MM:', $this->cancelKeyboard());

            return;
        }
        if (empty($data['type'])) {
            $this->ask($driver, $msg, 'kind', $data, '↔️ Foi despesa ou receita?', MenuHandler::submenuKeyboard([
                '💸 Despesa' => 'kind:despesa',
                '💰 Receita' => 'kind:receita',
            ]));

            return;
        }
        $this->askDesc($driver, $msg, $data);
    }

    private function stepAmount(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $amount = BotPresenter::parseAmount($text);
        if ($amount === null) {
            $this->ask($driver, $msg, 'amount', $data, '❌ Valor inválido. Digite como 150,50:', $this->cancelKeyboard());

            return;
        }
        $data['amount'] = $amount;
        $this->nextMissing($driver, $msg, $data, $user);
    }

    private function stepDate(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $date = BotPresenter::parseDate($text);
        if ($date === null) {
            $this->ask($driver, $msg, 'date', $data, '❌ Data inválida. Digite <b>hoje</b> ou DD/MM:', $this->cancelKeyboard());

            return;
        }
        $data['date'] = $date;
        $this->nextMissing($driver, $msg, $data, $user);
    }

    private function stepKind(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $low = mb_strtolower(trim($text));
        if (str_contains($low, 'despesa') || $low === 'manual:despesa') {
            $data['type'] = 'despesa';
        } elseif (str_contains($low, 'receita') || $low === 'manual:receita') {
            $data['type'] = 'receita';
        } elseif ($low === 'manual') {
            $this->askManual($driver, $msg, 'É despesa ou receita?', $data['path'] ?? null, $data['mime'] ?? null);

            return;
        } else {
            $this->ask($driver, $msg, 'kind', $data, 'Toque em 💸 Despesa ou 💰 Receita:', MenuHandler::submenuKeyboard([
                '💸 Despesa' => 'kind:despesa',
                '💰 Receita' => 'kind:receita',
            ]));

            return;
        }
        $this->nextMissing($driver, $msg, $data, $user);
    }

    private function askDesc(BotDriver $driver, IncomingMessage $msg, array $data): void
    {
        $this->ask($driver, $msg, 'desc', $data, '📝 Descrição: <b>'.e($data['description'] ?? '—')."</b>\n\nEnvie <b>ok</b> para manter ou digite uma nova.", $this->cancelKeyboard());
    }

    private function stepDesc(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $low = mb_strtolower(trim($text));
        if (! in_array($low, ['ok', 'manter', 'sim', 's'], true)) {
            $desc = trim($text);
            if ($desc === '') {
                $this->askDesc($driver, $msg, $data);

                return;
            }
            $data['description'] = mb_substr($desc, 0, 120);
        }
        if (empty($data['description'])) {
            $this->ask($driver, $msg, 'desc', $data, 'Digite uma descrição:', $this->cancelKeyboard());

            return;
        }

        $accounts = app(AccountService::class)->list($user->family)->values();
        if ($accounts->isEmpty()) {
            $this->done($driver, $msg, $user, '❌ Você ainda não tem contas. Crie uma no sistema primeiro.');

            return;
        }
        $lines = $accounts->map(fn ($a) => $a->name)->all();
        $this->ask($driver, $msg, 'account', $data + ['_accounts' => $accounts->pluck('id')->all()], '🏦 Qual a conta?', $this->selectKeyboard($lines));
    }

    private function stepAccount(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_accounts'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'account', $data, '❌ Escolha o número da conta:', $this->cancelKeyboard());

            return;
        }
        $data['account_id'] = $ids[$num - 1];
        unset($data['_accounts']);

        $type = $data['type'] ?? 'despesa';
        $cats = app(CategoryService::class)->list($user->family, ['tipo' => $type], 100);
        $suggested = CategoryKeywords::guessName($data['description'] ?? '');
        $suggestedId = null;
        if ($suggested) {
            $suggestedId = $user->family->categories()->where('type', $type)->where('name', $suggested)->value('id');
        }
        $lines = [];
        foreach ($cats as $c) {
            $lines[] = $c->name.($suggestedId && (int) $c->id === (int) $suggestedId ? ' ⭐' : '');
        }
        $this->ask($driver, $msg, 'category', $data + ['_categories' => $cats->pluck('id')->all()], '🏷️ Qual a categoria?', $this->selectKeyboard($lines));
    }

    private function stepCategory(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        $ids = $data['_categories'] ?? [];
        $num = self::num($text);
        if ($num === null || ! isset($ids[$num - 1])) {
            $this->ask($driver, $msg, 'category', $data, '❌ Escolha o número da categoria:', $this->cancelKeyboard());

            return;
        }
        $data['category_id'] = $ids[$num - 1];
        unset($data['_categories']);

        $account = $user->family->accounts()->find($data['account_id']);
        $cat = $user->family->categories()->find($data['category_id']);
        $extra = [];
        if (! empty($data['bank'])) {
            $extra[] = '🏛️ '.$data['bank'];
        }
        if (! empty($data['channel']) && $data['channel'] !== 'outro') {
            $extra[] = ucfirst($data['channel']);
        }
        $summary = '🧾 <b>Confirmar '.($data['type'] === 'receita' ? 'receita' : 'despesa')."?</b>\n"
            .BotPresenter::divider()."\n"
            .'📝 '.$data['description']."\n"
            .'💵 <b>'.BotPresenter::money((float) $data['amount']).'</b> · '.Carbon::parse($data['date'])->format('d/m/Y')."\n"
            .'🏦 '.($account->name ?? '—').' · 🏷️ '.($cat->name ?? '—')
            .($extra ? "\n".implode(' · ', $extra) : '')
            ."\n📎 Comprovante anexado.";
        $this->ask($driver, $msg, 'confirm', $data, $summary, $this->confirmKeyboard());
    }

    private function stepSave(BotDriver $driver, IncomingMessage $msg, array $data, string $text, ?User $user): void
    {
        if (self::isNo($text)) {
            $this->cleanup($data);
            $this->done($driver, $msg, $user, '🚫 Lançamento cancelado.');

            return;
        }
        if (! self::isYes($text)) {
            $this->ask($driver, $msg, 'confirm', $data, 'Confirma? Toque em ✅ Confirmar ou ❌ Cancelar.', $this->confirmKeyboard());

            return;
        }

        $anexo = null;
        if (! empty($data['path']) && is_file($data['path'])) {
            $anexo = new UploadedFile($data['path'], basename($data['path']), $data['mime'] ?? null, null, true);
        }

        app(TransactionService::class)->create($user->family, $data['type'] ?? 'despesa', [
            'description' => $data['description'],
            'amount' => $data['amount'],
            'occurred_on' => $data['date'],
            'due_on' => $data['date'],
            'status' => 'pago',
            'user_id' => $user->id,
            'account_id' => $data['account_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ], $anexo, $user->id);

        $this->cleanup($data);
        $this->done($driver, $msg, $user, '✅ Lançamento registrado: <b>'.e($data['description']).'</b> ('.BotPresenter::money((float) $data['amount']).').');
    }

    private function cleanup(array $data): void
    {
        if (! empty($data['path']) && is_file($data['path'])) {
            @unlink($data['path']);
        }
    }
}
