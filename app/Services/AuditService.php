<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\{AuditLog, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action on a model
     */
    public function log(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $this->sanitizeValues($oldValues),
            'new_values' => $this->sanitizeValues($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get audit logs for a specific model
     */
    public function getLogsForModel(Model $model, int $limit = 50): Collection
    {
        return AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->id)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by user
     */
    public function getLogsByUser(User $user, int $limit = 100): Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->with('auditable')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by action
     */
    public function getLogsByAction(string $action, int $limit = 100): Collection
    {
        return AuditLog::where('action', $action)
            ->with(['user', 'auditable'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs with filters
     */
    public function getLogsFiltered(array $filters = []): Collection
    {
        $query = AuditLog::with(['user', 'auditable']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        if (isset($filters['action_category'])) {
            $query->where('action', 'like', "{$filters['action_category']}.%");
        }

        if (isset($filters['model_type'])) {
            $query->where('auditable_type', $filters['model_type']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        return $query->latest('created_at')
            ->limit($filters['limit'] ?? 100)
            ->get();
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(array $filters = []): array
    {
        $query = AuditLog::query();

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $total = $query->count();
        $byAction = (clone $query)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        $byUser = (clone $query)
            ->selectRaw('user_id, COUNT(*) as count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->get()
            ->pluck('count', 'user_id')
            ->toArray();

        $byModelType = (clone $query)
            ->selectRaw('auditable_type, COUNT(*) as count')
            ->groupBy('auditable_type')
            ->get()
            ->pluck('count', 'auditable_type')
            ->toArray();

        return [
            'total' => $total,
            'by_action' => $byAction,
            'by_user' => $byUser,
            'by_model_type' => $byModelType,
        ];
    }

    /**
     * Export audit logs to CSV
     */
    public function exportToCsv(array $filters = []): string
    {
        $logs = $this->getLogsFiltered($filters);

        $csv = "ID,User,Action,Model,Model ID,IP Address,Date,Changes\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%d,%s,%s,%s\n",
                $log->id,
                $log->user ? $log->user->name : 'System',
                $log->action,
                $log->model_name,
                $log->auditable_id,
                $log->ip_address ?? 'N/A',
                $log->created_at->format('Y-m-d H:i:s'),
                json_encode($log->changes_summary)
            );
        }

        return $csv;
    }

    /**
     * Sanitize sensitive data from values
     */
    private function sanitizeValues(?array $values): ?array
    {
        if (!$values) {
            return null;
        }

        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'webhook_secret',
            'ssh_key',
            'private_key',
        ];

        $sanitized = $values;

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***REDACTED***';
            }
        }

        return $sanitized;
    }
}
