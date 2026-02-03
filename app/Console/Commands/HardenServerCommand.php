<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Security\ServerHardeningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HardenServerCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'security:harden
                            {server : The server ID to harden}
                            {--ssh-port= : Change SSH port (e.g., 2222)}
                            {--firewall : Setup UFW firewall}
                            {--fail2ban : Install and configure fail2ban}
                            {--sysctl : Apply kernel hardening}
                            {--full : Apply all hardening measures}';

    /**
     * @var string
     */
    protected $description = 'Apply security hardening to a server (SSH, firewall, fail2ban, sysctl)';

    public function handle(ServerHardeningService $hardeningService): int
    {
        $serverId = $this->argument('server');
        $server = Server::find($serverId);

        if (! $server) {
            $this->error("Server with ID {$serverId} not found.");

            return Command::FAILURE;
        }

        $this->info("Hardening server: {$server->name} ({$server->ip_address})");

        $options = [];
        $full = (bool) $this->option('full');

        // Determine what to harden
        if ($this->option('ssh-port')) {
            $options['ssh_port'] = (int) $this->option('ssh-port');
        }

        $options['firewall'] = $full || (bool) $this->option('firewall');
        $options['fail2ban'] = $full || (bool) $this->option('fail2ban');
        $options['sysctl'] = $full || (bool) $this->option('sysctl');

        if (! $full && ! $options['firewall'] && ! $options['fail2ban'] && ! $options['sysctl'] && ! isset($options['ssh_port'])) {
            $this->error('Please specify at least one hardening option or --full for all.');
            $this->line('  --ssh-port=<port>  Change SSH port');
            $this->line('  --firewall         Setup UFW firewall');
            $this->line('  --fail2ban         Install and configure fail2ban');
            $this->line('  --sysctl           Apply kernel hardening');
            $this->line('  --full             Apply all measures');

            return Command::FAILURE;
        }

        // Confirm before proceeding
        if (isset($options['ssh_port'])) {
            $this->warn("SSH port will be changed to {$options['ssh_port']}. Ensure you can connect on the new port.");
        }

        if (! $this->confirm('Proceed with server hardening?', true)) {
            $this->info('Hardening cancelled.');

            return Command::SUCCESS;
        }

        try {
            $results = $hardeningService->hardenServer($server, $options);

            $this->newLine();
            $this->info('Hardening Results:');

            $tableData = [];
            foreach ($results as $step => $result) {
                $status = ($result['success'] ?? false) ? 'OK' : 'FAILED';
                $message = $result['message'] ?? 'Unknown';
                $tableData[] = [$step, $status, $message];
            }

            $this->table(['Step', 'Status', 'Message'], $tableData);

            $allSuccess = collect($results)->every(fn (array $r): bool => $r['success'] ?? false);

            if ($allSuccess) {
                $this->info('Server hardened successfully.');
            } else {
                $this->warn('Some hardening steps failed. Review results above.');
            }

            return $allSuccess ? Command::SUCCESS : Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("Hardening failed: {$e->getMessage()}");
            Log::error('Server hardening failed', ['server_id' => $server->id, 'error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }
}
