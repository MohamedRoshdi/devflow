<?php

declare(strict_types=1);

namespace Tests\Feature;


use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocsControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $docsPath;
    private string $testMarkdownFile;
    private string $testMarkdownContent;

    protected function setUp(): void
    {
        parent::setUp();

        // Use storage path which is writable in Docker
        $this->docsPath = storage_path('app/docs-test');
        $this->testMarkdownFile = $this->docsPath . '/test-category.md';

        // Test markdown content with frontmatter
        $this->testMarkdownContent = <<<'MD'
---
title: Test Category
description: This is a test category for documentation
---

# Test Category

This is the main content of the test documentation page.

## First Section

This section contains information about feature one.

### Subsection A

Some detailed information here.

## Second Section

This section contains information about feature two with a **search term** example.

## Third Section

Another section with more content for testing purposes.
MD;

        // Create test markdown file in writable location
        if (!File::isDirectory($this->docsPath)) {
            File::makeDirectory($this->docsPath, 0755, true);
        }

        File::put($this->testMarkdownFile, $this->testMarkdownContent);

        // Bind the docs path for tests
        config(['docs.categories_path' => $this->docsPath]);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clean up test files and directory
        if (File::isDirectory($this->docsPath)) {
            File::deleteDirectory($this->docsPath);
        }

        parent::tearDown();
    }

    // ==================== Index Page Tests ====================

    #[Test]
    public function user_can_view_documentation_index(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show'));

        $response->assertOk()
            ->assertViewIs('docs.index')
            ->assertViewHas('categories')
            ->assertSee('Deployments')
            ->assertSee('Domains')
            ->assertSee('SSL Certificates')
            ->assertSee('Servers');
    }

    #[Test]
    public function documentation_index_displays_all_categories(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show'));

        $expectedCategories = [
            'Deployments',
            'Domains',
            'SSL Certificates',
            'Servers',
            'Monitoring',
            'Security',
            'Docker',
            'Kubernetes',
            'CI/CD Pipelines',
            'Teams',
            'Database',
            'Backups',
            'Multi-Tenancy',
        ];

        foreach ($expectedCategories as $category) {
            $response->assertSee($category);
        }
    }

    // ==================== Show Method Tests ====================

    #[Test]
    public function user_can_view_documentation_page_with_category(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $response->assertOk()
            ->assertViewIs('docs.show')
            ->assertViewHas('category', 'test-category')
            ->assertViewHas('title', 'Test Category')
            ->assertViewHas('description', 'This is a test category for documentation')
            ->assertViewHas('content')
            ->assertViewHas('categories')
            ->assertViewHas('tableOfContents');
    }

    #[Test]
    public function documentation_page_renders_markdown_content(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $response->assertOk()
            ->assertSee('Test Category')
            ->assertSee('This is the main content of the test documentation page')
            ->assertSee('First Section')
            ->assertSee('Second Section');
    }

    #[Test]
    public function documentation_page_parses_frontmatter_correctly(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $response->assertOk()
            ->assertViewHas('title', 'Test Category')
            ->assertViewHas('description', 'This is a test category for documentation');
    }

    #[Test]
    public function documentation_page_generates_table_of_contents(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $response->assertOk();

        $tableOfContents = $response->viewData('tableOfContents');

        $this->assertIsArray($tableOfContents);
        $this->assertNotEmpty($tableOfContents);

        // Verify structure of TOC entries
        $firstEntry = $tableOfContents[0];
        $this->assertArrayHasKey('level', $firstEntry);
        $this->assertArrayHasKey('title', $firstEntry);
        $this->assertArrayHasKey('slug', $firstEntry);

        // Verify TOC contains expected sections
        $titles = array_column($tableOfContents, 'title');
        $this->assertContains('First Section', $titles);
        $this->assertContains('Second Section', $titles);
    }

    #[Test]
    public function documentation_page_strips_frontmatter_from_content(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $content = $response->viewData('content');

        // Frontmatter should not appear in rendered content
        $this->assertStringNotContainsString('---', $content);
        $this->assertStringNotContainsString('title: Test Category', $content);
        $this->assertStringNotContainsString('description:', $content);
    }

    #[Test]
    public function documentation_page_uses_default_title_when_no_frontmatter(): void
    {
        $this->actingAsUser();

        // Create a file without frontmatter
        $noFrontmatterFile = $this->docsPath . '/no-frontmatter.md';
        File::put($noFrontmatterFile, '# Just Content');

        $response = $this->get(route('docs.show', ['category' => 'no-frontmatter']));

        $response->assertOk()
            ->assertViewHas('title', 'No Frontmatter'); // Slug transformed to title

        File::delete($noFrontmatterFile);
    }

    #[Test]
    public function documentation_page_caches_rendered_content(): void
    {
        $this->actingAsUser();

        // First request - should cache
        $response1 = $this->get(route('docs.show', ['category' => 'test-category']));
        $response1->assertOk();

        // Check cache exists
        $lastModified = File::lastModified($this->testMarkdownFile);
        $cacheKey = "docs.test-category.{$lastModified}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second request - should use cache
        $response2 = $this->get(route('docs.show', ['category' => 'test-category']));
        $response2->assertOk();

        // Both responses should have identical content
        $this->assertEquals(
            $response1->viewData('content'),
            $response2->viewData('content')
        );
    }

    // ==================== 404 Handling Tests ====================

    #[Test]
    public function documentation_page_returns_404_for_missing_category(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'non-existent-category']));

        $response->assertNotFound();
    }

    #[Test]
    public function documentation_page_shows_error_message_for_missing_page(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'missing-page']));

        $response->assertNotFound();
    }

    // ==================== Search Method Tests ====================

    #[Test]
    public function user_can_search_documentation(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.search', ['q' => 'search term']));

        $response->assertOk()
            ->assertViewIs('docs.search')
            ->assertViewHas('query', 'search term')
            ->assertViewHas('results')
            ->assertViewHas('categories');
    }

    #[Test]
    public function search_returns_json_when_expecting_json(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'search term']));

        $response->assertOk()
            ->assertJsonStructure([
                'results',
                'count',
            ]);
    }

    #[Test]
    public function search_finds_matching_content(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'search term']));

        $response->assertOk();

        $results = $response->json('results');
        $this->assertNotEmpty($results);

        // Verify result structure
        $firstResult = $results[0];
        $this->assertArrayHasKey('category', $firstResult);
        $this->assertArrayHasKey('title', $firstResult);
        $this->assertArrayHasKey('excerpt', $firstResult);
        $this->assertArrayHasKey('sections', $firstResult);
        $this->assertArrayHasKey('url', $firstResult);

        // Verify the search found our test content
        $this->assertEquals('test-category', $firstResult['category']);
        $this->assertStringContainsString('search term', $firstResult['excerpt']);
    }

    #[Test]
    public function search_returns_empty_results_for_query_less_than_2_characters(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'a']));

        $response->assertOk()
            ->assertJson([
                'results' => [],
                'message' => 'Search query must be at least 2 characters',
            ]);
    }

    #[Test]
    public function search_returns_empty_results_for_no_matches(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'xyzabc123notfound']));

        $response->assertOk()
            ->assertJson([
                'results' => [],
                'count' => 0,
            ]);
    }

    #[Test]
    public function search_is_case_insensitive(): void
    {
        $this->actingAsUser();

        $response1 = $this->getJson(route('docs.search', ['q' => 'SEARCH TERM']));
        $response2 = $this->getJson(route('docs.search', ['q' => 'search term']));
        $response3 = $this->getJson(route('docs.search', ['q' => 'SeArCh TeRm']));

        $response1->assertOk();
        $response2->assertOk();
        $response3->assertOk();

        // All should return same count
        $this->assertEquals(
            $response1->json('count'),
            $response2->json('count')
        );
        $this->assertEquals(
            $response2->json('count'),
            $response3->json('count')
        );
    }

    #[Test]
    public function search_highlights_query_in_excerpt(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'search term']));

        $response->assertOk();

        $results = $response->json('results');
        $this->assertNotEmpty($results);

        $excerpt = $results[0]['excerpt'];

        // Check that the excerpt contains the mark tag for highlighting
        $this->assertStringContainsString('<mark>', $excerpt);
        $this->assertStringContainsString('</mark>', $excerpt);
    }

    #[Test]
    public function search_finds_matching_sections(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'search term']));

        $response->assertOk();

        $results = $response->json('results');
        $this->assertNotEmpty($results);

        $sections = $results[0]['sections'];
        $this->assertIsArray($sections);

        if (!empty($sections)) {
            $firstSection = $sections[0];
            $this->assertArrayHasKey('title', $firstSection);
            $this->assertArrayHasKey('slug', $firstSection);
            $this->assertArrayHasKey('level', $firstSection);

            // Should find "Second Section" which contains the search term
            $sectionTitles = array_column($sections, 'title');
            $this->assertContains('Second Section', $sectionTitles);
        }
    }

    #[Test]
    public function search_limits_sections_to_five(): void
    {
        $this->actingAsUser();

        // Create a file with many sections containing the search term
        $manySectionsFile = $this->docsPath . '/many-sections.md';
        $content = "# Main Title\n\n";
        for ($i = 1; $i <= 10; $i++) {
            $content .= "## Section {$i}\n\nThis section contains the searchable keyword.\n\n";
        }
        File::put($manySectionsFile, $content);

        $response = $this->getJson(route('docs.search', ['q' => 'searchable']));

        $response->assertOk();

        $results = $response->json('results');
        if (!empty($results)) {
            foreach ($results as $result) {
                $sections = $result['sections'];
                $this->assertLessThanOrEqual(5, count($sections));
            }
        }

        File::delete($manySectionsFile);
    }

    #[Test]
    public function search_includes_correct_url_in_results(): void
    {
        $this->actingAsUser();

        $response = $this->getJson(route('docs.search', ['q' => 'search term']));

        $response->assertOk();

        $results = $response->json('results');
        $this->assertNotEmpty($results);

        $url = $results[0]['url'];
        $expectedUrl = route('docs.show', ['category' => 'test-category']);

        $this->assertEquals($expectedUrl, $url);
    }

    // ==================== Markdown Parsing Tests ====================

    #[Test]
    public function markdown_content_is_converted_to_html(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $content = $response->viewData('content');

        // Check that markdown is converted to HTML
        $this->assertStringContainsString('<h1>', $content);
        $this->assertStringContainsString('<h2>', $content);
        $this->assertStringContainsString('<p>', $content);
    }

    #[Test]
    public function markdown_bold_text_is_converted_to_strong_tags(): void
    {
        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'test-category']));

        $content = $response->viewData('content');

        // The content has **search term** which should be converted to <strong>
        $this->assertStringContainsString('<strong>', $content);
        $this->assertStringContainsString('</strong>', $content);
    }

    #[Test]
    public function markdown_with_code_blocks_is_parsed_correctly(): void
    {
        $this->actingAsUser();

        // Create a file with code blocks
        $codeFile = $this->docsPath . '/code-example.md';
        $codeContent = <<<'MD'
# Code Example

Here is a code block:

```php
echo "Hello World";
```

And inline `code` too.
MD;
        File::put($codeFile, $codeContent);

        $response = $this->get(route('docs.show', ['category' => 'code-example']));

        $content = $response->viewData('content');

        // Check for code tags
        $this->assertStringContainsString('<code>', $content);

        File::delete($codeFile);
    }

    // ==================== Authentication Tests ====================

    #[Test]
    public function guest_users_cannot_access_documentation(): void
    {
        $response = $this->get(route('docs.show'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_users_cannot_search_documentation(): void
    {
        $response = $this->get(route('docs.search', ['q' => 'test']));

        $response->assertRedirect(route('login'));
    }

    // ==================== Real Documentation Files Tests ====================

    #[Test]
    public function user_can_view_actual_deployments_documentation(): void
    {
        // Use real docs path for this test
        config(['docs.categories_path' => resource_path('docs/categories')]);

        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'deployments']));

        $response->assertOk()
            ->assertViewIs('docs.show')
            ->assertViewHas('category', 'deployments')
            ->assertSee('Deployments');
    }

    #[Test]
    public function user_can_view_actual_domains_documentation(): void
    {
        // Use real docs path for this test
        config(['docs.categories_path' => resource_path('docs/categories')]);

        $this->actingAsUser();

        $response = $this->get(route('docs.show', ['category' => 'domains']));

        $response->assertOk()
            ->assertViewIs('docs.show')
            ->assertViewHas('category', 'domains');
    }

    // ==================== Edge Cases Tests ====================

    #[Test]
    public function search_handles_special_characters_safely(): void
    {
        // Use real docs for search testing
        config(['docs.categories_path' => resource_path('docs/categories')]);

        $this->actingAsUser();

        $specialChars = ['<script>', '&', '"', "'", '<', '>'];

        foreach ($specialChars as $char) {
            $response = $this->getJson(route('docs.search', ['q' => $char]));
            $response->assertOk();
        }
    }

    #[Test]
    public function documentation_page_handles_markdown_without_headings(): void
    {
        $this->actingAsUser();

        // Create a file without any headings
        $noHeadingsFile = $this->docsPath . '/no-headings.md';
        File::put($noHeadingsFile, 'Just plain text without any headings.');

        $response = $this->get(route('docs.show', ['category' => 'no-headings']));

        $response->assertOk();

        $tableOfContents = $response->viewData('tableOfContents');
        $this->assertIsArray($tableOfContents);
        $this->assertEmpty($tableOfContents);

        File::delete($noHeadingsFile);
    }

    #[Test]
    public function search_returns_empty_array_when_docs_directory_does_not_exist(): void
    {
        $this->actingAsUser();

        // Point to a non-existent directory
        config(['docs.categories_path' => storage_path('app/non-existent-docs')]);

        $response = $this->getJson(route('docs.search', ['q' => 'test']));

        $response->assertOk()
            ->assertJson([
                'results' => [],
                'count' => 0,
            ]);
    }

    #[Test]
    public function cache_key_changes_when_file_is_modified(): void
    {
        $this->actingAsUser();

        // Get initial cache key
        $initialModified = File::lastModified($this->testMarkdownFile);
        $initialCacheKey = "docs.test-category.{$initialModified}";

        // First request
        $this->get(route('docs.show', ['category' => 'test-category']));
        $this->assertTrue(Cache::has($initialCacheKey));

        // Wait a moment and modify the file
        sleep(1);
        File::put($this->testMarkdownFile, $this->testMarkdownContent . "\n\nNew content");

        // New cache key should be different
        $newModified = File::lastModified($this->testMarkdownFile);
        $newCacheKey = "docs.test-category.{$newModified}";

        $this->assertNotEquals($initialCacheKey, $newCacheKey);

        // Second request should create new cache entry
        $this->get(route('docs.show', ['category' => 'test-category']));
        $this->assertTrue(Cache::has($newCacheKey));
    }
}
