<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SSLCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SSLCertificateExpiring extends Notification
{
    use Queueable;

    public function __construct(
        public SSLCertificate $certificate
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft = $this->certificate->daysUntilExpiry();
        $urgency = $this->getUrgencyLevel($daysLeft);

        return (new MailMessage)
            ->subject("SSL Certificate Expiring Soon: {$this->certificate->domain_name}")
            ->line("Your SSL certificate for {$this->certificate->domain_name} is expiring soon.")
            ->line("**Domain:** {$this->certificate->domain_name}")
            ->line("**Server:** {$this->certificate->server->name}")
            ->line("**Expires:** {$this->certificate->expires_at->format('Y-m-d H:i')}")
            ->line("**Days Remaining:** {$daysLeft} {$urgency}")
            ->line($this->certificate->auto_renew
                ? 'Auto-renewal is enabled. The certificate will be renewed automatically.'
                : '**⚠️ Auto-renewal is disabled. You need to renew manually.**')
            ->action('View Certificate', route('ssl-certificates.show', $this->certificate->id))
            ->line('Please ensure your certificate is renewed before expiry to avoid service disruption.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $daysLeft = $this->certificate->daysUntilExpiry();

        return [
            'type' => 'ssl_expiring',
            'certificate_id' => $this->certificate->id,
            'domain' => $this->certificate->domain_name,
            'server_id' => $this->certificate->server_id,
            'server_name' => $this->certificate->server->name,
            'expires_at' => $this->certificate->expires_at,
            'days_until_expiry' => $daysLeft,
            'auto_renew' => $this->certificate->auto_renew,
            'urgency' => $this->getUrgencyLevel($daysLeft),
        ];
    }

    protected function getUrgencyLevel(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null => 'unknown',
            $daysLeft === 0 => '🔴 EXPIRED',
            $daysLeft <= 3 => '🔴 CRITICAL',
            $daysLeft <= 7 => '🟠 URGENT',
            $daysLeft <= 14 => '🟡 WARNING',
            default => '🟢 NOTICE',
        };
    }
}
