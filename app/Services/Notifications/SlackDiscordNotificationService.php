<?php

namespace App\Services\Notifications;

use App\Models\Deployment;
use App\Models\Domain;
use App\Models\NotificationChannel;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackDiscordNotificationService
{
    /**
     * Slack color codes for different notification types
     *
     * @var array<string, string>
     */
    protected array $colors = [
        'success' => '#36a64f',  // Green
        'warning' => '#ff9800',  // Orange
        'error' => '#f44336',    // Red
        'info' => '#2196f3',     // Blue
        'pending' => '#9e9e9e',  // Gray
    ];

    /**
     * Discord color codes (decimal) for different notification types
     *
     * @var array<string, int>
     */
    protected array $discordColors = [
        'success' => 3066993,  // Green
        'warning' => 16753920, // Orange
        'error' => 15158332,   // Red
        'info' => 3447003,     // Blue
        'pending' => 10197915, // Gray
    ];

    /**
     * Send notification to all configured channels
     */
    public function sendNotification(string $type, array $data): void
    {
        $channels = NotificationChannel::where('enabled', true)->get();

        foreach ($channels as $channel) {
            try {
                match ($channel->provider) {
                    'slack' => $this->sendSlackNotification($channel, $type, $data),
                    'discord' => $this->sendDiscordNotification($channel, $type, $data),
                    'teams' => $this->sendTeamsNotification($channel, $type, $data),
                    'webhook' => $this->sendWebhookNotification($channel, $type, $data),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error("Failed to send notification to {$channel->name}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Send Slack notification
     */
    protected function sendSlackNotification(NotificationChannel $channel, string $type, array $data): void
    {
        $payload = match ($type) {
            'deployment_started' => $this->buildSlackDeploymentStarted($data),
            'deployment_completed' => $this->buildSlackDeploymentCompleted($data),
            'deployment_failed' => $this->buildSlackDeploymentFailed($data),
            'rollback_completed' => $this->buildSlackRollbackCompleted($data),
            'health_check_failed' => $this->buildSlackHealthCheckFailed($data),
            'ssl_expiring' => $this->buildSlackSSLExpiring($data),
            'storage_warning' => $this->buildSlackStorageWarning($data),
            'security_alert' => $this->buildSlackSecurityAlert($data),
            default => $this->buildSlackGenericNotification($type, $data),
        };

        Http::post($channel->webhook_url, $payload);
    }

    /**
     * Build Slack deployment started message
     */
    protected function buildSlackDeploymentStarted(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':rocket:',
            'attachments' => [
                [
                    'color' => $this->colors['info'],
                    'title' => "Deployment Started: {$project->name}",
                    'title_link' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
                    'fields' => [
                        [
                            'title' => 'Project',
                            'value' => $project->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Branch',
                            'value' => $deployment->branch,
                            'short' => true,
                        ],
                        [
                            'title' => 'Commit',
                            'value' => substr($deployment->commit_hash, 0, 7),
                            'short' => true,
                        ],
                        [
                            'title' => 'Triggered By',
                            'value' => $deployment->triggered_by,
                            'short' => true,
                        ],
                    ],
                    'footer' => 'DevFlow Pro',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build Slack deployment completed message
     */
    protected function buildSlackDeploymentCompleted(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        $duration = $deployment->completed_at->diffForHumans($deployment->started_at, true);

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':white_check_mark:',
            'attachments' => [
                [
                    'color' => $this->colors['success'],
                    'title' => "Deployment Successful: {$project->name}",
                    'title_link' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
                    'text' => "Deployment completed successfully in {$duration}",
                    'fields' => [
                        [
                            'title' => 'Project',
                            'value' => $project->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Environment',
                            'value' => ucfirst($project->environment ?? 'production'),
                            'short' => true,
                        ],
                        [
                            'title' => 'Version',
                            'value' => substr($deployment->commit_hash, 0, 7),
                            'short' => true,
                        ],
                        [
                            'title' => 'Duration',
                            'value' => $duration,
                            'short' => true,
                        ],
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'View Deployment',
                            'url' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
                        ],
                        [
                            'type' => 'button',
                            'text' => 'View Application',
                            'url' => "https://{$project->domains->first()->full_domain}",
                        ],
                    ],
                    'footer' => 'DevFlow Pro',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build Slack deployment failed message
     */
    protected function buildSlackDeploymentFailed(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':x:',
            'attachments' => [
                [
                    'color' => $this->colors['error'],
                    'title' => "Deployment Failed: {$project->name}",
                    'title_link' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
                    'text' => '<!channel> Deployment failed and requires attention',
                    'fields' => [
                        [
                            'title' => 'Project',
                            'value' => $project->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Branch',
                            'value' => $deployment->branch,
                            'short' => true,
                        ],
                        [
                            'title' => 'Error',
                            'value' => substr($deployment->error_message, 0, 200),
                            'short' => false,
                        ],
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'View Logs',
                            'url' => url("/projects/{$project->id}/deployments/{$deployment->id}/logs"),
                            'style' => 'danger',
                        ],
                        [
                            'type' => 'button',
                            'text' => 'Rollback',
                            'url' => url("/projects/{$project->id}/rollback"),
                        ],
                    ],
                    'footer' => 'DevFlow Pro',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Send Discord notification
     */
    protected function sendDiscordNotification(NotificationChannel $channel, string $type, array $data): void
    {
        $embed = match ($type) {
            'deployment_started' => $this->buildDiscordDeploymentStarted($data),
            'deployment_completed' => $this->buildDiscordDeploymentCompleted($data),
            'deployment_failed' => $this->buildDiscordDeploymentFailed($data),
            'rollback_completed' => $this->buildDiscordRollbackCompleted($data),
            'health_check_failed' => $this->buildDiscordHealthCheckFailed($data),
            'ssl_expiring' => $this->buildDiscordSSLExpiring($data),
            'storage_warning' => $this->buildDiscordStorageWarning($data),
            'security_alert' => $this->buildDiscordSecurityAlert($data),
            default => $this->buildDiscordGenericNotification($type, $data),
        };

        Http::post($channel->webhook_url, [
            'username' => 'DevFlow Pro',
            'avatar_url' => asset('images/devflow-logo.png'),
            'embeds' => [$embed],
        ]);
    }

    /**
     * Build Discord deployment started embed
     */
    protected function buildDiscordDeploymentStarted(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        return [
            'title' => "🚀 Deployment Started: {$project->name}",
            'url' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
            'color' => $this->discordColors['info'],
            'fields' => [
                [
                    'name' => 'Project',
                    'value' => $project->name,
                    'inline' => true,
                ],
                [
                    'name' => 'Branch',
                    'value' => $deployment->branch,
                    'inline' => true,
                ],
                [
                    'name' => 'Commit',
                    'value' => '`'.substr($deployment->commit_hash, 0, 7).'`',
                    'inline' => true,
                ],
                [
                    'name' => 'Triggered By',
                    'value' => $deployment->triggered_by,
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build Discord deployment completed embed
     */
    protected function buildDiscordDeploymentCompleted(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];
        $duration = $deployment->completed_at->diffForHumans($deployment->started_at, true);

        return [
            'title' => "✅ Deployment Successful: {$project->name}",
            'url' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
            'description' => "Deployment completed successfully in {$duration}",
            'color' => $this->discordColors['success'],
            'fields' => [
                [
                    'name' => 'Project',
                    'value' => $project->name,
                    'inline' => true,
                ],
                [
                    'name' => 'Environment',
                    'value' => ucfirst($project->environment ?? 'production'),
                    'inline' => true,
                ],
                [
                    'name' => 'Version',
                    'value' => '`'.substr($deployment->commit_hash, 0, 7).'`',
                    'inline' => true,
                ],
                [
                    'name' => 'Duration',
                    'value' => $duration,
                    'inline' => true,
                ],
                [
                    'name' => 'Application URL',
                    'value' => "[View Application](https://{$project->domains->first()->full_domain})",
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build Discord deployment failed embed
     */
    protected function buildDiscordDeploymentFailed(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        return [
            'title' => "❌ Deployment Failed: {$project->name}",
            'url' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
            'description' => '@everyone Deployment failed and requires immediate attention',
            'color' => $this->discordColors['error'],
            'fields' => [
                [
                    'name' => 'Project',
                    'value' => $project->name,
                    'inline' => true,
                ],
                [
                    'name' => 'Branch',
                    'value' => $deployment->branch,
                    'inline' => true,
                ],
                [
                    'name' => 'Error Message',
                    'value' => '```'.substr($deployment->error_message, 0, 500).'```',
                    'inline' => false,
                ],
                [
                    'name' => 'Actions',
                    'value' => '[View Logs]('.url("/projects/{$project->id}/deployments/{$deployment->id}/logs").') | '.
                               '[Rollback]('.url("/projects/{$project->id}/rollback").')',
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro - Urgent',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build Discord health check failed embed
     */
    protected function buildDiscordHealthCheckFailed(array $data): array
    {
        $project = $data['project'];
        $healthData = $data['health_data'];

        return [
            'title' => "🏥 Health Check Failed: {$project->name}",
            'url' => url("/projects/{$project->id}/health"),
            'description' => 'Health check detected issues with the application',
            'color' => $this->discordColors['warning'],
            'fields' => [
                [
                    'name' => 'Project',
                    'value' => $project->name,
                    'inline' => true,
                ],
                [
                    'name' => 'Status',
                    'value' => $healthData['status'] ?? 'Unknown',
                    'inline' => true,
                ],
                [
                    'name' => 'Response Time',
                    'value' => ($healthData['response_time'] ?? 'N/A').'ms',
                    'inline' => true,
                ],
                [
                    'name' => 'Failed Checks',
                    'value' => implode("\n", array_map(fn ($check) => "• {$check}", $healthData['failed_checks'] ?? [])),
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro Health Monitor',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build Discord SSL expiring embed
     */
    protected function buildDiscordSSLExpiring(array $data): array
    {
        $domain = $data['domain'];
        $daysUntilExpiry = $data['days_until_expiry'];

        $urgency = match (true) {
            $daysUntilExpiry <= 7 => '🔴 Critical',
            $daysUntilExpiry <= 14 => '🟠 High',
            $daysUntilExpiry <= 30 => '🟡 Medium',
            default => '🔵 Low',
        };

        return [
            'title' => '🔒 SSL Certificate Expiring Soon',
            'description' => "SSL certificate for {$domain->full_domain} expires in {$daysUntilExpiry} days",
            'color' => $daysUntilExpiry <= 7 ? $this->discordColors['error'] : $this->discordColors['warning'],
            'fields' => [
                [
                    'name' => 'Domain',
                    'value' => $domain->full_domain,
                    'inline' => true,
                ],
                [
                    'name' => 'Urgency',
                    'value' => $urgency,
                    'inline' => true,
                ],
                [
                    'name' => 'Expires On',
                    'value' => $domain->ssl_expires_at->format('Y-m-d H:i'),
                    'inline' => true,
                ],
                [
                    'name' => 'Action Required',
                    'value' => '[Renew Certificate]('.url("/domains/{$domain->id}/ssl/renew").')',
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro SSL Monitor',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Send Teams notification
     */
    protected function sendTeamsNotification(NotificationChannel $channel, string $type, array $data): void
    {
        $card = match ($type) {
            'deployment_started' => $this->buildTeamsDeploymentCard($data, 'started'),
            'deployment_completed' => $this->buildTeamsDeploymentCard($data, 'completed'),
            'deployment_failed' => $this->buildTeamsDeploymentCard($data, 'failed'),
            default => $this->buildTeamsGenericCard($type, $data),
        };

        Http::post($channel->webhook_url, $card);
    }

    /**
     * Build Teams deployment card
     */
    protected function buildTeamsDeploymentCard(array $data, string $status): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        $color = match ($status) {
            'started' => '0076D7',
            'completed' => '00C853',
            'failed' => 'D32F2F',
            default => '757575',
        };

        $title = match ($status) {
            'started' => '🚀 Deployment Started',
            'completed' => '✅ Deployment Successful',
            'failed' => '❌ Deployment Failed',
            default => 'Deployment Update',
        };

        return [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'themeColor' => $color,
            'summary' => "{$title}: {$project->name}",
            'sections' => [
                [
                    'activityTitle' => $title,
                    'activitySubtitle' => $project->name,
                    'facts' => [
                        [
                            'name' => 'Project',
                            'value' => $project->name,
                        ],
                        [
                            'name' => 'Branch',
                            'value' => $deployment->branch,
                        ],
                        [
                            'name' => 'Commit',
                            'value' => substr($deployment->commit_hash, 0, 7),
                        ],
                        [
                            'name' => 'Status',
                            'value' => ucfirst($status),
                        ],
                    ],
                    'markdown' => true,
                ],
            ],
            'potentialAction' => [
                [
                    '@type' => 'OpenUri',
                    'name' => 'View Deployment',
                    'targets' => [
                        [
                            'os' => 'default',
                            'uri' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Send custom webhook notification
     */
    protected function sendWebhookNotification(NotificationChannel $channel, string $type, array $data): void
    {
        $payload = [
            'event' => $type,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
            'source' => 'DevFlow Pro',
        ];

        // Sign payload if secret is configured
        if ($channel->webhook_secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $channel->webhook_secret);

            Http::withHeaders([
                'X-DevFlow-Signature' => $signature,
            ])->post($channel->webhook_url, $payload);
        } else {
            Http::post($channel->webhook_url, $payload);
        }
    }

    /**
     * Send deployment notification
     */
    public function notifyDeployment(Deployment $deployment, string $status): void
    {
        $project = $deployment->project;

        $data = [
            'deployment' => $deployment,
            'project' => $project,
        ];

        match ($status) {
            'started' => $this->sendNotification('deployment_started', $data),
            'completed' => $this->sendNotification('deployment_completed', $data),
            'failed' => $this->sendNotification('deployment_failed', $data),
            'rolled_back' => $this->sendNotification('rollback_completed', $data),
            default => null,
        };
    }

    /**
     * Send health check notification
     */
    public function notifyHealthCheck(Project $project, array $healthData): void
    {
        if ($healthData['status'] !== 'healthy') {
            $this->sendNotification('health_check_failed', [
                'project' => $project,
                'health_data' => $healthData,
            ]);
        }
    }

    /**
     * Send SSL expiry notification
     */
    public function notifySSLExpiry(Domain $domain, int $daysUntilExpiry): void
    {
        $this->sendNotification('ssl_expiring', [
            'domain' => $domain,
            'days_until_expiry' => $daysUntilExpiry,
        ]);
    }

    /**
     * Send storage warning notification
     */
    public function notifyStorageWarning(Project $project, array $storageData): void
    {
        $this->sendNotification('storage_warning', [
            'project' => $project,
            'storage_data' => $storageData,
        ]);
    }

    /**
     * Send security alert notification
     */
    public function notifySecurityAlert(string $message, string $severity = 'high'): void
    {
        $this->sendNotification('security_alert', [
            'message' => $message,
            'severity' => $severity,
            'timestamp' => now(),
        ]);
    }

    /**
     * Build generic Slack notification
     */
    protected function buildSlackGenericNotification(string $type, array $data): array
    {
        return [
            'username' => 'DevFlow Pro',
            'text' => "Event: {$type}",
            'attachments' => [
                [
                    'color' => $this->colors['info'],
                    'title' => ucwords(str_replace('_', ' ', $type)),
                    'text' => json_encode($data, JSON_PRETTY_PRINT),
                    'footer' => 'DevFlow Pro',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build generic Discord notification
     */
    protected function buildDiscordGenericNotification(string $type, array $data): array
    {
        return [
            'title' => ucwords(str_replace('_', ' ', $type)),
            'description' => '```json\n'.json_encode($data, JSON_PRETTY_PRINT).'\n```',
            'color' => $this->discordColors['info'],
            'footer' => [
                'text' => 'DevFlow Pro',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build generic Teams card
     */
    protected function buildTeamsGenericCard(string $type, array $data): array
    {
        return [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'themeColor' => '0076D7',
            'summary' => ucwords(str_replace('_', ' ', $type)),
            'sections' => [
                [
                    'activityTitle' => ucwords(str_replace('_', ' ', $type)),
                    'text' => json_encode($data, JSON_PRETTY_PRINT),
                ],
            ],
        ];
    }

    /**
     * Test notification channel
     */
    public function testChannel(NotificationChannel $channel): bool
    {
        try {
            $testData = [
                'message' => 'This is a test notification from DevFlow Pro',
                'timestamp' => now()->toIso8601String(),
            ];

            match ($channel->provider) {
                'slack' => Http::post($channel->webhook_url, [
                    'text' => '🔧 Test notification from DevFlow Pro',
                ]),
                'discord' => Http::post($channel->webhook_url, [
                    'content' => '🔧 Test notification from DevFlow Pro',
                ]),
                'teams' => Http::post($channel->webhook_url, [
                    '@type' => 'MessageCard',
                    '@context' => 'https://schema.org/extensions',
                    'text' => '🔧 Test notification from DevFlow Pro',
                ]),
                default => Http::post($channel->webhook_url, $testData),
            };

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to test notification channel: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Build rollback completed Slack notification
     */
    protected function buildSlackRollbackCompleted(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':arrow_backward:',
            'attachments' => [
                [
                    'color' => $this->colors['warning'],
                    'title' => "Rollback Completed: {$project->name}",
                    'title_link' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
                    'text' => 'Successfully rolled back to previous version',
                    'fields' => [
                        [
                            'title' => 'Project',
                            'value' => $project->name,
                            'short' => true,
                        ],
                        [
                            'title' => 'Rolled Back To',
                            'value' => substr($deployment->commit_hash, 0, 7),
                            'short' => true,
                        ],
                    ],
                    'footer' => 'DevFlow Pro',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build rollback completed Discord notification
     */
    protected function buildDiscordRollbackCompleted(array $data): array
    {
        $deployment = $data['deployment'];
        $project = $data['project'];

        return [
            'title' => "↩️ Rollback Completed: {$project->name}",
            'url' => url("/projects/{$project->id}/deployments/{$deployment->id}"),
            'description' => 'Successfully rolled back to previous version',
            'color' => $this->discordColors['warning'],
            'fields' => [
                [
                    'name' => 'Project',
                    'value' => $project->name,
                    'inline' => true,
                ],
                [
                    'name' => 'Rolled Back To',
                    'value' => '`'.substr($deployment->commit_hash, 0, 7).'`',
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build health check failed Slack notification
     */
    protected function buildSlackHealthCheckFailed(array $data): array
    {
        $project = $data['project'];
        $healthData = $data['health_data'];

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':hospital:',
            'attachments' => [
                [
                    'color' => $this->colors['warning'],
                    'title' => "Health Check Failed: {$project->name}",
                    'title_link' => url("/projects/{$project->id}/health"),
                    'fields' => [
                        [
                            'title' => 'Status',
                            'value' => $healthData['status'] ?? 'Unknown',
                            'short' => true,
                        ],
                        [
                            'title' => 'Response Time',
                            'value' => ($healthData['response_time'] ?? 'N/A').'ms',
                            'short' => true,
                        ],
                        [
                            'title' => 'Failed Checks',
                            'value' => implode(', ', $healthData['failed_checks'] ?? []),
                            'short' => false,
                        ],
                    ],
                    'footer' => 'DevFlow Pro Health Monitor',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build SSL expiring Slack notification
     */
    protected function buildSlackSSLExpiring(array $data): array
    {
        $domain = $data['domain'];
        $daysUntilExpiry = $data['days_until_expiry'];

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':lock:',
            'attachments' => [
                [
                    'color' => $daysUntilExpiry <= 7 ? $this->colors['error'] : $this->colors['warning'],
                    'title' => 'SSL Certificate Expiring Soon',
                    'text' => "Certificate for {$domain->full_domain} expires in {$daysUntilExpiry} days",
                    'fields' => [
                        [
                            'title' => 'Domain',
                            'value' => $domain->full_domain,
                            'short' => true,
                        ],
                        [
                            'title' => 'Days Until Expiry',
                            'value' => $daysUntilExpiry,
                            'short' => true,
                        ],
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'Renew Certificate',
                            'url' => url("/domains/{$domain->id}/ssl/renew"),
                        ],
                    ],
                    'footer' => 'DevFlow Pro SSL Monitor',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build storage warning Slack notification
     */
    protected function buildSlackStorageWarning(array $data): array
    {
        $project = $data['project'];
        $storageData = $data['storage_data'];

        $usagePercentage = ($storageData['used'] / $storageData['total']) * 100;

        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':floppy_disk:',
            'attachments' => [
                [
                    'color' => $usagePercentage > 90 ? $this->colors['error'] : $this->colors['warning'],
                    'title' => "Storage Warning: {$project->name}",
                    'text' => "Storage usage is at {$usagePercentage}%",
                    'fields' => [
                        [
                            'title' => 'Used',
                            'value' => $this->formatBytes($storageData['used']),
                            'short' => true,
                        ],
                        [
                            'title' => 'Total',
                            'value' => $this->formatBytes($storageData['total']),
                            'short' => true,
                        ],
                    ],
                    'actions' => [
                        [
                            'type' => 'button',
                            'text' => 'Clean Up Storage',
                            'url' => url("/projects/{$project->id}/storage/cleanup"),
                        ],
                    ],
                    'footer' => 'DevFlow Pro Storage Monitor',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build security alert Slack notification
     */
    protected function buildSlackSecurityAlert(array $data): array
    {
        return [
            'username' => 'DevFlow Pro',
            'icon_emoji' => ':warning:',
            'attachments' => [
                [
                    'color' => $this->colors['error'],
                    'title' => 'Security Alert',
                    'text' => $data['message'],
                    'fields' => [
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($data['severity']),
                            'short' => true,
                        ],
                        [
                            'title' => 'Time',
                            'value' => $data['timestamp']->format('Y-m-d H:i:s'),
                            'short' => true,
                        ],
                    ],
                    'footer' => 'DevFlow Pro Security',
                    'ts' => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * Build storage warning Discord notification
     */
    protected function buildDiscordStorageWarning(array $data): array
    {
        $project = $data['project'];
        $storageData = $data['storage_data'];
        $usagePercentage = ($storageData['used'] / $storageData['total']) * 100;

        return [
            'title' => "💾 Storage Warning: {$project->name}",
            'description' => "Storage usage has reached {$usagePercentage}%",
            'color' => $usagePercentage > 90 ? $this->discordColors['error'] : $this->discordColors['warning'],
            'fields' => [
                [
                    'name' => 'Used',
                    'value' => $this->formatBytes($storageData['used']),
                    'inline' => true,
                ],
                [
                    'name' => 'Total',
                    'value' => $this->formatBytes($storageData['total']),
                    'inline' => true,
                ],
                [
                    'name' => 'Action',
                    'value' => '[Clean Up Storage]('.url("/projects/{$project->id}/storage/cleanup").')',
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro Storage Monitor',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build security alert Discord notification
     */
    protected function buildDiscordSecurityAlert(array $data): array
    {
        return [
            'title' => '⚠️ Security Alert',
            'description' => $data['message'],
            'color' => $this->discordColors['error'],
            'fields' => [
                [
                    'name' => 'Severity',
                    'value' => strtoupper($data['severity']),
                    'inline' => true,
                ],
                [
                    'name' => 'Time',
                    'value' => $data['timestamp']->format('Y-m-d H:i:s'),
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'DevFlow Pro Security',
                'icon_url' => asset('images/devflow-icon.png'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
