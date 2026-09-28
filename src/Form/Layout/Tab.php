<?php

namespace Yakamara\YForm\Form\Layout;

use Closure;

use function count;

/** Tab of a form. A layout with a single tab is rendered without tab navigation. */
final class Tab
{
    /** @var list<Section> */
    public private(set) array $sections = [];

    public function __construct(
        public readonly ?string $label = null,
    ) {}

    /** Section with optional legend: `->section('Adresse', fn (Section $s) => $s->row('zip', 'city'))`. */
    public function section(?string $legend, ?Closure $callback = null): self
    {
        $section = new Section($legend);
        if (null !== $callback) {
            $callback($section);
        }
        $this->sections[] = $section;
        return $this;
    }

    /** Row in the last section (created on demand). */
    public function row(string|Cell ...$cells): self
    {
        $this->currentSection()->row(...$cells);
        return $this;
    }

    public function fields(string ...$fields): self
    {
        $this->currentSection()->fields(...$fields);
        return $this;
    }

    /** @internal */
    public function addSection(Section $section): void
    {
        $this->sections[] = $section;
    }

    private function currentSection(): Section
    {
        if ([] === $this->sections) {
            $this->sections[] = new Section();
        }
        return $this->sections[count($this->sections) - 1];
    }
}
