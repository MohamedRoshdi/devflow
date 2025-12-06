<?php

namespace Database\Factories;

use App\Models\SSHKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SSHKey>
 */
class SSHKeyFactory extends Factory
{
    protected $model = SSHKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['ed25519', 'rsa', 'ecdsa']);

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true).' SSH Key',
            'type' => $type,
            'public_key' => $this->generateMockPublicKey($type),
            'private_key_encrypted' => null,
            'fingerprint' => $this->generateMockFingerprint(),
            'expires_at' => fake()->optional(0.3)->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Indicate that the SSH key has a private key.
     */
    public function withPrivateKey(): static
    {
        return $this->state(function (array $attributes) {
            $privateKey = $this->generateMockPrivateKey($attributes['type']);

            return [
                'private_key_encrypted' => Crypt::encryptString($privateKey),
            ];
        });
    }

    /**
     * Indicate that the SSH key is of type RSA.
     */
    public function rsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'rsa',
            'public_key' => $this->generateMockPublicKey('rsa'),
        ]);
    }

    /**
     * Indicate that the SSH key is of type ED25519.
     */
    public function ed25519(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ed25519',
            'public_key' => $this->generateMockPublicKey('ed25519'),
        ]);
    }

    /**
     * Indicate that the SSH key is of type ECDSA.
     */
    public function ecdsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ecdsa',
            'public_key' => $this->generateMockPublicKey('ecdsa'),
        ]);
    }

    /**
     * Indicate that the SSH key is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Generate a mock public key.
     */
    private function generateMockPublicKey(string $type): string
    {
        $keyData = base64_encode(random_bytes(256));
        $comment = 'devflow-test-'.fake()->uuid();

        return match ($type) {
            'ed25519' => "ssh-ed25519 {$keyData} {$comment}",
            'rsa' => "ssh-rsa {$keyData} {$comment}",
            'ecdsa' => "ecdsa-sha2-nistp521 {$keyData} {$comment}",
            default => "ssh-rsa {$keyData} {$comment}",
        };
    }

    /**
     * Generate a mock private key.
     */
    private function generateMockPrivateKey(string $type): string
    {
        $keyContent = base64_encode(random_bytes(1024));

        return match ($type) {
            'ed25519' => "-----BEGIN OPENSSH PRIVATE KEY-----\n{$keyContent}\n-----END OPENSSH PRIVATE KEY-----",
            'rsa' => "-----BEGIN RSA PRIVATE KEY-----\n{$keyContent}\n-----END RSA PRIVATE KEY-----",
            'ecdsa' => "-----BEGIN EC PRIVATE KEY-----\n{$keyContent}\n-----END EC PRIVATE KEY-----",
            default => "-----BEGIN RSA PRIVATE KEY-----\n{$keyContent}\n-----END RSA PRIVATE KEY-----",
        };
    }

    /**
     * Generate a mock fingerprint.
     */
    private function generateMockFingerprint(): string
    {
        $parts = [];
        for ($i = 0; $i < 16; $i++) {
            $parts[] = fake()->hexColor();
        }

        return implode(':', array_map(fn ($p) => substr($p, 1, 2), $parts));
    }
}
