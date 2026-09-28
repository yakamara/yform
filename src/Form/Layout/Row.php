<?php

namespace Yakamara\YForm\Form\Layout;

use function count;
use function intdiv;
use function is_string;
use function max;

/** Fields side by side. */
final class Row
{
    /** @var list<Cell> */
    public private(set) array $cells = [];

    /** Strings get equal widths: `new Row('zip', 'city')` → 6 + 6. */
    public function __construct(string|Cell ...$cells)
    {
        $auto = max(1, intdiv(12, max(1, count($cells))));
        foreach ($cells as $cell) {
            $this->cells[] = is_string($cell) ? new Cell($cell, $auto) : $cell;
        }
    }
}
