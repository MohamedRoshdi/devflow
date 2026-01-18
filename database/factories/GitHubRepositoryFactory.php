<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GitHubConnection;
use App\Models\GitHubRepository;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GitHubRepository>
 */
class GitHubRepositoryFactory extends Factory
{
    protected $model = GitHubRepository::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(2);
        $username = fake()->userName();
        $fullName = "{$username}/{$name}";

        return [
            'github_connection_id' => GitHubConnection::factory(),
            'project_id' => null,
            'repo_id' => (string) fake()->unique()->randomNumber(6),
            'name' => $name,
            'full_name' => $fullName,
            'description' => fake()->optional()->sentence(),
            'private' => fake()->boolean(),
            'default_branch' => fake()->randomElement(['main', 'master', 'develop']),
            'clone_url' => "https://github.com/{$fullName}.git",
            'ssh_url' => "git@github.com:{$fullName}.git",
            'html_url' => "https://github.com/{$fullName}",
            'language' => fake()->optional()->randomElement(['PHP', 'JavaScript', 'Python', 'Ruby', 'Go', 'Rust']),
            'stars_count' => fake()->numberBetween(0, 1000),
            'forks_count' => fake()->numberBetween(0, 100),
            'synced_at' => now(),
        ];
    }

    /**
     * Indicate that the repository is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'private' => true,
        ]);
    }

    /**
     * Indicate that the repository is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'private' => false,
        ]);
    }

    /**
     * Indicate that the repository is linked to a project.
     */
    public function linked(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => Project::factory(),
        ]);
    }

    /**
     * Indicate that the repository has a specific language.
     */
    public function withLanguage(string $language): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => $language,
        ]);
    }
}
