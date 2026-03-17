<?php

declare(strict_types=1);

namespace App\Livewire\Servers;

use App\Livewire\Concerns\WithPasswordConfirmation;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DatabaseManager extends Component
{
    use WithPasswordConfirmation;

    #[Locked]
    public Server $server;

    /** @var array<int, array{name: string, size: string}> */
    public array $databases = [];

    public string $dbType = '';

    /** @var array<int, array{name: string, is_superuser: bool}> */
    public array $dbUsers = [];

    public bool $showCreateDbModal = false;

    public string $newDbName = '';

    public string $newDbOwner = '';

    public bool $showCreateUserModal = false;

    public string $newUserName = '';

    public string $newUserPassword = '';

    public bool $loading = false;

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->detectDatabaseType();

        if ($this->dbType !== '') {
            $this->loadDatabases();
            $this->loadUsers();
        }
    }

    public function detectDatabaseType(): void
    {
        try {
            $pgResult = Process::timeout(10)->run(
                $this->buildSSHCommand($this->server, 'which psql 2>/dev/null && echo "found" || echo "not_found"', true)
            );

            if ($pgResult->successful() && str_contains($pgResult->output(), 'found')) {
                // Get PostgreSQL version
                $versionResult = Process::timeout(10)->run(
                    $this->buildSSHCommand($this->server, 'sudo -u postgres psql -t -c "SELECT version();" 2>/dev/null | head -1', true)
                );

                $version = '';
                if ($versionResult->successful()) {
                    preg_match('/PostgreSQL (\d+\.\d+)/', $versionResult->output(), $matches);
                    $version = $matches[1] ?? '';
                }

                $this->dbType = 'postgresql'.($version !== '' ? ' '.$version : '');

                return;
            }

            $mysqlResult = Process::timeout(10)->run(
                $this->buildSSHCommand($this->server, 'which mysql 2>/dev/null && echo "found" || echo "not_found"', true)
            );

            if ($mysqlResult->successful() && str_contains($mysqlResult->output(), 'found')) {
                $versionResult = Process::timeout(10)->run(
                    $this->buildSSHCommand($this->server, 'mysql --version 2>/dev/null', true)
                );

                $version = '';
                if ($versionResult->successful()) {
                    preg_match('/(\d+\.\d+)/', $versionResult->output(), $matches);
                    $version = $matches[1] ?? '';
                }

                $this->dbType = 'mysql'.($version !== '' ? ' '.$version : '');
            }
        } catch (\Exception $e) {
            Log::error('Failed to detect database type', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function loadDatabases(): void
    {
        try {
            if (str_starts_with($this->dbType, 'postgresql')) {
                $command = 'sudo -u postgres psql -t -A -F"|" -c "SELECT datname, pg_size_pretty(pg_database_size(datname)) FROM pg_database WHERE datistemplate = false ORDER BY datname;" 2>/dev/null';
                $result = Process::timeout(15)->run(
                    $this->buildSSHCommand($this->server, $command, true)
                );

                if ($result->successful()) {
                    $this->databases = collect(explode("\n", trim($result->output())))
                        ->filter(fn (string $line) => $line !== '')
                        ->map(function (string $line): array {
                            $parts = explode('|', $line);

                            return [
                                'name' => trim($parts[0] ?? ''),
                                'size' => trim($parts[1] ?? 'N/A'),
                            ];
                        })
                        ->filter(fn (array $db) => $db['name'] !== '')
                        ->values()
                        ->all();
                }
            } elseif (str_starts_with($this->dbType, 'mysql')) {
                $command = 'mysql -N -e "SELECT table_schema, ROUND(SUM(data_length + index_length)/1024/1024, 2) FROM information_schema.tables GROUP BY table_schema ORDER BY table_schema;" 2>/dev/null';
                $result = Process::timeout(15)->run(
                    $this->buildSSHCommand($this->server, $command, true)
                );

                if ($result->successful()) {
                    $this->databases = collect(explode("\n", trim($result->output())))
                        ->filter(fn (string $line) => $line !== '')
                        ->map(function (string $line): array {
                            $parts = preg_split('/\s+/', trim($line), 2);

                            return [
                                'name' => trim($parts[0] ?? ''),
                                'size' => isset($parts[1]) ? trim($parts[1]).' MB' : 'N/A',
                            ];
                        })
                        ->filter(fn (array $db) => $db['name'] !== '')
                        ->values()
                        ->all();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load databases', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load databases: '.$e->getMessage());
        }
    }

    public function loadUsers(): void
    {
        try {
            if (str_starts_with($this->dbType, 'postgresql')) {
                $command = 'sudo -u postgres psql -t -A -F"|" -c "SELECT usename, usesuper FROM pg_user ORDER BY usename;" 2>/dev/null';
                $result = Process::timeout(15)->run(
                    $this->buildSSHCommand($this->server, $command, true)
                );

                if ($result->successful()) {
                    $this->dbUsers = collect(explode("\n", trim($result->output())))
                        ->filter(fn (string $line) => $line !== '')
                        ->map(function (string $line): array {
                            $parts = explode('|', $line);

                            return [
                                'name' => trim($parts[0] ?? ''),
                                'is_superuser' => trim($parts[1] ?? '') === 't',
                            ];
                        })
                        ->filter(fn (array $user) => $user['name'] !== '')
                        ->values()
                        ->all();
                }
            } elseif (str_starts_with($this->dbType, 'mysql')) {
                $command = 'mysql -N -e "SELECT user, host FROM mysql.user ORDER BY user;" 2>/dev/null';
                $result = Process::timeout(15)->run(
                    $this->buildSSHCommand($this->server, $command, true)
                );

                if ($result->successful()) {
                    $this->dbUsers = collect(explode("\n", trim($result->output())))
                        ->filter(fn (string $line) => $line !== '')
                        ->map(function (string $line): array {
                            $parts = preg_split('/\s+/', trim($line), 2);

                            return [
                                'name' => trim($parts[0] ?? '').'@'.trim($parts[1] ?? '%'),
                                'is_superuser' => false,
                            ];
                        })
                        ->filter(fn (array $user) => $user['name'] !== '@')
                        ->values()
                        ->all();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load database users', [
                'server_id' => $this->server->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to load users: '.$e->getMessage());
        }
    }

    public function refresh(): void
    {
        $this->loadDatabases();
        $this->loadUsers();
        session()->flash('message', 'Database list refreshed.');
    }

    public function createDatabase(): void
    {
        $this->validate([
            'newDbName' => ['required', 'string', 'max:63', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
        ]);

        try {
            if (str_starts_with($this->dbType, 'postgresql')) {
                $ownerClause = $this->newDbOwner !== '' ? ' -O '.escapeshellarg($this->newDbOwner) : '';
                $command = 'sudo -u postgres createdb'.$ownerClause.' '.escapeshellarg($this->newDbName).' 2>&1';
            } else {
                $command = 'mysql -e "CREATE DATABASE '.escapeshellarg($this->newDbName).';" 2>&1';
            }

            $result = Process::timeout(30)->run(
                $this->buildSSHCommand($this->server, $command)
            );

            if ($result->successful() || trim($result->output()) === '') {
                session()->flash('message', "Database '{$this->newDbName}' created successfully.");
                $this->showCreateDbModal = false;
                $this->newDbName = '';
                $this->newDbOwner = '';
                $this->loadDatabases();
            } else {
                $error = trim($result->output().$result->errorOutput());
                session()->flash('error', 'Failed to create database: '.$error);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create database', [
                'server_id' => $this->server->id,
                'db_name' => $this->newDbName,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to create database: '.$e->getMessage());
        }
    }

    public function dropDatabase(string $name): void
    {
        try {
            if (str_starts_with($this->dbType, 'postgresql')) {
                $command = 'sudo -u postgres dropdb '.escapeshellarg($name).' 2>&1';
            } else {
                $command = 'mysql -e "DROP DATABASE '.escapeshellarg($name).';" 2>&1';
            }

            $result = Process::timeout(30)->run(
                $this->buildSSHCommand($this->server, $command)
            );

            if ($result->successful() || trim($result->output()) === '') {
                session()->flash('message', "Database '{$name}' dropped successfully.");
                $this->loadDatabases();
            } else {
                $error = trim($result->output().$result->errorOutput());
                session()->flash('error', "Failed to drop database '{$name}': ".$error);
            }
        } catch (\Exception $e) {
            Log::error('Failed to drop database', [
                'server_id' => $this->server->id,
                'db_name' => $name,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to drop database: '.$e->getMessage());
        }
    }

    public function createUser(): void
    {
        $this->validate([
            'newUserName' => ['required', 'string', 'max:63', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
            'newUserPassword' => ['required', 'string', 'min:8'],
        ]);

        try {
            if (str_starts_with($this->dbType, 'postgresql')) {
                $escapedUser = escapeshellarg($this->newUserName);
                $escapedPass = str_replace("'", "''", $this->newUserPassword);
                $command = "sudo -u postgres psql -c \"CREATE USER {$this->newUserName} WITH PASSWORD '{$escapedPass}';\" 2>&1";
            } else {
                $escapedUser = escapeshellarg($this->newUserName);
                $escapedPass = escapeshellarg($this->newUserPassword);
                $command = "mysql -e \"CREATE USER {$this->newUserName}@'%' IDENTIFIED BY {$escapedPass};\" 2>&1";
            }

            $result = Process::timeout(30)->run(
                $this->buildSSHCommand($this->server, $command)
            );

            if ($result->successful() || (str_contains($result->output(), 'CREATE ROLE') || str_contains($result->output(), 'Query OK'))) {
                session()->flash('message', "User '{$this->newUserName}' created successfully.");
                $this->showCreateUserModal = false;
                $this->newUserName = '';
                $this->newUserPassword = '';
                $this->loadUsers();
            } else {
                $error = trim($result->output().$result->errorOutput());
                session()->flash('error', 'Failed to create user: '.$error);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create database user', [
                'server_id' => $this->server->id,
                'username' => $this->newUserName,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to create user: '.$e->getMessage());
        }
    }

    public function grantAccess(string $user, string $database): void
    {
        try {
            // Strip the @host part for MySQL users if present
            $userName = str_contains($user, '@') ? explode('@', $user)[0] : $user;

            if (str_starts_with($this->dbType, 'postgresql')) {
                $command = "sudo -u postgres psql -c \"GRANT ALL PRIVILEGES ON DATABASE {$database} TO {$userName};\" 2>&1";
            } else {
                $command = "mysql -e \"GRANT ALL PRIVILEGES ON {$database}.* TO '{$userName}'@'%'; FLUSH PRIVILEGES;\" 2>&1";
            }

            $result = Process::timeout(30)->run(
                $this->buildSSHCommand($this->server, $command)
            );

            if ($result->successful()) {
                session()->flash('message', "Granted access to '{$database}' for user '{$userName}'.");
            } else {
                $error = trim($result->output().$result->errorOutput());
                session()->flash('error', 'Failed to grant access: '.$error);
            }
        } catch (\Exception $e) {
            Log::error('Failed to grant database access', [
                'server_id' => $this->server->id,
                'user' => $user,
                'database' => $database,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to grant access: '.$e->getMessage());
        }
    }

    public function generatePassword(): void
    {
        $this->newUserPassword = Str::password(16, symbols: false);
    }

    protected function buildSSHCommand(Server $server, string $remoteCommand, bool $suppressWarnings = false): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=30',
            '-o LogLevel=ERROR',
            '-p '.$server->port,
        ];

        $stderrRedirect = $suppressWarnings ? '2>/dev/null' : '2>&1';

        $isLongScript = strlen($remoteCommand) > 500 || str_contains($remoteCommand, '$(');

        if ($isLongScript) {
            $encodedScript = base64_encode($remoteCommand);
            $executeCommand = "echo {$encodedScript} | base64 -d | /bin/bash";
        } else {
            $executeCommand = '/bin/bash -c '.escapeshellarg($remoteCommand);
        }

        if ($server->ssh_password) {
            $escapedPassword = escapeshellarg($server->ssh_password);

            return sprintf(
                'sshpass -p %s ssh %s %s@%s %s %s',
                $escapedPassword,
                implode(' ', $sshOptions),
                $server->username,
                $server->ip_address,
                escapeshellarg($executeCommand),
                $stderrRedirect
            );
        }

        $sshOptions[] = '-o BatchMode=yes';

        if ($server->ssh_key) {
            $keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($keyFile, $server->ssh_key);
            chmod($keyFile, 0600);
            $sshOptions[] = '-i '.$keyFile;
        }

        return sprintf(
            'ssh %s %s@%s %s %s',
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            escapeshellarg($executeCommand),
            $stderrRedirect
        );
    }

    public function render(): View
    {
        return view('livewire.servers.database-manager')->layout('layouts.app');
    }
}
