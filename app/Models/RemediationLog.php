<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RemediationAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property int|null $security_incident_id
 * @property string $action
 * @property string $target
 * @property string|null $command_executed
 * @property string|null $rollback_command
 * @property bool $success
 * @property string|null $output
 * @property string|null $error
 * @property bool $auto_triggered
 * @property int|null $triggered_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Server $server
 * @property-read SecurityIncident|null $securityIncident
 * @property-read User|null $triggeredByUser
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class RemediationLog extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'server_id',
        'security_incident_id',
        'action',
        'target',
        'command_executed',
        'rollback_command',
        'success',
        'output',
        'error',
        'auto_triggered',
        'triggered_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'auto_triggered' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Server, self>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<SecurityIncident, self>
     */
    public function securityIncident(): BelongsTo
    {
        return $this->belongsTo(SecurityIncident::class);
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getActionEnum(): ?RemediationAction
    {
        return RemediationAction::tryFrom($this->action);
    }
}
