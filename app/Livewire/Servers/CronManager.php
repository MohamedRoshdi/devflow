<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Models\Server;
use App\Services\Docker\Concerns\ExecutesRemoteCommands;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CronManager extends Component
{
    use AuthorizesRequests;
    use ExecutesRemoteCommands;

    #[Locked]
    public Server $server;

    /** @var array<int, array{schedule: string, command: string, comment: string}> */
    public array $cronEntries = [];

    public bool $showCreateModal = false;

    public string $newSchedule = '* * * * *';

    public string $newCommand = '';

    public string $newComment = '';

    public function mount(Server $server): void
    {
        $this->authorize('view', $server);
        $this->server = $server;
        $this->loadCrontab();
    }

    /**
     * Load the deploy user's crontab from the server and parse each line.
     */
    public function loadCrontab(): void
    {
        try {
            $output = $this->getRemoteOutput(
                $this->server,
                'crontab -l 2>/dev/null || true',
                false
            );

            $this->cronEntries = [];

            foreach (explode("\n", $output) as $line) {
                $line = trim($line);

                // Skip blank lines and comment-only lines that start with #
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $entry = $this->parseCronLine($line);
                if ($entry !== null) {
                    $this->cronEntries[] = $entry;
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load crontab: '.$e->getMessage());
        }
    }

    /**
     * Parse a single cron line into {schedule, command, comment}.
     *
     * Supports inline comment suffix: "* * * * * /usr/bin/php artisan ... # My task"
     *
     * @return array{schedule: string, command: string, comment: string}|null
     */
    private function parseCronLine(string $line): ?array
    {
        // A cron line has at least 5 schedule fields followed by a command
        // Pattern: field1 field2 field3 field4 field5 command [# comment]
        if (! preg_match('/^(\S+\s+\S+\s+\S+\s+\S+\s+\S+)\s+(.+)$/', $line, $matches)) {
            return null;
        }

        $schedule = trim($matches[1]);
        $rest = trim($matches[2]);

        // Split command and optional inline comment
        $comment = '';
        if (preg_match('/^(.*?)\s+#\s*(.+)$/', $rest, $commentMatches)) {
            $command = trim($commentMatches[1]);
            $comment = trim($commentMatches[2]);
        } else {
            $command = $rest;
        }

        return [
            'schedule' => $schedule,
            'command' => $command,
            'comment' => $comment,
        ];
    }

    /**
     * Add a new cron entry to the deploy user's crontab.
     */
    public function addEntry(): void
    {
        $this->authorize('update', $this->server);

        $this->validate([
            'newSchedule' => ['required', 'string', 'max:100'],
            'newCommand' => ['required', 'string', 'max:1024'],
            'newComment' => ['nullable', 'string', 'max:255'],
        ]);

        $line = $this->newSchedule.' '.$this->newCommand;
        if ($this->newComment !== '') {
            $line .= ' # '.$this->newComment;
        }

        try {
            // Append the new line to the existing crontab
            $escapedLine = escapeshellarg($line);
            $this->executeRemoteCommand(
                $this->server,
                "(crontab -l 2>/dev/null; echo {$escapedLine}) | crontab -",
                false
            );

            $this->showCreateModal = false;
            $this->newSchedule = '* * * * *';
            $this->newCommand = '';
            $this->newComment = '';

            $this->loadCrontab();

            session()->flash('message', 'Cron entry added successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add cron entry: '.$e->getMessage());
        }
    }

    /**
     * Delete a cron entry by rebuilding the crontab without the given index.
     */
    public function deleteEntry(int $index): void
    {
        $this->authorize('update', $this->server);

        if (! isset($this->cronEntries[$index])) {
            return;
        }

        $entry = $this->cronEntries[$index];
        $lineToRemove = $entry['schedule'].' '.$entry['command'];
        if ($entry['comment'] !== '') {
            $lineToRemove .= ' # '.$entry['comment'];
        }

        try {
            // Remove the matching line from crontab using grep -Fxv for exact-line matching
            $escaped = escapeshellarg($lineToRemove);
            $this->executeRemoteCommand(
                $this->server,
                "(crontab -l 2>/dev/null | grep -Fxv {$escaped}) | crontab -",
                false
            );

            $this->loadCrontab();

            session()->flash('message', 'Cron entry removed.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete cron entry: '.$e->getMessage());
        }
    }

    /**
     * Decode a cron schedule expression to a human-readable description.
     */
    public function humanSchedule(string $schedule): string
    {
        return match ($schedule) {
            '* * * * *' => 'Every minute',
            '*/5 * * * *' => 'Every 5 minutes',
            '*/10 * * * *' => 'Every 10 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *' => 'Every hour',
            '0 0 * * *' => 'Daily at midnight',
            '0 1 * * *' => 'Daily at 1:00 AM',
            '0 2 * * *' => 'Daily at 2:00 AM',
            '0 3 * * *' => 'Daily at 3:00 AM',
            '0 6 * * *' => 'Daily at 6:00 AM',
            '0 12 * * *' => 'Daily at noon',
            '0 0 * * 0' => 'Weekly on Sunday',
            '0 0 * * 1' => 'Weekly on Monday',
            '0 0 1 * *' => 'Monthly on the 1st',
            '@hourly' => 'Every hour',
            '@daily' => 'Daily at midnight',
            '@weekly' => 'Weekly on Sunday',
            '@monthly' => 'Monthly on the 1st',
            '@yearly', '@annually' => 'Once a year',
            '@reboot' => 'On system reboot',
            default => '',
        };
    }

    public function render(): View
    {
        return view('livewire.servers.cron-manager')
            ->layout('layouts.app');
    }
}
