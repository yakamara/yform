<?php

namespace Yakamara\YForm\Search;

use Closure;
use Override;
use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Field\Field;

use function in_array;
use function is_array;
use function is_string;

/**
 * Search for records having one of the selected options.
 *
 * By default the column must equal one of the selected values. Fields storing several values (JSON lists, pivot
 * tables) pass their own condition.
 */
class ChoiceFilter extends Filter
{
    /**
     * @param array<int|string, string>                         $options value → label
     * @param (Closure(Query, list<int|string>): void)|null $apply
     */
    public function __construct(
        Field $field,
        public readonly array $options,
        private readonly ?Closure $apply = null,
    ) {
        parent::__construct($field);
    }

    /** @return list<int|string>|null the selected option keys */
    #[Override]
    public function parse(mixed $input): ?array
    {
        $input = is_string($input) ? [$input] : $input;
        if (!is_array($input)) {
            return null;
        }
        $selected = [];
        foreach ($this->options as $key => $label) {
            if (in_array((string) $key, $input, true)) {
                $selected[] = $key;
            }
        }
        return [] === $selected ? null : $selected;
    }

    /** @param list<int|string> $value */
    #[Override]
    public function apply(Query $query, mixed $value): void
    {
        if (null !== $this->apply) {
            ($this->apply)($query, $value);
            return;
        }
        $query->whereIn($this->field->name, $value);
    }
}
