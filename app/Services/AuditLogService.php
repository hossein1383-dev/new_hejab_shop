<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * ثبت یک رخداد حساس Admin (بخش ۳۶). هرگز داده حساس (رمز، توکن) اینجا
     * ذخیره نشود — فقط تغییرات فیلدهای معمول Model.
     */
    public function log(?User $user, string $action, Model $model, array $oldValues = [], array $newValues = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
