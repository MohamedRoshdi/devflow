<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    protected $model = Server::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true).' Server',
            'hostname' => fake()->optional()->domainName(),
            'ip_address' => fake()->ipv4(),
            'port' => 22,
            'username' => 'root',
            'ssh_key' => null,
            'ssh_password' => null,
            'status' => fake()->randomElement(['online', 'offline', 'maintenance']),
            'os' => fake()->randomElement(['Linux', 'Ubuntu', 'Debian', 'CentOS']),
            'cpu_cores' => fake()->numberBetween(1, 16),
            'memory_gb' => fake()->numberBetween(1, 64),
            'disk_gb' => fake()->numberBetween(20, 500),
            'docker_installed' => fake()->boolean(70),
            'docker_version' => fake()->optional(0.7)->numerify('##.#.#'),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
            'location_name' => fake()->optional()->city(),
            'last_ping_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    /**
     * Indicate that the server is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_ping_at' => now(),
        ]);
    }

    /**
     * Indicate that the server is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
        ]);
    }

    /**
     * Indicate that the server uses password authentication.
     */
    public function withPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssh_password' => 'test_password_123',
            'ssh_key' => null,
        ]);
    }

    /**
     * Indicate that the server uses SSH key authentication.
     */
    public function withSshKey(): static
    {
        return $this->state(fn (array $attributes) => [
            'ssh_key' => '-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACBHK2rHd48lqfvvGlr+fVZnUfmv0tnFzWN7jAw7E7VUUQAAAJBMR1QOTERW
-----END OPENSSH PRIVATE KEY-----',
            'ssh_password' => null,
        ]);
    }

    /**
     * Indicate that Docker is installed.
     */
    public function withDocker(): static
    {
        return $this->state(fn (array $attributes) => [
            'docker_installed' => true,
            'docker_version' => '24.0.7',
        ]);
    }

    /**
     * Indicate that Docker is not installed.
     */
    public function withoutDocker(): static
    {
        return $this->state(fn (array $attributes) => [
            'docker_installed' => false,
            'docker_version' => null,
        ]);
    }
}
