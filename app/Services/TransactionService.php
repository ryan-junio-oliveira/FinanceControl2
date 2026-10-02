<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Family;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Regras de domínio dos lançamentos (despesas/receitas).
 *
 * Usada pelos controllers web e API — mesma regra, duas apresentações.
 */
final class TransactionService
{
    public function list(Family $family, string $type, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = Transaction::ofFamily($family->id)->where('type', $type)->with(['member', 'category', 'account']);

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
    public function create(Family $family, string $type, array $data, ?UploadedFile $anexo, int $actorId): Collection
    {
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        if (! empty($data['category_id'])) {
            $cat = $family->categories()->findOrFail($data['category_id']);
            abort_if($cat->type !== $type, 422, 'Categoria de outro tipo.');
        }

        $parcelas = max(1, min(48, (int) ($data['installments_total'] ?? 1)));
        unset($data['installments_total']);
        $data['user_id'] = $data['user_id'] ?? $actorId;
        $data['is_fixed'] = (bool) ($data['is_fixed'] ?? false);
        $data['source'] = 'cash';
        unset($data['credit_card_id']);

        $criados = collect();
        DB::transaction(function () use ($family, $data, $type, $parcelas, $criados) {
            if ($parcelas === 1) {
                $criados->push(Transaction::create($data + [
                    'family_id' => $family->id,
                    'type' => $type,
                ]));

                return;
            }

            // Parcelado: divide em centavos (sem drift de float) e vence 1x ao mês.
            $group = (string) Str::uuid();
            $totalCents = (int) round((float) $data['amount'] * 100);
            $base = intdiv($totalCents, $parcelas);
            $resto = $totalCents % $parcelas;
            $baseDate = Carbon::parse($data['occurred_on']);
            $baseDue = ! empty($data['due_on']) ? Carbon::parse($data['due_on']) : null;

            for ($i = 1; $i <= $parcelas; $i++) {
                $cents = $base + ($i <= $resto ? 1 : 0);
                $occ = $baseDate->copy()->addMonthsNoOverflow($i - 1)->toDateString();
                $criados->push(Transaction::create($data + [
                    'family_id' => $family->id,
                    'type' => $type,
                    'is_fixed' => false,
                    'description' => "{$data['description']} ({$i}/{$parcelas})",
                    'amount' => $cents / 100,
                    'occurred_on' => $occ,
                    'due_on' => $baseDue ? $baseDue->copy()->addMonthsNoOverflow($i - 1)->toDateString() : $occ,
                    'status' => $i === 1 ? $data['status'] : 'pendente',
                    'installment_group_id' => $group,
                    'installment_number' => $i,
                    'installments_total' => $parcelas,
                ]));
            }
        });

        if ($anexo && $criados->isNotEmpty()) {
            $this->guardarAnexo($anexo, $family->id, $criados->first(), $actorId);
        }

        return $criados;
    }

    public function update(Transaction $transaction, Family $family, array $data, ?UploadedFile $anexo, int $actorId): Transaction
    {
        $data['user_id'] = $data['user_id'] ?? $transaction->user_id;
        $family->users()->findOrFail($data['user_id']);
        if (! empty($data['account_id'])) {
            $family->accounts()->findOrFail($data['account_id']);
        }
        if (! empty($data['category_id'])) {
            $cat = $family->categories()->findOrFail($data['category_id']);
            abort_if($cat->type !== $transaction->type, 422, 'Categoria de outro tipo.');
        }
        $data['is_fixed'] = (bool) ($data['is_fixed'] ?? false);
        unset($data['credit_card_id']);

        $transaction->update($data);

        if ($anexo) {
            $this->guardarAnexo($anexo, $family->id, $transaction, $actorId);
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

    public function guardarAnexo(UploadedFile $file, int $familyId, Transaction $transaction, int $actorId): Attachment
    {
        $path = $file->store("anexos/{$familyId}", 'local');

        return $transaction->attachments()->create([
            'family_id' => $familyId,
            'user_id' => $actorId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function attachmentPath(Attachment $attachment): string
    {
        abort_if(! Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->path($attachment->path);
    }
}
