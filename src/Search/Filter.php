<?php

namespace Yakamara\YForm\Search;

use Yakamara\YForm\Data\Query;
use Yakamara\YForm\Field\Field;
use Yakamara\YForm\View\ViewRenderer;

/**
 * Control of the detailed search in the backend list, provided by {@see Field::searchFilter()} for fields marked
 * as {@see Field::filterable()}.
 *
 * A filter turns the submitted input into a search value ({@see parse()}) and restricts the query with it
 * ({@see apply()}). The control is rendered by the co-located `ClassName.view.php`.
 */
abstract class Filter
{
    public function __construct(
        public readonly Field $field,
    ) {}

    /** Normalised search value of the submitted input, `null` when the filter is not active. */
    abstract public function parse(mixed $input): mixed;

    /** Restricts the query to the records matching the value returned by {@see parse()}. */
    abstract public function apply(Query $query, mixed $value): void;

    /** @param string $name input name, e.g. `filter[title]` */
    public function render(string $name, string $id, mixed $input): string
    {
        return ViewRenderer::render(static::class, $this, $name, $id, $input);
    }
}
