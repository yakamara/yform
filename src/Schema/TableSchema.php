<?php

namespace Yakamara\YForm\Schema;

use Redaxo\Core\Database\Column;
use Redaxo\Core\Database\Index;

/**
 * An additional table a field needs (e.g. the pivot table of a many-to-many relation), synchronised together
 * with the table of the field.
 */
final readonly class TableSchema
{
    /**
     * @param list<Column> $columns
     * @param list<string> $primaryKey
     * @param list<Index> $indexes
     */
    public function __construct(
        public string $name,
        public array $columns,
        public array $primaryKey,
        public array $indexes = [],
    ) {}
}
