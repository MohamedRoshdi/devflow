<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\SSLCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SSLCertificate>
 */
class SSLCertificateFactory extends Factory
{
    protected $model = SSLCertificate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = fake()->dateTimeBetween('-90 days', 'now');
        $expiresAt = (clone $issuedAt)->modify('+90 days');

        return [
            'domain_id' => null,
            'server_id' => Server::factory(),
            'domain' => fake()->domainName(),
            'provider' => fake()->randomElement(['letsencrypt', 'cloudflare', 'custom', 'self-signed']),
            'status' => fake()->randomElement(['issued', 'pending', 'failed', 'expired']),
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'auto_renew' => fake()->boolean(80),
            'certificate_path' => '/etc/ssl/certs/'.fake()->slug().'.crt',
            'private_key_path' => '/etc/ssl/private/'.fake()->slug().'.key',
            'certificate_content' => '-----BEGIN CERTIFICATE-----'."\n".fake()->sha256()."\n".'-----END CERTIFICATE-----',
            'error_message' => null,
        ];
    }

    /**
     * Indicate that the certificate is active/issued.
     */
    public function issued(): static
    {
        return $this->state(function (array $attributes) {
            $issuedAt = now()->subDays(30);

            return [
                'status' => 'issued',
                'issued_at' => $issuedAt,
                'expires_at' => $issuedAt->copy()->addDays(90),
                'error_message' => null,
            ];
        });
    }

    /**
     * Indicate that the certificate is expiring soon (within 7 days).
     */
    public function expiringSoon(): static
    {
        return $this->state(function (array $attributes) {
            $expiresAt = now()->addDays(fake()->numberBetween(1, 7));

            return [
                'status' => 'issued',
                'issued_at' => $expiresAt->copy()->subDays(90),
                'expires_at' => $expiresAt,
            ];
        });
    }

    /**
     * Indicate that the certificate is expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $expiresAt = now()->subDays(fake()->numberBetween(1, 30));

            return [
                'status' => 'expired',
                'issued_at' => $expiresAt->copy()->subDays(90),
                'expires_at' => $expiresAt,
            ];
        });
    }

    /**
     * Indicate that the certificate issuance failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'issued_at' => null,
            'expires_at' => null,
            'error_message' => fake()->randomElement([
                'Domain validation failed',
                'Rate limit exceeded',
                'DNS challenge failed',
            ]),
        ]);
    }
}
