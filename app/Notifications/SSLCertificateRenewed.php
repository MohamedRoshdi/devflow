<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SSLCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SSLCertificateRenewed extends Notification
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
        return (new MailMessage)
            ->subject("SSL Certificate Renewed: {$this->certificate->domain_name}")
            ->line("Your SSL certificate for {$this->certificate->domain_name} has been successfully renewed.")
            ->line("**Domain:** {$this->certificate->domain_name}")
            ->line("**Server:** {$this->certificate->server->name}")
            ->line("**New Expiry Date:** {$this->certificate->expires_at->format('Y-m-d H:i')}")
            ->line("**Days Until Expiry:** {$this->certificate->daysUntilExpiry()} days")
            ->action('View Certificate', route('ssl-certificates.show', $this->certificate->id))
            ->line('Your website will continue to be secure with the renewed certificate.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'ssl_renewed',
            'certificate_id' => $this->certificate->id,
            'domain' => $this->certificate->domain_name,
            'server_id' => $this->certificate->server_id,
            'server_name' => $this->certificate->server->name,
            'expires_at' => $this->certificate->expires_at,
            'days_until_expiry' => $this->certificate->daysUntilExpiry(),
        ];
    }
}
