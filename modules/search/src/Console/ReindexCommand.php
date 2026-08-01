<?php

namespace Liberu\Foundation\Search\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\Search\Contracts\SearchIndexer;
use Liberu\Foundation\Search\Registry\IndexableRegistry;

final class ReindexCommand extends Command
{
    protected $signature = 'search:reindex {type?}';

    protected $description = 'Rebuild authorized search indexes through the configured provider';

    public function handle(IndexableRegistry $registry, SearchIndexer $indexer): int
    {
        $types = $registry->all();
        if ($requested = $this->argument('type')) {
            $types = array_intersect_key($types, [(string) $requested => true]);
        }if ($types === []) {
            $this->error('No matching indexable search type.');

            return self::FAILURE;
        }foreach ($types as $type => $model) {
            $indexer->flush($type);
            $model::query()->chunkById(500, function ($records) use ($type, $indexer): void {
                foreach ($records as $record) {
                    $indexer->index($type, $record);
                }
            });
            $this->info("Reindexed {$type}.");
        }

return self::SUCCESS;
    }
}
