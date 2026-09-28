<?php

namespace Yakamara\YForm\Schema;

/**
 * Differences between a table definition and the database, as computed by {@see SchemaSync::plan()}.
 */
final readonly class SchemaPlan
{
    /**
     * @param list<string> $add columns to add
     * @param list<string> $modify columns whose definition changes
     * @param list<string> $indexes indexes to add or change
     * @param list<string> $obsolete columns in the database without a field
     * @param list<string> $additional additional tables (e.g. pivot tables) to create or extend
     */
    public function __construct(
        public string $table,
        public bool $create,
        public array $add = [],
        public array $modify = [],
        public array $indexes = [],
        public array $obsolete = [],
        public array $additional = [],
    ) {}

    /** Whether applying the plan (without dropping obsolete columns) changes anything. */
    public function hasChanges(): bool
    {
        return $this->create || [] !== $this->add || [] !== $this->modify || [] !== $this->indexes || [] !== $this->additional;
    }
}
