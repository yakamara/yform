<?php

namespace Yakamara\YForm\Backend;

use Yakamara\YForm\View\ViewRenderer;

/**
 * Search column left of the backend list and the record form, built from the filterable fields.
 *
 * @internal
 */
final readonly class SearchPanel
{
    public function __construct(
        public RecordList $list,
    ) {}

    /** Places the search column left of the given content; without filterable fields the content stays alone. */
    public function wrap(string $content): string
    {
        if ([] === $this->list->filters) {
            return $content;
        }
        return '<div class="rex-grid yform-list-layout">'
            . '<div data-span="sm:4 md:3 lg:2">' . ViewRenderer::render(self::class, $this) . '</div>'
            . '<div data-span="sm:8 md:9 lg:10">' . $content . '</div>'
            . '</div>';
    }
}
