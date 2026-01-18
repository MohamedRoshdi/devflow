<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface AuditServiceInterface
{
    /**
     * Log an action on a model
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog;

    /**
     * Get audit logs for a specific model
     *
     * @return Collection<int, AuditLog>
     */
    public function getLogsForModel(Model $model, int $limit = 50): Collection;

    /**
     * Get audit logs by user
     *
     * @return Collection<int, AuditLog>
     */
    public function getLogsByUser(User $user, int $limit = 100): Collection;

    /**
     * Get audit logs by action
     *
     * @return Collection<int, AuditLog>
     */
    public function getLogsByAction(string $action, int $limit = 100): Collection;

    /**
     * Get audit logs with filters
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AuditLog>
     */
    public function getLogsFiltered(array $filters = []): Collection;
}
