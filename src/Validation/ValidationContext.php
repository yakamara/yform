<?php

namespace Yakamara\YForm\Validation;

use Yakamara\YForm\Data\Record;
use Yakamara\YForm\Table\Table;

/**
 * What a rule may look at besides the value itself: the sibling values (for comparisons) and, when a table
 * record is validated, the table and record (for uniqueness checks).
 */
final readonly class ValidationContext
{
    /** @param array<string, mixed> $values */
    public function __construct(
        public array $values,
        public ?Table $table = null,
        public ?Record $record = null,
    ) {}

    public function value(string $field): mixed
    {
        return $this->values[$field] ?? null;
    }
}
