<?php

namespace Yakamara\YForm\Field\Relation;

use Yakamara\YForm\Table\Table;

use function array_diff;
use function array_keys;
use function array_values;

/**
 * Request cache of record labels, so lists with relation columns do not query once per cell.
 *
 * @internal
 */
final class Labels
{
    /** @var array<string, array<int, string>> */
    private static array $cache = [];

    /**
     * @param list<int> $ids
     *
     * @return array<int, string> id → label (missing records are omitted)
     */
    public static function get(Table $table, array $ids): array
    {
        $cached = self::$cache[$table->name] ?? [];
        $missing = array_values(array_diff($ids, array_keys($cached)));
        if ([] !== $missing) {
            foreach ($table->query()->whereIn('id', $missing)->find() as $record) {
                $cached[(int) $record->id] = $record->label();
            }
            foreach ($missing as $id) {
                $cached[$id] ??= '';
            }
            self::$cache[$table->name] = $cached;
        }

        $labels = [];
        foreach ($ids as $id) {
            if ('' !== ($cached[$id] ?? '')) {
                $labels[$id] = $cached[$id];
            }
        }
        return $labels;
    }

    /** Warms the cache for many ids at once (e.g. all ids of a list page). @param list<int> $ids */
    public static function preload(Table $table, array $ids): void
    {
        self::get($table, $ids);
    }

    public static function forget(Table $table): void
    {
        unset(self::$cache[$table->name]);
    }
}
