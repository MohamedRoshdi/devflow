<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\DeploymentComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeploymentComment>
 */
class DeploymentCommentFactory extends Factory
{
    protected $model = DeploymentComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deployment_id' => Deployment::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'mentions' => [],
        ];
    }

    public function withMentions(array $userIds = []): static
    {
        return $this->state(fn (array $attributes) => [
            'mentions' => $userIds,
            'content' => fake()->paragraph().' @user mentioned here',
        ]);
    }
}
