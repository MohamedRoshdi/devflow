<?php

namespace Database\Factories;

use App\Models\DeploymentScript;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeploymentScript>
 */
class DeploymentScriptFactory extends Factory
{
    protected $model = DeploymentScript::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $language = fake()->randomElement(['bash', 'sh', 'python', 'php', 'node', 'ruby']);

        $scriptContent = match($language) {
            'bash', 'sh' => 'echo "Deployment script running..."',
            'python' => 'print("Deployment script running...")',
            'php' => '<?php echo "Deployment script running...";',
            'node' => 'console.log("Deployment script running...");',
            'ruby' => 'puts "Deployment script running..."',
        };

        return [
            'name' => fake()->words(3, true).' Script',
            'language' => $language,
            'script' => $scriptContent,
            'variables' => [],
            'run_as' => 'www-data',
            'timeout' => fake()->randomElement([60, 300, 600, 900]),
            'is_template' => fake()->boolean(20),
            'tags' => fake()->optional()->randomElements(['deployment', 'backup', 'maintenance', 'cleanup'], fake()->numberBetween(1, 3)),
        ];
    }

    /**
     * Indicate that the script is a bash script.
     */
    public function bash(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'bash',
            'script' => '#!/bin/bash'."\n".'echo "Bash script"',
        ]);
    }

    /**
     * Indicate that the script is a Python script.
     */
    public function python(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'python',
            'script' => '#!/usr/bin/env python3'."\n".'print("Python script")',
        ]);
    }

    /**
     * Indicate that the script is a PHP script.
     */
    public function php(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'php',
            'script' => '#!/usr/bin/env php'."\n".'<?php echo "PHP script";',
        ]);
    }

    /**
     * Indicate that the script is a template.
     */
    public function template(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_template' => true,
        ]);
    }

    /**
     * Create a script with custom variables.
     */
    public function withVariables(array $variables): static
    {
        return $this->state(fn (array $attributes) => [
            'variables' => $variables,
        ]);
    }

    /**
     * Create a script with specific timeout.
     */
    public function withTimeout(int $timeout): static
    {
        return $this->state(fn (array $attributes) => [
            'timeout' => $timeout,
        ]);
    }
}
