<?php

namespace Yakamara\YForm\Data;

use function max;

/**
 * One page of a query result.
 *
 * @template T of Record
 */
final readonly class Paginated
{
    public int $pages;

    /** @param Collection<T> $items */
    public function __construct(
        public Collection $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
        $this->pages = max(1, (int) ceil($total / max(1, $perPage)));
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages;
    }
}
