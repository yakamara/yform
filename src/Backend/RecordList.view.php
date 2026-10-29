<?php

use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Component\Dropdown;
use Redaxo\Core\View\Component\Section;
use Redaxo\Core\View\Component\Table as TableComponent;
use Redaxo\Core\View\Component\TableCell;
use Redaxo\Core\View\Component\TableColumn;
use Redaxo\Core\View\Component\TableColumnType;
use Redaxo\Core\View\Component\TableRow;
use Redaxo\Core\View\Html;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Backend\RecordList;
use Yakamara\YForm\Data\Paginated;
use Yakamara\YForm\Data\Record;

use function Redaxo\Core\View\escape;

return static function (RecordList $list, Paginated $result): void {
    $table = $list->table;
    $deleteToken = CsrfToken::factory('yform_delete_' . $table->name);

    // Same markup as the core pagination (`Pagination` component), which counts by offset instead of page.
    $pagination = static function () use ($list, $result): void {
        $current = $result->page;
        $last = $result->pages;
        $from = max(2, min($current - 3, $last - 7));
        $to = min($last - 1, $from + 6);
        $page = static fn (int $p): string => '<li><a class="rex-pagination-link" href="' . escape($list->url(['p' => $p])) . '"' . ($p === $current ? ' aria-current="page"' : '') . '>' . $p . '</a></li>';
        $step = static fn (bool $enabled, int $p, string $label, string $icon): string => '<li>'
            . ($enabled ? '<a class="rex-pagination-link" href="' . escape($list->url(['p' => $p])) . '">' : '<span class="rex-pagination-link" aria-disabled="true">')
            . '<i class="rex-icon ' . $icon . '" aria-hidden="true"></i><span class="rex-u-visually-hidden">' . I18n::msg($label) . '</span>'
            . ($enabled ? '</a>' : '</span>')
            . '</li>';
        $gap = '<li><span class="rex-pagination-link" aria-hidden="true">…</span></li>';
        ?>
        <nav class="rex-pagination" aria-label="<?= I18n::msg('navigation_pagination') ?>">
            <?php if ($last > 1): ?>
                <ul class="rex-pagination-list" role="list">
                    <?= $step($result->hasPrevious(), max(1, $current - 1), 'list_previous', 'rex-icon-previous') ?>
                    <?= $page(1) ?>
                    <?= $from > 2 ? $gap : '' ?>
                    <?php for ($p = $from; $p <= $to; ++$p): ?>
                        <?= $page($p) ?>
                    <?php endfor ?>
                    <?= $to < $last - 1 ? $gap : '' ?>
                    <?= $page($last) ?>
                    <?= $step($result->hasNext(), min($last, $current + 1), 'list_next', 'rex-icon-next') ?>
                </ul>
            <?php endif ?>
            <p class="rex-pagination-count"><?= I18n::msg('list_rows_found', $result->total) ?></p>
        </nav>
        <?php
    };
    $columns = [
        new TableColumn(
            $list->canEdit ? Html::raw('<a class="rex-u-link-expanded" href="' . escape($list->url(['func' => 'add'])) . '" title="' . I18n::msg('yform_add') . '"><i class="rex-icon rex-icon-add" aria-hidden="true"></i><span class="rex-u-visually-hidden">' . I18n::msg('yform_add') . '</span></a>') : '',
            static fn (Record $record): Html => Html::raw('<a class="rex-u-link-expanded" href="' . escape($list->url(['func' => 'edit', 'id' => (int) $record->id])) . '" title="' . I18n::msg($list->canEdit ? 'yform_edit' : 'yform_view') . '"><i class="rex-icon ' . ($list->canEdit ? 'rex-icon-editmode' : 'fa-regular fa-eye') . '" aria-hidden="true"></i><span class="rex-u-visually-hidden">' . I18n::msg($list->canEdit ? 'yform_edit' : 'yform_view') . '</span></a>'),
            TableColumnType::Icon,
        ),
    ];
    foreach ($list->columns as $name => $column) {
        $columns[] = new TableColumn(
            $column['label'],
            static fn (Record $record): Html => Html::raw($list->cell($name, $record)),
            sortUrl: $column['sortable'] ? $list->sortUrl($name) : null,
            sorted: $list->sort === $name ? ('ASC' === $list->direction ? 'asc' : 'desc') : null,
            attributes: new HtmlAttributes(['class' => ['yform-col-' . $name]]),
        );
    }
    $columns[] = new TableColumn(I18n::msg('yform_actions'), static function (Record $record) use ($list, $deleteToken): Html {
        $icon = static fn (string $class): string => '<i class="rex-icon ' . $class . '" aria-hidden="true"></i> ';
        $items = [[
            'title' => Html::raw($icon($list->canEdit ? 'rex-icon-editmode' : 'fa-regular fa-eye') . I18n::msg($list->canEdit ? 'yform_edit' : 'yform_view')),
            'href' => $list->url(['func' => 'edit', 'id' => (int) $record->id]),
        ]];
        if ($list->canEdit) {
            $items[] = ['title' => Html::raw($icon('rex-icon-duplicate') . I18n::msg('yform_clone')), 'href' => $list->url(['func' => 'clone', 'id' => (int) $record->id])];
            $items[] = ['title' => Html::raw(
                '<form method="post" action="' . escape($list->url(['func' => 'delete', 'id' => (int) $record->id])) . '">' . $deleteToken->getHiddenField()
                . '<button type="submit" data-confirm="' . I18n::msg('yform_delete_confirm') . '">' . $icon('rex-icon-delete') . I18n::msg('yform_delete') . '</button></form>',
            )];
        }

        return Html::raw((string) new Dropdown($items, prefix: I18n::rawMsg('yform_actions_toggle'), alignEnd: true, link: true, attributes: new HtmlAttributes(['class' => ['yform-dropdown']]))->render());
    }, TableColumnType::Action);

    $rows = $result->items->isEmpty()
        ? [new TableRow([new TableCell(I18n::rawMsg('yform_no_records'), count($columns), new HtmlAttributes(['class' => ['rex-u-text-muted']]))])]
        : $result->items;

    $content = Html::capture(static function () use ($columns, $rows, $result, $pagination): void {
        $pagination();
        echo new TableComponent($columns, $rows, attributes: new HtmlAttributes(['data-hover' => true]))->render();
        if ($result->pages > 1) {
            $pagination();
        }
    });

    echo new Section($table->label(), content: $content, attributes: new HtmlAttributes(['class' => ['yform-list']]))->render();
};
