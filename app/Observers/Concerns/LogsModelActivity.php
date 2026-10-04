<?php

namespace App\Observers\Concerns;

use App\Support\Audit;
use App\Support\Dashboard;
use Illuminate\Database\Eloquent\Model;

/**
 * Hooks padrão de auditoria para observers de modelo:
 * created / updated (com diff) / deleted, via Audit.
 */
trait LogsModelActivity
{
    public function created(Model $model): void
    {
        Audit::model($model, 'created', $model->getAttributes());
        self::bustDashboard($model);
    }

    public function updated(Model $model): void
    {
        $changes = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, ['updated_at'], true)) {
                continue;
            }
            $changes[$field] = ['de' => $model->getOriginal($field), 'para' => $new];
        }
        if ($changes !== []) {
            Audit::model($model, 'updated', $changes);
        }
        self::bustDashboard($model);
    }

    public function deleted(Model $model): void
    {
        Audit::model($model, 'deleted', $model->getAttributes());
        self::bustDashboard($model);
    }

    private static function bustDashboard(Model $model): void
    {
        $fid = $model->getAttribute('group_id');
        if (is_numeric($fid)) {
            Dashboard::forgetGroup((int) $fid);
        }
    }
}
