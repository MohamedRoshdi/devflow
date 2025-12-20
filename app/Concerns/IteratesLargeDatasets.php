<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Trait for memory-efficient iteration over large datasets.
 *
 * Provides cursor-based and chunked iteration methods to prevent
 * memory exhaustion when processing large numbers of records.
 */
trait IteratesLargeDatasets
{
    /**
     * Process records using cursor for memory efficiency.
     *
     * Use this method when you need to process a large number of records
     * and don't need to keep them all in memory at once.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): void $callback The callback to process each record
     * @param int $batchSize Number of records to process before yielding
     * @return int Number of records processed
     */
    protected function processByCursor(Builder $query, callable $callback, int $batchSize = 100): int
    {
        $count = 0;

        foreach ($query->cursor() as $record) {
            $callback($record);
            $count++;

            // Periodically free memory
            if ($count % $batchSize === 0) {
                gc_collect_cycles();
            }
        }

        return $count;
    }

    /**
     * Process records and collect results using cursor.
     *
     * Similar to processByCursor but collects and returns results.
     * Use with caution on very large datasets.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @template TResult
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): TResult $callback The callback to process each record
     * @param int|null $limit Maximum number of results to collect (null for unlimited)
     * @return Collection<int, TResult>
     */
    protected function mapByCursor(Builder $query, callable $callback, ?int $limit = null): Collection
    {
        $results = collect();
        $count = 0;

        foreach ($query->cursor() as $record) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            $results->push($callback($record));
            $count++;
        }

        return $results;
    }

    /**
     * Get a lazy collection from query for streaming processing.
     *
     * Returns a LazyCollection that can be iterated without loading
     * all records into memory at once.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @return LazyCollection<int, TModel>
     */
    protected function lazyQuery(Builder $query): LazyCollection
    {
        return $query->cursor();
    }

    /**
     * Process records in chunks with a callback.
     *
     * Use this when you need to batch operations but still want
     * memory efficiency.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @param int $chunkSize Number of records per chunk
     * @param callable(Collection<int, TModel>): void $callback The callback to process each chunk
     * @return int Total number of records processed
     */
    protected function processInChunks(Builder $query, int $chunkSize, callable $callback): int
    {
        $totalProcessed = 0;

        $query->chunk($chunkSize, function (Collection $chunk) use ($callback, &$totalProcessed): bool {
            $callback($chunk);
            $totalProcessed += $chunk->count();

            return true;
        });

        return $totalProcessed;
    }

    /**
     * Stream large query results with transformation.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @template TResult
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): TResult $transformer Transform function for each record
     * @return LazyCollection<int, TResult>
     */
    protected function streamTransform(Builder $query, callable $transformer): LazyCollection
    {
        return $query->cursor()->map($transformer);
    }

    /**
     * Efficiently count records matching condition from cursor.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): bool $condition Condition to check
     * @return int Count of matching records
     */
    protected function countByCursor(Builder $query, callable $condition): int
    {
        $count = 0;

        foreach ($query->cursor() as $record) {
            if ($condition($record)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Find first record matching condition using cursor.
     *
     * More memory efficient than loading all records when you only need one.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): bool $condition Condition to check
     * @return TModel|null First matching record or null
     */
    protected function findByCursor(Builder $query, callable $condition): mixed
    {
        foreach ($query->cursor() as $record) {
            if ($condition($record)) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Partition records into two collections based on condition.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): bool $condition Partition condition
     * @param int|null $limit Maximum total records to process
     * @return array{0: Collection<int, TModel>, 1: Collection<int, TModel>} [matching, not matching]
     */
    protected function partitionByCursor(Builder $query, callable $condition, ?int $limit = null): array
    {
        $matching = collect();
        $notMatching = collect();
        $count = 0;

        foreach ($query->cursor() as $record) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            if ($condition($record)) {
                $matching->push($record);
            } else {
                $notMatching->push($record);
            }

            $count++;
        }

        return [$matching, $notMatching];
    }

    /**
     * Batch update records efficiently using cursor.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query The query builder
     * @param callable(TModel): (array<string, mixed>|null) $getUpdates Callback returning update data or null to skip
     * @param int $batchSize Number of updates to accumulate before applying
     * @return int Number of records updated
     */
    protected function batchUpdateByCursor(Builder $query, callable $getUpdates, int $batchSize = 100): int
    {
        $updated = 0;
        $batch = [];
        $modelClass = null;

        foreach ($query->cursor() as $record) {
            if ($modelClass === null) {
                $modelClass = $record::class;
            }

            $updates = $getUpdates($record);
            if (is_array($updates)) {
                $batch[] = ['id' => $record->getKey(), 'updates' => $updates];
            }

            if (count($batch) >= $batchSize) {
                $updated += $this->applyBatchUpdates($modelClass, $batch);
                $batch = [];
            }
        }

        // Apply remaining updates
        if (count($batch) > 0 && $modelClass !== null) {
            $updated += $this->applyBatchUpdates($modelClass, $batch);
        }

        return $updated;
    }

    /**
     * Apply batch updates to records.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param array<int, array{id: mixed, updates: array<string, mixed>}> $batch
     * @return int Number of records updated
     */
    private function applyBatchUpdates(string $modelClass, array $batch): int
    {
        $updated = 0;

        foreach ($batch as $item) {
            $modelClass::where('id', $item['id'])->update($item['updates']);
            $updated++;
        }

        return $updated;
    }
}
