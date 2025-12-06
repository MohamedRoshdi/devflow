<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $github_connection_id
 * @property int|null $project_id
 * @property int $repo_id
 * @property string $name
 * @property string $full_name
 * @property string|null $description
 * @property bool $private
 * @property string $default_branch
 * @property string $clone_url
 * @property string $ssh_url
 * @property string $html_url
 * @property string|null $language
 * @property int $stars_count
 * @property int $forks_count
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read GitHubConnection $connection
 * @property-read Project|null $project
 * @property-read string $language_color
 *
 * @method static \Illuminate\Database\Eloquent\Builder|GitHubRepository newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GitHubRepository newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GitHubRepository query()
 */
class GitHubRepository extends Model
{
    /** @use HasFactory<\Database\Factories\GitHubRepositoryFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'github_repositories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'github_connection_id',
        'project_id',
        'repo_id',
        'name',
        'full_name',
        'description',
        'private',
        'default_branch',
        'clone_url',
        'ssh_url',
        'html_url',
        'language',
        'stars_count',
        'forks_count',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'private' => 'boolean',
        'stars_count' => 'integer',
        'forks_count' => 'integer',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the GitHub connection that owns the repository.
     *
     * @return BelongsTo<GitHubConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(GitHubConnection::class, 'github_connection_id');
    }

    /**
     * Get the project linked to this repository.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if the repository is linked to a project.
     */
    public function isLinked(): bool
    {
        return $this->project_id !== null;
    }

    /**
     * Get language color for badge.
     */
    public function getLanguageColorAttribute(): string
    {
        return match ($this->language) {
            'PHP' => 'bg-purple-500',
            'JavaScript' => 'bg-yellow-500',
            'TypeScript' => 'bg-blue-500',
            'Python' => 'bg-blue-600',
            'Ruby' => 'bg-red-500',
            'Go' => 'bg-cyan-500',
            'Rust' => 'bg-orange-600',
            'Java' => 'bg-orange-500',
            'C#' => 'bg-green-600',
            'Swift' => 'bg-orange-400',
            'Kotlin' => 'bg-purple-600',
            default => 'bg-gray-500',
        };
    }
}
