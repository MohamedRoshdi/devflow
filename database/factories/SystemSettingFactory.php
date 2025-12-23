<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        $types = ['string', 'boolean', 'integer', 'json'];
        $groups = ['general', 'auth', 'features', 'mail', 'security'];

        return [
            'key' => fake()->unique()->word() . '.' . fake()->word(),
            'value' => fake()->word(),
            'type' => fake()->randomElement($types),
            'group' => fake()->randomElement($groups),
            'label' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_public' => fake()->boolean(),
            'is_encrypted' => false,
        ];
    }

    public function stringType(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'string',
            'value' => fake()->word(),
        ]);
    }

    public function booleanType(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => fake()->boolean() ? 'true' : 'false',
        ]);
    }

    public function integerType(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 1000),
        ]);
    }

    public function jsonType(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'json',
            'value' => json_encode(['key' => fake()->word()]),
        ]);
    }

    public function encrypted(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_encrypted' => true,
        ]);
    }

    public function public(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function inGroup(string $group): self
    {
        return $this->state(fn (array $attributes) => [
            'group' => $group,
        ]);
    }
}
