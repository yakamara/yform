<?php

namespace Yakamara\YForm\Form\Layout;

use Closure;
use InvalidArgumentException;
use Yakamara\YForm\Field\Field;

use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

/**
 * Arrangement of the fields of a form: tabs → sections → rows → cells.
 *
 * Built in code (`Table::layout()`, `FormDefinition::layout()`) or with the backend designer, which stores the
 * same structure as JSON next to the class. Before rendering, {@see resolve()} drops unknown fields and appends
 * fields that are not placed, so a new field never goes missing.
 *
 * ```php
 * return $layout
 *     ->tab('Allgemein', fn (Tab $t) => $t
 *         ->row('name')
 *         ->row(Cell::field('price', 4), Cell::field('status', 8)))
 *     ->tab('Adresse', fn (Tab $t) => $t
 *         ->section('Anschrift', fn (Section $s) => $s->row('zip', 'city')));
 * ```
 */
final class Layout
{
    /** @var list<Tab> */
    public private(set) array $tabs = [];

    public function tab(?string $label, ?Closure $callback = null): self
    {
        $tab = new Tab($label);
        if (null !== $callback) {
            $callback($tab);
        }
        $this->tabs[] = $tab;
        return $this;
    }

    /** Section in the last tab (created on demand). */
    public function section(?string $legend, ?Closure $callback = null): self
    {
        $this->currentTab()->section($legend, $callback);
        return $this;
    }

    public function row(string|Cell ...$cells): self
    {
        $this->currentTab()->row(...$cells);
        return $this;
    }

    public function fields(string ...$fields): self
    {
        $this->currentTab()->fields(...$fields);
        return $this;
    }

    public function isEmpty(): bool
    {
        return [] === $this->fieldNames();
    }

    /** @return list<string> placed field names in order */
    public function fieldNames(): array
    {
        $names = [];
        foreach ($this->tabs as $tab) {
            foreach ($tab->sections as $section) {
                foreach ($section->rows as $row) {
                    foreach ($row->cells as $cell) {
                        $names[] = $cell->field;
                    }
                }
            }
        }
        return $names;
    }

    /**
     * Returns a copy containing only the given fields (each at most once), with unplaced fields appended to the
     * last section of the first tab.
     *
     * @param array<string, Field> $fields
     */
    public function resolve(array $fields): self
    {
        $resolved = new self();
        $placed = [];

        foreach ($this->tabs as $tab) {
            $newTab = new Tab($tab->label);
            foreach ($tab->sections as $section) {
                $newSection = new Section($section->legend);
                foreach ($section->rows as $row) {
                    $keep = [];
                    foreach ($row->cells as $cell) {
                        if (isset($fields[$cell->field]) && !isset($placed[$cell->field])) {
                            $keep[] = $cell;
                            $placed[$cell->field] = true;
                        }
                    }
                    $newSection->addRow(new Row(...$keep));
                }
                $newSection = $newSection->withoutEmptyRows();
                if ([] !== $newSection->rows || null !== $newSection->legend) {
                    $newTab->addSection($newSection);
                }
            }
            $resolved->tabs[] = $newTab;
        }

        $missing = [];
        foreach ($fields as $name => $field) {
            if (!isset($placed[$name])) {
                $missing[] = $name;
            }
        }
        if ([] !== $missing) {
            if ([] === $resolved->tabs) {
                $resolved->tabs[] = new Tab();
            }
            $resolved->tabs[0]->fields(...$missing);
        }

        return $resolved;
    }

    // ---------------------------------------------------------------- serialisation (designer file)

    /** @return array{tabs: list<array{label: ?string, sections: list<array{legend: ?string, rows: list<list<array{field: string, width: int}>>}>}>} */
    public function toArray(): array
    {
        $tabs = [];
        foreach ($this->tabs as $tab) {
            $sections = [];
            foreach ($tab->sections as $section) {
                $rows = [];
                foreach ($section->rows as $row) {
                    $cells = [];
                    foreach ($row->cells as $cell) {
                        $cells[] = ['field' => $cell->field, 'width' => $cell->width];
                    }
                    $rows[] = $cells;
                }
                $sections[] = ['legend' => $section->legend, 'rows' => $rows];
            }
            $tabs[] = ['label' => $tab->label, 'sections' => $sections];
        }
        return ['tabs' => $tabs];
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $layout = new self();
        if (!isset($data['tabs']) || !is_array($data['tabs'])) {
            throw new InvalidArgumentException('Invalid layout data: "tabs" missing.');
        }
        foreach ($data['tabs'] as $tabData) {
            if (!is_array($tabData)) {
                throw new InvalidArgumentException('Invalid layout data: tab must be an object.');
            }
            $tab = new Tab(self::optionalString($tabData, 'label'));
            foreach ((array) ($tabData['sections'] ?? []) as $sectionData) {
                if (!is_array($sectionData)) {
                    throw new InvalidArgumentException('Invalid layout data: section must be an object.');
                }
                $section = new Section(self::optionalString($sectionData, 'legend'));
                foreach ((array) ($sectionData['rows'] ?? []) as $rowData) {
                    $cells = [];
                    foreach ((array) $rowData as $cellData) {
                        if (!is_array($cellData) || !is_string($cellData['field'] ?? null)) {
                            throw new InvalidArgumentException('Invalid layout data: cell needs a field name.');
                        }
                        $width = $cellData['width'] ?? 12;
                        $cells[] = new Cell($cellData['field'], is_int($width) ? $width : (int) $width);
                    }
                    $section->addRow(new Row(...$cells));
                }
                $tab->addSection($section);
            }
            $layout->tabs[] = $tab;
        }
        return $layout;
    }

    /** @param array<mixed> $data */
    private static function optionalString(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data) || null === $data[$key] || '' === $data[$key]) {
            return null;
        }
        return is_string($data[$key]) ? $data[$key] : throw new InvalidArgumentException('Invalid layout data: "' . $key . '" must be a string.');
    }

    private function currentTab(): Tab
    {
        if ([] === $this->tabs) {
            $this->tabs[] = new Tab();
        }
        return $this->tabs[count($this->tabs) - 1];
    }

    /** Whether the layout needs a tab navigation. */
    public function hasTabs(): bool
    {
        return count($this->tabs) > 1;
    }

    /** @internal */
    public function contains(string $field): bool
    {
        return in_array($field, $this->fieldNames(), true);
    }
}
