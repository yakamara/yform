<?php

namespace Yakamara\YForm\Form\Layout;

/** Group of rows, rendered as fieldset when it has a legend. */
final class Section
{
    /** @var list<Row> */
    public private(set) array $rows = [];

    public function __construct(
        public readonly ?string $legend = null,
    ) {}

    /** Adds a row: `->row('zip', 'city')` or `->row(Cell::field('street', 8), Cell::field('no', 4))`. */
    public function row(string|Cell ...$cells): self
    {
        $this->rows[] = new Row(...$cells);
        return $this;
    }

    /** Adds one row per field. */
    public function fields(string ...$fields): self
    {
        foreach ($fields as $field) {
            $this->rows[] = new Row($field);
        }
        return $this;
    }

    /** @internal */
    public function addRow(Row $row): void
    {
        $this->rows[] = $row;
    }

    /** @internal Removes rows without cells. */
    public function withoutEmptyRows(): self
    {
        $section = new self($this->legend);
        foreach ($this->rows as $row) {
            if ([] !== $row->cells) {
                $section->rows[] = $row;
            }
        }
        return $section;
    }
}
