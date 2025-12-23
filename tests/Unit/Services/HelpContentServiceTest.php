<?php

declare(strict_types=1);

namespace Tests\Unit\Services;


use PHPUnit\Framework\Attributes\Test;
use App\Models\HelpContent;
use App\Models\HelpContentRelated;
use App\Models\HelpInteraction;
use App\Services\HelpContentService;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class HelpContentServiceTest extends TestCase
{
    

    protected HelpContentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new HelpContentService();

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_gets_help_content_by_key(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'project-deployment',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->getByKey('project-deployment');

        // Assert
        $this->assertInstanceOf(HelpContent::class, $result);
        $this->assertEquals('project-deployment', $result->key);
        $this->assertEquals($helpContent->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_help_content_not_found(): void
    {
        // Act
        $result = $this->service->getByKey('non-existent-key');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_does_not_return_inactive_help_content(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'key' => 'inactive-help',
            'is_active' => false,
        ]);

        // Act
        $result = $this->service->getByKey('inactive-help');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_caches_help_content_by_key(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'cached-help',
            'is_active' => true,
        ]);

        // Act
        $result1 = $this->service->getByKey('cached-help');
        $result2 = $this->service->getByKey('cached-help');

        // Assert
        $this->assertEquals($result1->id, $result2->id);
        $this->assertTrue(Cache::has('help_content_cached-help'));
    }

    #[Test]
    public function it_gets_help_content_by_category(): void
    {
        // Arrange
        HelpContent::factory()->count(3)->create([
            'category' => 'deployment',
            'is_active' => true,
        ]);
        HelpContent::factory()->count(2)->create([
            'category' => 'servers',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->getByCategory('deployment');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        $result->each(function ($item) {
            $this->assertEquals('deployment', $item->category);
        });
    }

    #[Test]
    public function it_caches_help_content_by_category(): void
    {
        // Arrange
        HelpContent::factory()->count(2)->create([
            'category' => 'servers',
            'is_active' => true,
        ]);

        // Act
        $result1 = $this->service->getByCategory('servers');
        $result2 = $this->service->getByCategory('servers');

        // Assert
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertTrue(Cache::has('help_content_category_servers'));
    }

    #[Test]
    public function it_searches_help_content(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'title' => 'How to deploy a project',
            'brief' => 'Learn how to deploy projects',
            'key' => 'project-deployment',
            'is_active' => true,
            'view_count' => 100,
        ]);
        HelpContent::factory()->create([
            'title' => 'Server configuration',
            'brief' => 'Configure your server',
            'key' => 'server-config',
            'is_active' => true,
            'view_count' => 50,
        ]);
        HelpContent::factory()->create([
            'title' => 'Deploy with Docker',
            'brief' => 'Docker deployment guide',
            'key' => 'docker-deploy',
            'is_active' => true,
            'view_count' => 75,
        ]);

        // Act
        $result = $this->service->search('deploy');

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        // Should be ordered by view_count desc
        $this->assertEquals('project-deployment', $result->first()->key);
    }

    #[Test]
    public function it_limits_search_results_to_10(): void
    {
        // Arrange
        HelpContent::factory()->count(15)->create([
            'title' => 'Test help content',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->search('Test');

        // Assert
        $this->assertCount(10, $result);
    }

    #[Test]
    public function it_does_not_cache_search_results(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'title' => 'Searchable content',
            'is_active' => true,
        ]);

        // Act
        $this->service->search('Searchable');

        // Assert
        // Search results should not be cached
        $this->assertFalse(Cache::has('help_content_search_Searchable'));
    }

    #[Test]
    public function it_records_view_successfully(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-help',
            'is_active' => true,
            'view_count' => 5,
        ]);

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $this->service->recordView('test-help', 1);

        // Assert
        $this->assertEquals(6, $helpContent->fresh()->view_count);
        $this->assertDatabaseHas('help_interactions', [
            'user_id' => 1,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'view',
            'ip_address' => '127.0.0.1',
        ]);
    }

    #[Test]
    public function it_records_view_without_user_id(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-help',
            'is_active' => true,
            'view_count' => 0,
        ]);

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->andReturn('192.168.1.1');
        $request->shouldReceive('userAgent')->andReturn('Chrome/91.0');

        // Act
        $this->service->recordView('test-help');

        // Assert
        $this->assertEquals(1, $helpContent->fresh()->view_count);
        $this->assertDatabaseHas('help_interactions', [
            'user_id' => null,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'view',
        ]);
    }

    #[Test]
    public function it_does_not_record_view_for_non_existent_key(): void
    {
        // Arrange
        $initialCount = HelpInteraction::count();

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->never();
        $request->shouldReceive('userAgent')->never();

        // Act
        $this->service->recordView('non-existent-key', 1);

        // Assert
        $this->assertEquals($initialCount, HelpInteraction::count());
    }

    #[Test]
    public function it_records_helpful_feedback_successfully(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-help',
            'is_active' => true,
            'helpful_count' => 3,
        ]);

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $this->service->recordHelpful('test-help', 1);

        // Assert
        $this->assertEquals(4, $helpContent->fresh()->helpful_count);
        $this->assertDatabaseHas('help_interactions', [
            'user_id' => 1,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'helpful',
        ]);
    }

    #[Test]
    public function it_does_not_record_helpful_for_non_existent_key(): void
    {
        // Arrange
        $initialCount = HelpInteraction::count();

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->never();
        $request->shouldReceive('userAgent')->never();

        // Act
        $this->service->recordHelpful('non-existent-key', 1);

        // Assert
        $this->assertEquals($initialCount, HelpInteraction::count());
    }

    #[Test]
    public function it_records_not_helpful_feedback_successfully(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-help',
            'is_active' => true,
            'not_helpful_count' => 1,
        ]);

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $this->service->recordNotHelpful('test-help', 1);

        // Assert
        $this->assertEquals(2, $helpContent->fresh()->not_helpful_count);
        $this->assertDatabaseHas('help_interactions', [
            'user_id' => 1,
            'help_content_id' => $helpContent->id,
            'interaction_type' => 'not_helpful',
        ]);
    }

    #[Test]
    public function it_gets_popular_help_content(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'is_active' => true,
            'view_count' => 100,
        ]);
        HelpContent::factory()->create([
            'is_active' => true,
            'view_count' => 200,
        ]);
        HelpContent::factory()->create([
            'is_active' => true,
            'view_count' => 50,
        ]);

        // Act
        $result = $this->service->getPopularHelp(10);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
        // Should be ordered by view_count desc
        $this->assertEquals(200, $result->first()->view_count);
        $this->assertEquals(50, $result->last()->view_count);
    }

    #[Test]
    public function it_limits_popular_help_results(): void
    {
        // Arrange
        HelpContent::factory()->count(15)->create([
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->getPopularHelp(5);

        // Assert
        $this->assertCount(5, $result);
    }

    #[Test]
    public function it_caches_popular_help_results(): void
    {
        // Arrange
        HelpContent::factory()->count(3)->create([
            'is_active' => true,
        ]);

        // Act
        $result1 = $this->service->getPopularHelp(10);
        $result2 = $this->service->getPopularHelp(10);

        // Assert
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertTrue(Cache::has('popular_help_10'));
    }

    #[Test]
    public function it_does_not_include_inactive_help_in_popular_results(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'is_active' => true,
            'view_count' => 100,
        ]);
        HelpContent::factory()->create([
            'is_active' => false,
            'view_count' => 1000,
        ]);

        // Act
        $result = $this->service->getPopularHelp(10);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is_active);
    }

    #[Test]
    public function it_gets_most_helpful_content(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'is_active' => true,
            'helpful_count' => 90,
            'not_helpful_count' => 10,
        ]);
        HelpContent::factory()->create([
            'is_active' => true,
            'helpful_count' => 50,
            'not_helpful_count' => 50,
        ]);
        HelpContent::factory()->create([
            'is_active' => true,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);

        // Act
        $result = $this->service->getMostHelpful(10);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        // Should only include items with helpful_count > 0
        $this->assertCount(2, $result);
        // First item should have highest helpful ratio
        $this->assertEquals(90, $result->first()->helpful_count);
    }

    #[Test]
    public function it_limits_most_helpful_results(): void
    {
        // Arrange
        HelpContent::factory()->count(15)->create([
            'is_active' => true,
            'helpful_count' => 10,
        ]);

        // Act
        $result = $this->service->getMostHelpful(5);

        // Assert
        $this->assertCount(5, $result);
    }

    #[Test]
    public function it_caches_most_helpful_results(): void
    {
        // Arrange
        HelpContent::factory()->count(3)->create([
            'is_active' => true,
            'helpful_count' => 10,
        ]);

        // Act
        $result1 = $this->service->getMostHelpful(10);
        $result2 = $this->service->getMostHelpful(10);

        // Assert
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertTrue(Cache::has('most_helpful_help_10'));
    }

    #[Test]
    public function it_gets_related_help_content(): void
    {
        // Arrange
        $mainHelp = HelpContent::factory()->create([
            'key' => 'main-help',
            'is_active' => true,
        ]);
        $relatedHelp1 = HelpContent::factory()->create(['is_active' => true]);
        $relatedHelp2 = HelpContent::factory()->create(['is_active' => true]);

        HelpContentRelated::create([
            'help_content_id' => $mainHelp->id,
            'related_help_content_id' => $relatedHelp1->id,
            'relevance_score' => 90,
        ]);
        HelpContentRelated::create([
            'help_content_id' => $mainHelp->id,
            'related_help_content_id' => $relatedHelp2->id,
            'relevance_score' => 70,
        ]);

        // Act
        $result = $this->service->getRelatedHelp('main-help', 5);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        // Should be ordered by relevance_score desc
        $this->assertEquals($relatedHelp1->id, $result->first()->id);
    }

    #[Test]
    public function it_limits_related_help_results(): void
    {
        // Arrange
        $mainHelp = HelpContent::factory()->create([
            'key' => 'main-help',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 10; $i++) {
            $related = HelpContent::factory()->create(['is_active' => true]);
            HelpContentRelated::create([
                'help_content_id' => $mainHelp->id,
                'related_help_content_id' => $related->id,
                'relevance_score' => 50 + $i,
            ]);
        }

        // Act
        $result = $this->service->getRelatedHelp('main-help', 3);

        // Assert
        $this->assertCount(3, $result);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_related_help_found(): void
    {
        // Arrange
        HelpContent::factory()->create([
            'key' => 'main-help',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->getRelatedHelp('main-help', 5);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_returns_empty_collection_for_non_existent_help_key(): void
    {
        // Act
        $result = $this->service->getRelatedHelp('non-existent-key', 5);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function it_clears_all_cache(): void
    {
        // Arrange
        Cache::put('help_content_test-key', 'value', 3600);
        Cache::put('help_content_category_deployment', 'value', 3600);
        Cache::put('popular_help_10', 'value', 3600);

        // Act
        $this->service->clearCache();

        // Assert
        $this->assertFalse(Cache::has('help_content_test-key'));
        $this->assertFalse(Cache::has('help_content_category_deployment'));
        $this->assertFalse(Cache::has('popular_help_10'));
    }

    #[Test]
    public function it_handles_cache_retrieval_correctly(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'cached-test',
            'is_active' => true,
        ]);

        // First call - should cache the result
        $result1 = $this->service->getByKey('cached-test');

        // Delete from database
        $helpContent->delete();

        // Second call - should return cached result even though deleted from DB
        $result2 = $this->service->getByKey('cached-test');

        // Assert
        $this->assertNotNull($result1);
        $this->assertNotNull($result2);
        $this->assertEquals($result1->id, $result2->id);
    }

    #[Test]
    public function it_uses_different_cache_keys_for_different_limits(): void
    {
        // Arrange
        HelpContent::factory()->count(15)->create([
            'is_active' => true,
            'view_count' => 100,
        ]);

        // Act
        $this->service->getPopularHelp(5);
        $this->service->getPopularHelp(10);

        // Assert
        $this->assertTrue(Cache::has('popular_help_5'));
        $this->assertTrue(Cache::has('popular_help_10'));
    }

    #[Test]
    public function it_records_interactions_with_correct_metadata(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-help',
            'is_active' => true,
        ]);

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->andReturn('192.168.1.100');
        $request->shouldReceive('userAgent')->andReturn('TestAgent/1.0');

        // Act
        $this->service->recordView('test-help', 42);

        // Assert
        $interaction = HelpInteraction::latest()->first();
        $this->assertEquals(42, $interaction->user_id);
        $this->assertEquals($helpContent->id, $interaction->help_content_id);
        $this->assertEquals('view', $interaction->interaction_type);
        $this->assertEquals('192.168.1.100', $interaction->ip_address);
        $this->assertEquals('TestAgent/1.0', $interaction->user_agent);
    }

    #[Test]
    public function it_handles_multiple_views_from_same_user(): void
    {
        // Arrange
        $helpContent = HelpContent::factory()->create([
            'key' => 'test-help',
            'is_active' => true,
            'view_count' => 0,
        ]);

        $this->app->instance('request', $request = Mockery::mock());
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('userAgent')->andReturn('Mozilla/5.0');

        // Act
        $this->service->recordView('test-help', 1);
        $this->service->recordView('test-help', 1);
        $this->service->recordView('test-help', 1);

        // Assert
        $this->assertEquals(3, $helpContent->fresh()->view_count);
        $this->assertEquals(3, HelpInteraction::where('help_content_id', $helpContent->id)->count());
    }
}
