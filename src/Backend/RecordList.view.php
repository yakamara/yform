<?php

use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Backend\RecordList;
use Yakamara\YForm\Data\Paginated;

use function Redaxo\Core\View\escape;

return static function (RecordList $list, Paginated $result): void {
    $table = $list->table;
    $deleteToken = CsrfToken::factory('yform_delete_' . $table->name);

    // Same markup as the core list pagination (`rex-nav-pagination`), shown above and below the table.
    $pagination = static function () use ($list, $result): void {
        $current = $result->page;
        $last = $result->pages;
        $from = max(2, min($current - 3, $last - 7));
        $to = min($last - 1, $from + 6);
        ?>
        <nav class="rex-nav-pagination">
            <?php if ($last > 1): ?>
                <ul class="pagination">
                    <li class="rex-prev<?= $result->hasPrevious() ? '' : ' disabled' ?>">
                        <a href="<?= escape($list->url(['p' => max(1, $current - 1)])) ?>" title="<?= I18n::msg('list_previous') ?>"><i class="rex-icon rex-icon-previous"></i><span class="sr-only"><?= I18n::msg('list_previous') ?></span></a>
                    </li>
                    <li class="rex-page<?= 1 === $current ? ' active' : '' ?>"><a href="<?= escape($list->url(['p' => 1])) ?>">1</a></li>
                    <?php if ($from > 2): ?><li class="disabled"><span>…</span></li><?php endif ?>
                    <?php for ($p = $from; $p <= $to; ++$p): ?>
                        <li class="rex-page<?= $p === $current ? ' active' : '' ?>"><a href="<?= escape($list->url(['p' => $p])) ?>"><?= $p ?></a></li>
                    <?php endfor ?>
                    <?php if ($to < $last - 1): ?><li class="disabled"><span>…</span></li><?php endif ?>
                    <li class="rex-page<?= $last === $current ? ' active' : '' ?>"><a href="<?= escape($list->url(['p' => $last])) ?>"><?= $last ?></a></li>
                    <li class="rex-next<?= $result->hasNext() ? '' : ' disabled' ?>">
                        <a href="<?= escape($list->url(['p' => min($last, $current + 1)])) ?>" title="<?= I18n::msg('list_next') ?>"><span class="sr-only"><?= I18n::msg('list_next') ?></span><i class="rex-icon rex-icon-next"></i></a>
                    </li>
                </ul>
            <?php endif ?>
            <div class="rex-pagination-count"><?= I18n::msg('list_rows_found', $result->total) ?></div>
        </nav>
        <?php
    };
    ?>
    <section class="rex-page-section yform-list">
        <div class="panel panel-default">
            <header class="panel-heading">
                <div class="panel-title"><?= escape($table->label()) ?></div>
            </header>
            <?php $pagination() ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="rex-table-icon">
                            <?php if ($list->canEdit): ?>
                                <a class="rex-link-expanded" href="<?= escape($list->url(['func' => 'add'])) ?>" title="<?= I18n::msg('yform_add') ?>"><i class="rex-icon rex-icon-add"></i></a>
                            <?php endif ?>
                        </th>
                        <?php foreach ($list->columns as $name => $column): ?>
                            <th class="yform-col-<?= escape($name) ?>">
                                <?php if ($column['sortable']): ?>
                                    <a href="<?= escape($list->sortUrl($name)) ?>"><?= escape($column['label']) ?><?php if ($list->sort === $name): ?> <i class="rex-icon fa-sort-<?= 'ASC' === $list->direction ? 'asc' : 'desc' ?>"></i><?php endif ?></a>
                                <?php else: ?>
                                    <?= escape($column['label']) ?>
                                <?php endif ?>
                            </th>
                        <?php endforeach ?>
                        <th class="rex-table-action"><?= I18n::msg('yform_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->items->isEmpty()): ?>
                        <tr><td colspan="<?= count($list->columns) + 2 ?>" class="text-muted"><?= I18n::msg('yform_no_records') ?></td></tr>
                    <?php endif ?>
                    <?php foreach ($result->items as $record):
                        $editUrl = $list->url(['func' => 'edit', 'id' => (int) $record->id]) ?>
                        <tr>
                            <td class="rex-table-icon">
                                <a class="rex-link-expanded" href="<?= escape($editUrl) ?>" title="<?= I18n::msg($list->canEdit ? 'yform_edit' : 'yform_view') ?>"><i class="rex-icon <?= $list->canEdit ? 'rex-icon-editmode' : 'fa-eye' ?>"></i></a>
                            </td>
                            <?php foreach ($list->columns as $name => $column): ?>
                                <td class="yform-col-<?= escape($name) ?>" data-title="<?= escape($column['label']) ?>"><?= $list->cell($name, $record) ?></td>
                            <?php endforeach ?>
                            <td class="rex-table-action">
                                <div class="dropdown yform-dropdown">
                                    <button class="btn btn-xs btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?= I18n::msg('yform_actions_toggle') ?> <span class="caret"></span></button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href="<?= escape($editUrl) ?>"><i class="rex-icon <?= $list->canEdit ? 'rex-icon-editmode' : 'fa-eye' ?>"></i> <?= I18n::msg($list->canEdit ? 'yform_edit' : 'yform_view') ?></a></li>
                                        <?php if ($list->canEdit): ?>
                                            <li>
                                                <form method="post" action="<?= escape($list->url(['func' => 'delete', 'id' => (int) $record->id])) ?>">
                                                    <?= $deleteToken->getHiddenField() ?>
                                                    <button type="submit" data-confirm="<?= I18n::msg('yform_delete_confirm') ?>"><i class="rex-icon rex-icon-delete"></i> <?= I18n::msg('yform_delete') ?></button>
                                                </form>
                                            </li>
                                        <?php endif ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php if ($result->pages > 1) {
                $pagination();
            } ?>
        </div>
    </section>
    <?php
};
