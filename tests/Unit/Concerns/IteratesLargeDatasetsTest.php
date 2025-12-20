<?php

declare(strict_types=1);

namespace Tests\Unit\Concerns;

use App\Concerns\IteratesLargeDatasets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for the IteratesLargeDatasets trait.
 *
 * These tests use mocks to verify the trait's behavior without requiring
 * a real database, avoiding SQLite transaction issues with cursor().
 */
class IteratesLargeDatasetsTest extends TestCase
{
    private object $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class using the trait
        $this->testClass = new class {
            use IteratesLargeDatasets;

            public function testProcessByCursor($query, $callback, $batchSize = 100): int
            {
                return $this->processByCursor($query, $callback, $batchSize);
            }

            public function testMapByCursor($query, $callback, $limit = null): Collection
            {
                return $this->mapByCursor($query, $callback, $limit);
            }

            public function testLazyQuery($query): LazyCollection
            {
                return $this->lazyQuery($query);
            }

            public function testProcessInChunks($query, $chunkSize, $callback): int
            {
                return $this->processInChunks($query, $chunkSize, $callback);
            }

            public function testStreamTransform($query, $transformer): LazyCollection
            {
                return $this->streamTransform($query, $transformer);
            }

            public function testCountByCursor($query, $condition): int
            {
                return $this->countByCursor($query, $condition);
            }

            public function testFindByCursor($query, $condition)
            {
                return $this->findByCursor($query, $condition);
            }

            public function testPartitionByCursor($query, $condition, $limit = null): array
            {
                return $this->partitionByCursor($query, $condition, $limit);
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock query builder that returns items via cursor
     */
    private function createMockQuery(array $items): MockInterface
    {
        $lazyCollection = LazyCollection::make($items);

        $mock = Mockery::mock(Builder::class);
        $mock->shouldReceive('cursor')->andReturn($lazyCollection);

        return $mock;
    }

    /**
     * Create simple objects to simulate model behavior
     */
    private function createMockModels(int $count, array $attributes = []): array
    {
        $models = [];
        for ($i = 0; $i < $count; $i++) {
            $model = new \stdClass();
            $model->id = $i + 1;
            $model->name = "Model " . ($i + 1);
            $model->status = $attributes['status'] ?? 'active';
            $models[] = $model;
        }
        return $models;
    }

    public function test_process_by_cursor_iterates_all_records(): void
    {
        $models = $this->createMockModels(5);
        $query = $this->createMockQuery($models);

        $processed = [];
        $count = $this->testClass->testProcessByCursor(
            $query,
            function ($model) use (&$processed) {
                $processed[] = $model->id;
            }
        );

        $this->assertEquals(5, $count);
        $this->assertCount(5, $processed);
        $this->assertEquals([1, 2, 3, 4, 5], $processed);
    }

    public function test_map_by_cursor_transforms_records(): void
    {
        $models = $this->createMockModels(3);
        $query = $this->createMockQuery($models);

        $result = $this->testClass->testMapByCursor(
            $query,
            fn($m) => $m->name
        );

        $this->assertCount(3, $result);
        $this->assertEquals(['Model 1', 'Model 2', 'Model 3'], $result->toArray());
    }

    public function test_map_by_cursor_respects_limit(): void
    {
        $models = $this->createMockModels(10);
        $query = $this->createMockQuery($models);

        $result = $this->testClass->testMapByCursor(
            $query,
            fn($m) => $m->id,
            3
        );

        $this->assertCount(3, $result);
        $this->assertEquals([1, 2, 3], $result->toArray());
    }

    public function test_lazy_query_returns_lazy_collection(): void
    {
        $models = $this->createMockModels(3);
        $query = $this->createMockQuery($models);

        $lazy = $this->testClass->testLazyQuery($query);

        $this->assertInstanceOf(LazyCollection::class, $lazy);
        $this->assertCount(3, $lazy->all());
    }

    public function test_stream_transform_lazily_transforms(): void
    {
        $models = $this->createMockModels(3, ['status' => 'running']);
        $query = $this->createMockQuery($models);

        $stream = $this->testClass->testStreamTransform(
            $query,
            fn($m) => ['id' => $m->id, 'status' => $m->status]
        );

        $this->assertInstanceOf(LazyCollection::class, $stream);

        $results = $stream->all();
        $this->assertCount(3, $results);
        $this->assertEquals('running', $results[0]['status']);
    }

    public function test_count_by_cursor_counts_matching_records(): void
    {
        $runningModels = $this->createMockModels(3, ['status' => 'running']);
        $stoppedModels = $this->createMockModels(2, ['status' => 'stopped']);
        $allModels = array_merge($runningModels, $stoppedModels);
        $query = $this->createMockQuery($allModels);

        $count = $this->testClass->testCountByCursor(
            $query,
            fn($m) => $m->status === 'running'
        );

        $this->assertEquals(3, $count);
    }

    public function test_find_by_cursor_returns_first_match(): void
    {
        $models = [
            $this->createMockModel(1, 'Alpha'),
            $this->createMockModel(2, 'Beta'),
            $this->createMockModel(3, 'Gamma'),
        ];
        $query = $this->createMockQuery($models);

        $found = $this->testClass->testFindByCursor(
            $query,
            fn($m) => str_starts_with($m->name, 'B')
        );

        $this->assertNotNull($found);
        $this->assertEquals('Beta', $found->name);
    }

    public function test_find_by_cursor_returns_null_when_no_match(): void
    {
        $models = $this->createMockModels(3);
        $query = $this->createMockQuery($models);

        $found = $this->testClass->testFindByCursor(
            $query,
            fn($m) => false // Never matches
        );

        $this->assertNull($found);
    }

    public function test_partition_by_cursor_splits_records(): void
    {
        $runningModels = $this->createMockModels(3, ['status' => 'running']);
        $stoppedModels = $this->createMockModels(2, ['status' => 'stopped']);
        $allModels = array_merge($runningModels, $stoppedModels);
        $query = $this->createMockQuery($allModels);

        [$running, $stopped] = $this->testClass->testPartitionByCursor(
            $query,
            fn($m) => $m->status === 'running'
        );

        $this->assertCount(3, $running);
        $this->assertCount(2, $stopped);
    }

    public function test_partition_by_cursor_respects_limit(): void
    {
        $models = $this->createMockModels(20);
        $query = $this->createMockQuery($models);

        [$matching, $notMatching] = $this->testClass->testPartitionByCursor(
            $query,
            fn($m) => $m->id <= 10,
            5
        );

        $this->assertEquals(5, $matching->count() + $notMatching->count());
    }

    public function test_process_by_cursor_handles_empty_query(): void
    {
        $query = $this->createMockQuery([]);

        $processed = [];
        $count = $this->testClass->testProcessByCursor(
            $query,
            function ($model) use (&$processed) {
                $processed[] = $model->id;
            }
        );

        $this->assertEquals(0, $count);
        $this->assertEmpty($processed);
    }

    public function test_map_by_cursor_handles_empty_query(): void
    {
        $query = $this->createMockQuery([]);

        $result = $this->testClass->testMapByCursor(
            $query,
            fn($m) => $m->id
        );

        $this->assertCount(0, $result);
    }

    private function createMockModel(int $id, string $name, string $status = 'active'): \stdClass
    {
        $model = new \stdClass();
        $model->id = $id;
        $model->name = $name;
        $model->status = $status;

        return $model;
    }
}
