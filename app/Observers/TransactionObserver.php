<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $this->log($transaction, 'created', $transaction->getAttributes());
    }

    public function updated(Transaction $transaction): void
    {
        $changes = [];
        foreach ($transaction->getChanges() as $field => $new) {
            if (in_array($field, ['updated_at'], true)) {
                continue;
            }
            $changes[$field] = ['de' => $transaction->getOriginal($field), 'para' => $new];
        }
        if ($changes === []) {
            return;
        }
        $this->log($transaction, 'updated', $changes);
    }

    public function deleted(Transaction $transaction): void
    {
        $this->log($transaction, 'deleted', $transaction->getAttributes());
    }

    private function log(Transaction $transaction, string $action, mixed $changes): void
    {
        AuditLog::create([
            'family_id' => $transaction->family_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => Transaction::class,
            'auditable_id' => $transaction->id,
            'changes' => is_array($changes) ? $changes : ['dados' => $changes],
        ]);
    }
}
