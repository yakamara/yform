<?php

namespace Yakamara\YForm\Form\Layout;

use InvalidArgumentException;

/** One field in a row with its width in grid columns (1–12). */
final readonly class Cell
{
    public function __construct(
        public string $field,
        public int $width = 12,
    ) {
        if ($width < 1 || $width > 12) {
            throw new InvalidArgumentException('The width of a layout cell must be between 1 and 12.');
        }
    }

    public static function field(string $field, int $width = 12): self
    {
        return new self($field, $width);
    }
}
