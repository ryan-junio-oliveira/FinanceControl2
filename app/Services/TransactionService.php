<?php

namespace App\Services;

use App\Jobs\ProcessReceiptOcr;
use App\Models\Attachment;
use App\Models\Group;
use App\Models\Transaction;
use App\Support\Installments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Regras de domínio dos lançamentos (despesas/receitas).
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class TransactionService
{
    public function __construct(
        private readonly CardService $cards,
        private readonly AccountService $accounts,
    ) {}

    /**
     * Ponto único de criação respeitando payment_method (SRP: controller só orquestra).
     * Elimina a divergência web x API — ambos chamam este método.
     *
     * @return array{kind: 'card'|'deposit'|'cash', items?: array, transactions?: Collection<int, Transaction>, message: string}
     */
    public function createForGroup(Group $group, string $type, array $data, ?UploadedFile $anexo, int $actorId): array
    {
        $data['user_id'] = $data['user_id'] ?? $actorId;
        $method = $data['payment_method'] ?? null;

        // Cartão: lança direto na fatura (item pendente).
        if ($type === 'despesa' && $method === 'cartao') {
            $group->creditCards()->findOrFail($data['credit_card_id'] ?? 0);
            $item = $this->cards->createItem($group, [
                'credit_card_id' => $data['credit_card_id'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'occurred_on' => $data['occurred_on'],
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'] ?? null,
                'installments_total' => $data['installments_total'] ?? 1,
            ]);

            return [
                'kind' => 'card',
                'items' => $item,
                'message' => $item['parcelas'] > 1 ? "Compra parcelada em {$item['parcelas']}x na fatura." : 'Compra lançada na fatura do cartão.',
            ];
        }

        // Dinheiro físico: sempre na conta "carteira".
        if ($method === 'dinheiro_fisico') {
            $data['account_id'] = $this->accounts->dinheiroFisico($group)->id;
        }

        // Dinheiro digital / depósito: exige conta não-carteira.
        if (in_array($method, ['dinheiro_digital', 'deposito'], true)) {
            $conta = $group->accounts()->where('active', true)->where('kind', '!=', 'carteira')->find($data['account_id'] ?? null)
                ?? $group->accounts()->where('active', true)->where('kind', '!=', 'carteira')->orderBy('name')->first();
            abort_if(! $conta, 422, $method === 'deposito' ? 'Escolha a conta bancária de destino do depósito.' : 'Crie uma conta corrente/digital para lançar dinheiro digital.');
            $data['account_id'] = $conta->id;

            if ($type === 'receita' && $method === 'deposito') {
                $this->accounts->transfer($group, [
                    'from_account_id' => $this->accounts->dinheiroFisico($group)->id,
                    'to_account_id' => $conta->id,
                    'user_id' => $data['user_id'],
                    'amount' => $data['amount'],
                    'occurred_on' => $data['occurred_on'],
                    'description' => $data['description'] ?: 'Depósito (dinheiro físico → conta)',
                ]);

                return ['kind' => 'deposit', 'message' => 'Depósito registrado.'];
            }
        }

        // Sanitiza mass-assignment: type/group são definidos pelo servidor.
        unset($data['type'], $data['group_id']);
        $criados = $this->create($group, $type, $data, $anexo, $actorId);
        $parcelas = $criados->first()->installments_total ?? 1;

        return [
            'kind' => 'cash',
            'transactions' => $criados,
            'message' => $parcelas > 1 ? "Lançamento parcelado em {$parcelas}x." : 'Lançamento registrado.',
        ];
    }

    public function list(Group $group, string $type, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = Transaction::ofGroup($group->id)->where('type', $type)->with(['member', 'category', 'account']);

        if (! empty($filters['q'])) {
            $q->where('description', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['status']) && in_array($filters['status'], ['pago', 'pendente', 'agendado'], true)) {
            $q->where('status', $filters['status']);
        }
        if (isset($filters['fixa']) && in_array($filters['fixa'], ['0', '1'], true)) {
            $q->where('is_fixed', $filters['fixa']);
        }
        if (! empty($filters['categoria'])) {
            $q->where('category_id', $filters['categoria']);
        }
        if (! empty($filters['membro'])) {
            $q->where('user_id', $filters['membro']);
        }

        return $q->inMonth($filters['mes'])->orderByDesc('occurred_on')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Cria um lançamento (ou as parcelas). Retorna a coleção de criados.
     *
     * @return Collection<int, Transaction>
     */
    public function create(Group $group, string $type, array $data, ?UploadedFile $anexo, int $actorId): Collection
    {
        if (! empty($data['account_id'])) {
            $group->accounts()->findOrFail($data['account_id']);
        }
        if (! empty($data['category_id'])) {
            $cat = $group->categories()->findOrFail($data['category_id']);
            abort_if($cat->type !== $type, 422, 'Categoria de outro tipo.');
        }

        $parcelas = max(1, min(48, (int) ($data['installments_total'] ?? 1)));
        unset($data['installments_total']);
        $data['user_id'] = $data['user_id'] ?? $actorId;
        $data['is_fixed'] = (bool) ($data['is_fixed'] ?? false);
        $data['source'] = 'cash';
        unset($data['credit_card_id']);

        $criados = collect();
        DB::transaction(function () use ($group, $data, $type, $parcelas, $criados) {
            if ($parcelas === 1) {
                $criados->push(Transaction::create($data + [
                    'group_id' => $group->id,
                    'type' => $type,
                ]));

                return;
            }

            // Parcelado via helper compartilhado (centavos, sem drift).
            foreach (Installments::split((float) $data['amount'], $parcelas, $data['occurred_on'], $data['due_on'] ?? null, $data['description'], $data['status'] ?? 'pendente', 'pendente') as $p) {
                $criados->push(Transaction::create($data + [
                    'group_id' => $group->id,
                    'type' => $type,
                    'is_fixed' => false,
                    'description' => $p['description'],
                    'amount' => $p['amount'],
                    'occurred_on' => $p['occurred_on'],
                    'due_on' => $p['due_on'],
                    'status' => $p['status'] ?? $data['status'],
                    'installment_group_id' => $p['installment_group_id'],
                    'installment_number' => $p['installment_number'],
                    'installments_total' => $p['installments_total'],
                ]));
            }
        });

        if ($anexo && $criados->isNotEmpty()) {
            $this->guardarAnexo($anexo, $group->id, $criados->first(), $actorId);
        }

        return $criados;
    }

    public function update(Transaction $transaction, Group $group, array $data, ?UploadedFile $anexo, int $actorId): Transaction
    {
        $data['user_id'] = $data['user_id'] ?? $transaction->user_id;
        $group->users()->findOrFail($data['user_id']);
        if (! empty($data['account_id'])) {
            $group->accounts()->findOrFail($data['account_id']);
        }
        if (! empty($data['category_id'])) {
            $cat = $group->categories()->findOrFail($data['category_id']);
            abort_if($cat->type !== $transaction->type, 422, 'Categoria de outro tipo.');
        }
        $data['is_fixed'] = (bool) ($data['is_fixed'] ?? false);
        unset($data['credit_card_id']);

        $transaction->update($data);

        if ($anexo) {
            $this->guardarAnexo($anexo, $group->id, $transaction, $actorId);
        }

        return $transaction->refresh();
    }

    public function destroy(Transaction $transaction): void
    {
        $transaction->attachments()->each(fn ($a) => $a->delete());
        $transaction->delete();
    }

    /** Marca como pago/recebido. */
    public function settle(Transaction $transaction): Transaction
    {
        $transaction->update(['status' => 'pago', 'due_on' => $transaction->due_on ?? $transaction->occurred_on]);

        return $transaction->refresh();
    }

    public function guardarAnexo(UploadedFile $file, int $groupId, Transaction $transaction, int $actorId): Attachment
    {
        $path = $file->store("anexos/{$groupId}", 'local');

        $attachment = $transaction->attachments()->create([
            'group_id' => $groupId,
            'user_id' => $actorId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'ocr_status' => 'queued',
        ]);

        // OCR pesado roda no worker (fila ocr); a resposta HTTP volta imediatamente.
        ProcessReceiptOcr::dispatch($attachment->id)->onQueue('ocr');

        return $attachment;
    }

    public function attachmentPath(Attachment $attachment): string
    {
        abort_if(! Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->path($attachment->path);
    }
}
