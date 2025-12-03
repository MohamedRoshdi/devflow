<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'enabled',
        'auto_deploy_branches',
        'skip_patterns',
        'deploy_patterns',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'auto_deploy_branches' => 'array',
            'skip_patterns' => 'array',
            'deploy_patterns' => 'array',
        ];
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Determine if a deployment should be triggered based on branch and commit message
     *
     * @param string $branch The branch that was pushed to
     * @param string $commitMessage The commit message
     * @return bool Whether deployment should proceed
     */
    public function shouldDeploy(string $branch, string $commitMessage): bool
    {
        // If pipeline config is disabled, don't deploy
        if (!$this->enabled) {
            return false;
        }

        // Check if branch is in auto_deploy_branches list
        $autoBranches = $this->auto_deploy_branches ?? [];
        if (!empty($autoBranches) && !in_array($branch, $autoBranches)) {
            return false;
        }

        // Check skip patterns first (highest priority)
        $skipPatterns = $this->skip_patterns ?? [];
        foreach ($skipPatterns as $pattern) {
            if ($this->matchesPattern($commitMessage, $pattern)) {
                return false; // Skip deployment
            }
        }

        // Check deploy patterns (force deploy even if other conditions might fail)
        $deployPatterns = $this->deploy_patterns ?? [];
        foreach ($deployPatterns as $pattern) {
            if ($this->matchesPattern($commitMessage, $pattern)) {
                return true; // Force deployment
            }
        }

        // If no deploy patterns matched and we have deploy patterns configured,
        // default to true (allow deployment)
        return true;
    }

    /**
     * Check if a commit message matches a pattern
     *
     * @param string $commitMessage
     * @param string $pattern
     * @return bool
     */
    private function matchesPattern(string $commitMessage, string $pattern): bool
    {
        // Case-insensitive substring match
        return stripos($commitMessage, $pattern) !== false;
    }

    /**
     * Generate a unique webhook secret for this pipeline
     *
     * @return string
     */
    public function generateWebhookSecret(): string
    {
        return bin2hex(random_bytes(32)); // 64-character hex string
    }
}
