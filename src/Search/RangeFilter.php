<?php

namespace Yakamara\YForm\Search;

use Closure;
use Override;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Field\Field;

use function is_array;
use function is_string;
use function trim;

/**
 * Search for values between two bounds (numbers, dates, times); either bound may stay empty.
 *
 * The converter turns one submitted bound into the value compared with the column, or `null` for invalid input.
 * `$upper` tells whether the upper bound is converted, so a day can cover the whole day of a datetime column.
 */
class RangeFilter extends Filter
{
    /** @param Closure(string $input, bool $upper): mixed $convert */
    public function __construct(
        Field $field,
        public readonly string $inputType,
        private readonly Closure $convert,
    ) {
        parent::__construct($field);
    }

    /** @return array{from: mixed, to: mixed}|null */
    #[Override]
    public function parse(mixed $input): ?array
    {
        if (!is_array($input)) {
            return null;
        }
        $from = $this->bound($input['from'] ?? null, false);
        $to = $this->bound($input['to'] ?? null, true);
        return null === $from && null === $to ? null : ['from' => $from, 'to' => $to];
    }

    /** @param array{from: mixed, to: mixed} $value */
    #[Override]
    public function apply(Query $query, mixed $value): void
    {
        if (null !== $value['from']) {
            $query->where($this->field->name, '>=', $value['from']);
        }
        if (null !== $value['to']) {
            $query->where($this->field->name, '<=', $value['to']);
        }
    }

    private function bound(mixed $input, bool $upper): mixed
    {
        if (!is_string($input) || '' === trim($input)) {
            return null;
        }
        return ($this->convert)(trim($input), $upper);
    }
}
