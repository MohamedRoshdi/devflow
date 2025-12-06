<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerProvisioningCompleted extends Notification
{
    use Queueable;

    public function __construct(
        public Server $server,
        public bool $success = true,
        public ?string $errorMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->success) {
            $packages = $this->server->installed_packages;
            $packagesList = is_array($packages) ? implode(', ', $packages) : '';

            return (new MailMessage)
                ->subject("Server Provisioned Successfully: {$this->server->name}")
                ->line("Your server {$this->server->name} has been successfully provisioned.")
                ->line("**Server:** {$this->server->name}")
                ->line("**IP Address:** {$this->server->ip_address}")
                ->line('**Installed Packages:** '.$packagesList)
                ->action('View Server', route('servers.show', $this->server->id))
                ->line('Your server is now ready for deployments!');
        }

        return (new MailMessage)
            ->error()
            ->subject("Server Provisioning Failed: {$this->server->name}")
            ->line("Server provisioning failed for {$this->server->name}.")
            ->line("**Server:** {$this->server->name}")
            ->line("**IP Address:** {$this->server->ip_address}")
            ->line("**Error:** {$this->errorMessage}")
            ->action('View Server', route('servers.show', $this->server->id))
            ->line('Please check the provisioning logs for more details.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->success ? 'provisioning_completed' : 'provisioning_failed',
            'server_id' => $this->server->id,
            'server_name' => $this->server->name,
            'ip_address' => $this->server->ip_address,
            'installed_packages' => $this->server->installed_packages,
            'success' => $this->success,
            'error_message' => $this->errorMessage,
        ];
    }
}
