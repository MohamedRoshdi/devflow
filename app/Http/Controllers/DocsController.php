<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

class DocsController extends Controller
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        // Middleware is defined in routes/web.php

        // Configure CommonMark with extensions
        $config = [
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'before',
                'title' => 'Permalink',
                'symbol' => '#',
            ],
            'table_of_contents' => [
                'html_class' => 'table-of-contents',
                'position' => 'top',
                'style' => 'bullet',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'normalize' => 'relative',
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new TableOfContentsExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Show documentation page
     */
    public function show(Request $request, ?string $category = null, ?string $page = null)
    {
        // If no category, show index
        if (!$category) {
            return view('docs.index', [
                'categories' => $this->getAllCategories(),
            ]);
        }

        // Build file path - use configurable path for testing
        $docsPath = config('docs.categories_path', resource_path('docs/categories'));
        $filePath = "{$docsPath}/{$category}.md";

        if (!File::exists($filePath)) {
            abort(404, 'Documentation page not found');
        }

        // Get markdown content from cache or file
        $cacheKey = "docs.{$category}." . File::lastModified($filePath);
        $html = Cache::remember($cacheKey, now()->addHours(24), function () use ($filePath) {
            $markdown = File::get($filePath);
            // Strip frontmatter before conversion
            $markdown = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $markdown) ?? $markdown;
            return $this->converter->convert($markdown)->getContent();
        });

        // Parse frontmatter for title and description
        $frontmatter = $this->parseFrontmatter($filePath);

        return view('docs.show', [
            'category' => $category,
            'title' => $frontmatter['title'] ?? ucwords(str_replace('-', ' ', $category)),
            'description' => $frontmatter['description'] ?? '',
            'content' => $html,
            'categories' => $this->getAllCategories(),
            'tableOfContents' => $this->generateTableOfContents($filePath),
        ]);
    }

    /**
     * Search documentation
     */
    public function search(Request $request)
    {
        $query = (string) $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'message' => 'Search query must be at least 2 characters',
            ]);
        }

        $results = $this->searchDocumentation($query);

        if ($request->expectsJson()) {
            return response()->json([
                'results' => $results,
                'count' => count($results),
            ]);
        }

        return view('docs.search', [
            'query' => $query,
            'results' => $results,
            'categories' => $this->getAllCategories(),
        ]);
    }

    /**
     * Get all available documentation categories
     */
    private function getAllCategories(): array
    {
        return [
            [
                'slug' => 'deployments',
                'title' => 'Deployments',
                'icon' => 'rocket-launch',
                'description' => 'Deploy, rollback, and manage your applications',
            ],
            [
                'slug' => 'domains',
                'title' => 'Domains',
                'icon' => 'globe-alt',
                'description' => 'Domain management and configuration',
            ],
            [
                'slug' => 'ssl',
                'title' => 'SSL Certificates',
                'icon' => 'lock-closed',
                'description' => 'SSL certificate management and automation',
            ],
            [
                'slug' => 'servers',
                'title' => 'Servers',
                'icon' => 'server',
                'description' => 'Server configuration and management',
            ],
            [
                'slug' => 'monitoring',
                'title' => 'Monitoring',
                'icon' => 'chart-bar',
                'description' => 'Monitoring, logs, and health checks',
            ],
            [
                'slug' => 'security',
                'title' => 'Security',
                'icon' => 'shield-check',
                'description' => '2FA, API tokens, and security features',
            ],
            [
                'slug' => 'docker',
                'title' => 'Docker',
                'icon' => 'cube',
                'description' => 'Docker container management',
            ],
            [
                'slug' => 'kubernetes',
                'title' => 'Kubernetes',
                'icon' => 'cloud',
                'description' => 'Kubernetes cluster management',
            ],
            [
                'slug' => 'pipelines',
                'title' => 'CI/CD Pipelines',
                'icon' => 'arrow-path',
                'description' => 'Build and manage deployment pipelines',
            ],
            [
                'slug' => 'teams',
                'title' => 'Teams',
                'icon' => 'user-group',
                'description' => 'Team collaboration and permissions',
            ],
            [
                'slug' => 'database',
                'title' => 'Database',
                'icon' => 'circle-stack',
                'description' => 'Database management and migrations',
            ],
            [
                'slug' => 'backups',
                'title' => 'Backups',
                'icon' => 'archive-box',
                'description' => 'Backup and restore functionality',
            ],
            [
                'slug' => 'multi-tenancy',
                'title' => 'Multi-Tenancy',
                'icon' => 'building-office',
                'description' => 'Multi-tenant application management',
            ],
        ];
    }

    /**
     * Parse frontmatter from markdown file
     */
    private function parseFrontmatter(string $filePath): array
    {
        $content = File::get($filePath);

        // Check for YAML frontmatter
        if (!preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            return [];
        }

        $frontmatter = [];
        $lines = explode("\n", $matches[1]);

        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $lineMatches)) {
                $frontmatter[$lineMatches[1]] = trim($lineMatches[2], '"\'');
            }
        }

        return $frontmatter;
    }

    /**
     * Generate table of contents from markdown file
     */
    private function generateTableOfContents(string $filePath): array
    {
        $content = File::get($filePath);

        // Remove frontmatter
        $content = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $content) ?? $content;

        $toc = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $title = trim($matches[2]);
                $slug = str($title)->slug()->toString();

                $toc[] = [
                    'level' => $level,
                    'title' => $title,
                    'slug' => $slug,
                ];
            }
        }

        return $toc;
    }

    /**
     * Search through all documentation files
     */
    private function searchDocumentation(string $query): array
    {
        $results = [];
        $docsPath = config('docs.categories_path', resource_path('docs/categories'));

        if (!File::isDirectory($docsPath)) {
            return [];
        }

        $files = File::files($docsPath);
        $query = strtolower($query);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $content = File::get($file->getPathname());
            $category = $file->getFilenameWithoutExtension();

            // Remove frontmatter
            $content = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $content) ?? $content;

            // Search in content
            if (stripos($content, $query) !== false) {
                // Extract relevant excerpt
                $excerpt = $this->extractExcerpt($content, $query);
                $frontmatter = $this->parseFrontmatter($file->getPathname());

                // Find matching sections
                $sections = $this->findMatchingSections($content, $query);

                $results[] = [
                    'category' => $category,
                    'title' => $frontmatter['title'] ?? ucwords(str_replace('-', ' ', $category)),
                    'excerpt' => $excerpt,
                    'sections' => $sections,
                    'url' => route('docs.show', ['category' => $category]),
                ];
            }
        }

        return $results;
    }

    /**
     * Extract excerpt around search query
     */
    private function extractExcerpt(string $content, string $query, int $contextLength = 150): string
    {
        $pos = stripos($content, $query);

        if ($pos === false) {
            return substr(strip_tags($content), 0, $contextLength) . '...';
        }

        $start = max(0, (int) ($pos - $contextLength / 2));
        $excerpt = substr($content, $start, $contextLength);

        // Highlight the query
        $excerpt = preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark>$1</mark>', $excerpt) ?? $excerpt;

        return ($start > 0 ? '...' : '') . strip_tags($excerpt, '<mark>') . '...';
    }

    /**
     * Find sections that match the search query
     */
    private function findMatchingSections(string $content, string $query): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = null;

        foreach ($lines as $line) {
            // Check for headings
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $currentSection = [
                    'title' => trim($matches[2]),
                    'slug' => str($matches[2])->slug()->toString(),
                    'level' => strlen($matches[1]),
                ];
            }

            // Check if line contains query
            if ($currentSection && stripos($line, $query) !== false) {
                if (!in_array($currentSection, $sections)) {
                    $sections[] = $currentSection;
                }
            }
        }

        return array_slice($sections, 0, 5); // Limit to 5 sections
    }
}
