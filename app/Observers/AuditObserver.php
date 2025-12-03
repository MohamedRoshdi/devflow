<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function created(Model $model): void
    {
        $modelName = strtolower(class_basename($model));
        $this->auditService->log(
            "{$modelName}.created",
            $model,
            null,
            $model->getAttributes()
        );
    }

    public function updated(Model $model): void
    {
        $modelName = strtolower(class_basename($model));
        $this->auditService->log(
            "{$modelName}.updated",
            $model,
            $model->getOriginal(),
            $model->getChanges()
        );
    }

    public function deleted(Model $model): void
    {
        $modelName = strtolower(class_basename($model));
        $this->auditService->log(
            "{$modelName}.deleted",
            $model,
            $model->getAttributes(),
            null
        );
    }

    public function restored(Model $model): void
    {
        $modelName = strtolower(class_basename($model));
        $this->auditService->log(
            "{$modelName}.restored",
            $model,
            null,
            $model->getAttributes()
        );
    }
}
