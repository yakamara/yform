<?php

namespace Yakamara\YForm\Search;

use Override;
use Yakamara\YForm\Data\Query;

/**
 * Yes/no search for checkbox columns.
 */
class BooleanFilter extends Filter
{
    #[Override]
    public function parse(mixed $input): ?bool
    {
        return match ($input) {
            '1' => true,
            '0' => false,
            default => null,
        };
    }

    /** @param bool $value */
    #[Override]
    public function apply(Query $query, mixed $value): void
    {
        if ($value) {
            $query->where($this->field->name, true);
            return;
        }
        $query->whereAny(fn (Query $q) => $q->where($this->field->name, false)->whereNull($this->field->name));
    }
}
