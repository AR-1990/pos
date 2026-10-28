<?php

namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    /**
     * Record who did what. $model is optional (e.g. the Sale that was voided).
     */
    public static function log(string $action, string $description, $model = null, array $meta = [], $storeId = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'store_id' => $storeId ?? ($model->store_id ?? auth()->user()?->store_id),
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'description' => $description,
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
