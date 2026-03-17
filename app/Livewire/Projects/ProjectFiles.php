<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use phpseclib3\Net\SSH2;

class ProjectFiles extends Component
{
    public Project $project;

    public string $currentPath = '';

    public bool $isLoading = true;

    public string $error = '';

    /** @var array<int, array{name: string, type: string, size: string, permissions: string, modified: string}> */
    public array $files = [];

    public ?string $selectedFile = null;

    public string $fileContent = '';

    public bool $showFileModal = false;

    public bool $isLoadingFile = false;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->currentPath = $this->getProjectPath();
        $this->loadFiles();
    }

    /**
     * Load files from the current directory
     */
    public function loadFiles(): void
    {
        $this->isLoading = true;
        $this->error = '';
        $this->files = [];

        try {
            $server = $this->project->server;
            if (! $server) {
                $this->error = __('project_files.no_server');
                $this->isLoading = false;

                return;
            }

            // List files with details (permissions, size, date, name)
            $command = sprintf(
                'ls -la --time-style=long-iso %s 2>&1 | tail -n +2',
                escapeshellarg($this->currentPath)
            );

            $output = $this->executeCommand($server, $command);

            if ($output === null) {
                $this->error = __('project_files.connection_failed');
                $this->isLoading = false;

                return;
            }

            $this->parseFileList($output);
        } catch (\Exception $e) {
            Log::error('ProjectFiles: Failed to load files', [
                'project_id' => $this->project->id,
                'path' => $this->currentPath,
                'error' => $e->getMessage(),
            ]);
            $this->error = __('project_files.load_failed').': '.$e->getMessage();
        }

        $this->isLoading = false;
    }

    /**
     * Parse the ls -la output into structured file data
     */
    protected function parseFileList(string $output): void
    {
        $lines = explode("\n", trim($output));
        $this->files = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parse ls -la output format:
            // drwxr-xr-x  2 user group 4096 2024-01-15 10:30 dirname
            // -rw-r--r--  1 user group 1234 2024-01-15 10:30 filename.txt
            $parts = preg_split('/\s+/', $line, 9);

            if (count($parts) < 9) {
                continue;
            }

            [$permissions, , , , $size, $date, $time, $name] = [
                $parts[0],
                $parts[1],
                $parts[2],
                $parts[3],
                $parts[4],
                $parts[5],
                $parts[6],
                implode(' ', array_slice($parts, 7)),
            ];

            // Skip . and .. entries
            if ($name === '.' || $name === '..') {
                continue;
            }

            $type = match (true) {
                str_starts_with($permissions, 'd') => 'directory',
                str_starts_with($permissions, 'l') => 'symlink',
                default => 'file',
            };

            $this->files[] = [
                'name' => $name,
                'type' => $type,
                'size' => $this->formatSize((int) $size),
                'permissions' => $permissions,
                'modified' => "{$date} {$time}",
            ];
        }

        // Sort: directories first, then files alphabetically
        usort($this->files, function ($a, $b) {
            if ($a['type'] === 'directory' && $b['type'] !== 'directory') {
                return -1;
            }
            if ($a['type'] !== 'directory' && $b['type'] === 'directory') {
                return 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });
    }

    /**
     * Format file size to human readable format
     */
    protected function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 1).' GB';
    }

    /**
     * Navigate to a directory
     */
    public function navigateTo(string $name): void
    {
        $newPath = rtrim($this->currentPath, '/').'/'.ltrim($name, '/');

        // Validate path doesn't escape project directory
        $basePath = $this->getProjectPath();
        $realNewPath = realpath($newPath) ?: $newPath;

        if (! str_starts_with($realNewPath, $basePath) && $newPath !== $basePath) {
            // Allow navigation but sanitize path
            $newPath = $basePath.'/'.basename($name);
        }

        $this->currentPath = $newPath;
        $this->loadFiles();
    }

    /**
     * Navigate up one directory level
     */
    public function navigateUp(): void
    {
        $basePath = $this->getProjectPath();

        if ($this->currentPath === $basePath) {
            return; // Already at root
        }

        $parentPath = dirname($this->currentPath);

        // Don't go above project root
        if (! str_starts_with($parentPath, $basePath)) {
            $this->currentPath = $basePath;
        } else {
            $this->currentPath = $parentPath;
        }

        $this->loadFiles();
    }

    /**
     * View a file's content
     */
    public function viewFile(string $name): void
    {
        $this->selectedFile = $name;
        $this->fileContent = '';
        $this->showFileModal = true;
        $this->isLoadingFile = true;

        try {
            $server = $this->project->server;
            if (! $server) {
                $this->fileContent = __('project_files.no_server');
                $this->isLoadingFile = false;

                return;
            }

            $filePath = rtrim($this->currentPath, '/').'/'.$name;

            // Check file size first (limit to 1MB)
            $sizeCommand = sprintf('stat -c%%s %s 2>/dev/null || stat -f%%z %s 2>/dev/null', escapeshellarg($filePath), escapeshellarg($filePath));
            $sizeOutput = $this->executeCommand($server, $sizeCommand);
            $fileSize = (int) trim($sizeOutput ?? '0');

            if ($fileSize > 1024 * 1024) {
                $this->fileContent = __('project_files.file_too_large', ['size' => $this->formatSize($fileSize)]);
                $this->isLoadingFile = false;

                return;
            }

            // Check if binary file
            $mimeCommand = sprintf('file --mime-type -b %s 2>/dev/null', escapeshellarg($filePath));
            $mimeOutput = $this->executeCommand($server, $mimeCommand);
            $mimeType = trim($mimeOutput ?? '');

            if (str_starts_with($mimeType, 'application/') && ! in_array($mimeType, ['application/json', 'application/xml', 'application/javascript'])) {
                $this->fileContent = __('project_files.binary_file', ['mime' => $mimeType]);
                $this->isLoadingFile = false;

                return;
            }

            // Read file content
            $command = sprintf('cat %s 2>&1', escapeshellarg($filePath));
            $content = $this->executeCommand($server, $command, 30);

            $this->fileContent = $content ?? __('project_files.read_failed');
        } catch (\Exception $e) {
            Log::error('ProjectFiles: Failed to read file', [
                'project_id' => $this->project->id,
                'file' => $name,
                'error' => $e->getMessage(),
            ]);
            $this->fileContent = __('project_files.read_failed').': '.$e->getMessage();
        }

        $this->isLoadingFile = false;
    }

    /**
     * Close the file viewer modal
     */
    public function closeFileModal(): void
    {
        $this->showFileModal = false;
        $this->selectedFile = null;
        $this->fileContent = '';
    }

    /**
     * Get the relative path from project root
     */
    #[Computed]
    public function relativePath(): string
    {
        $basePath = $this->getProjectPath();

        if ($this->currentPath === $basePath) {
            return '/';
        }

        return str_replace($basePath, '', $this->currentPath) ?: '/';
    }

    /**
     * Get breadcrumb parts for navigation
     *
     * @return array<int, array{name: string, path: string}>
     */
    #[Computed]
    public function breadcrumbs(): array
    {
        $basePath = $this->getProjectPath();
        $relativePath = str_replace($basePath, '', $this->currentPath);
        $parts = array_filter(explode('/', $relativePath));

        $breadcrumbs = [
            ['name' => $this->project->name, 'path' => $basePath],
        ];

        $currentPath = $basePath;
        foreach ($parts as $part) {
            $currentPath .= '/'.$part;
            $breadcrumbs[] = ['name' => $part, 'path' => $currentPath];
        }

        return $breadcrumbs;
    }

    /**
     * Navigate to a specific breadcrumb path
     */
    public function navigateToBreadcrumb(string $path): void
    {
        $basePath = $this->getProjectPath();

        // Validate path doesn't escape project directory
        if (! str_starts_with($path, $basePath)) {
            $path = $basePath;
        }

        $this->currentPath = $path;
        $this->loadFiles();
    }

    /**
     * Get the project path on the server
     */
    protected function getProjectPath(): string
    {
        $basePath = config('devflow.projects_path', '/var/www');

        return rtrim($basePath, '/').'/'.$this->project->slug;
    }

    /**
     * Execute a command on the server
     */
    protected function executeCommand(Server $server, string $command, int $timeout = 30): ?string
    {
        // Check if localhost - execute locally
        if ($server->shouldExecuteLocally()) {
            return $this->executeLocally($command, $timeout);
        }

        // Check if using password authentication
        if ($server->ssh_password !== null && strlen($server->ssh_password) > 0) {
            return $this->executeWithPhpseclib($server, $command, $timeout);
        }

        // Use system SSH
        return $this->executeWithSystemSsh($server, $command, $timeout);
    }

    /**
     * Check if IP is localhost
     */
    protected function isLocalhost(string $host): bool
    {
        $localIPs = ['127.0.0.1', '127.0.1.1', '::1', 'localhost'];

        return in_array($host, $localIPs);
    }

    /**
     * Execute command locally
     */
    protected function executeLocally(string $command, int $timeout): ?string
    {
        $result = Process::timeout($timeout)->run($command);

        return $result->output().$result->errorOutput();
    }

    /**
     * Execute command using phpseclib (password auth)
     */
    protected function executeWithPhpseclib(Server $server, string $command, int $timeout): ?string
    {
        $ssh = new SSH2($server->connection_host, $server->port, $timeout);

        if (! $ssh->login($server->username, $server->ssh_password)) {
            throw new \RuntimeException('SSH authentication failed');
        }

        return $ssh->exec($command);
    }

    /**
     * Execute command using system SSH (key auth)
     */
    protected function executeWithSystemSsh(Server $server, string $command, int $timeout): ?string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o ConnectTimeout=30',
            '-o LogLevel=ERROR',
            '-o BatchMode=yes',
            '-p '.$server->port,
        ];

        $sshCommand = sprintf(
            'ssh %s %s@%s %s 2>&1',
            implode(' ', $sshOptions),
            $server->username,
            $server->connection_host,
            escapeshellarg($command)
        );

        $result = Process::timeout($timeout)->run($sshCommand);

        return $result->output();
    }

    /**
     * Get file icon based on file type/extension
     */
    public function getFileIcon(string $name, string $type): string
    {
        if ($type === 'directory') {
            return 'folder';
        }

        if ($type === 'symlink') {
            return 'link';
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match ($extension) {
            'php' => 'php',
            'js', 'ts', 'jsx', 'tsx' => 'javascript',
            'vue' => 'vue',
            'css', 'scss', 'sass', 'less' => 'css',
            'html', 'htm', 'blade.php' => 'html',
            'json' => 'json',
            'xml' => 'xml',
            'md', 'markdown' => 'markdown',
            'yml', 'yaml' => 'yaml',
            'sh', 'bash' => 'shell',
            'sql' => 'database',
            'env' => 'env',
            'gitignore', 'editorconfig', 'htaccess' => 'config',
            'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico' => 'image',
            'pdf' => 'pdf',
            'zip', 'tar', 'gz', 'rar', '7z' => 'archive',
            'lock' => 'lock',
            'log' => 'log',
            default => 'file',
        };
    }

    public function render(): View
    {
        return view('livewire.projects.project-files');
    }
}
