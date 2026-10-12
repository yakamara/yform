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
        return '<div class="row yform-list-layout">'
            . '<div class="col-sm-4 col-md-3 col-lg-2">' . ViewRenderer::render(self::class, $this) . '</div>'
            . '<div class="col-sm-8 col-md-9 col-lg-10">' . $content . '</div>'
            . '</div>';
    }
}
