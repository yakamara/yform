<?php

namespace Yakamara\YForm\Search;

use Override;
use Yakamara\YForm\Data\Query;

use function addcslashes;
use function is_string;
use function ltrim;
use function preg_match;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Text search on one column.
 *
 *  - `tee`     contains „tee“
 *  - `tee*`    starts with „tee“ (`*` is a wildcard, the pattern then covers the whole value)
 *  - `"tee"`   exactly „tee“
 *  - `""`      empty
 *  - `!…`      negates any of the above
 */
class TextFilter extends Filter
{
    /** @return array{negate: bool, pattern: ?string}|null `pattern` is a LIKE pattern, `null` stands for „empty“ */
    #[Override]
    public function parse(mixed $input): ?array
    {
        if (!is_string($input)) {
            return null;
        }
        $term = trim($input);
        $negate = str_starts_with($term, '!');
        if ($negate) {
            $term = ltrim(substr($term, 1));
        }
        if ('' === $term) {
            return null;
        }
        if ('""' === $term) {
            return ['negate' => $negate, 'pattern' => null];
        }
        if (1 === preg_match('/^"(.+)"$/s', $term, $m)) {
            return ['negate' => $negate, 'pattern' => addcslashes($m[1], '\\%_')];
        }
        $pattern = addcslashes($term, '\\%_');
        $pattern = str_contains($term, '*') ? str_replace('*', '%', $pattern) : '%' . $pattern . '%';
        return ['negate' => $negate, 'pattern' => $pattern];
    }

    /** @param array{negate: bool, pattern: ?string} $value */
    #[Override]
    public function apply(Query $query, mixed $value): void
    {
        $column = $query->column($this->field->name);
        if (null === $value['pattern']) {
            $query->whereRaw($value['negate'] ? "($column IS NOT NULL AND $column <> '')" : "($column IS NULL OR $column = '')");
            return;
        }
        $query->whereRaw($value['negate'] ? "($column IS NULL OR $column NOT LIKE ?)" : "$column LIKE ?", [$value['pattern']]);
    }
}
