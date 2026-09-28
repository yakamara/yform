<?php

use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Backend\RecordList;
use Yakamara\YForm\Data\Paginated;

use function Redaxo\Core\View\escape;

return static function (RecordList $list, Paginated $result): void {
    $table = $list->table;
    $deleteToken = CsrfToken::factory('yform_delete_' . $table->name);
    ?>
    <section class="rex-page-section yform-list">
        <div class="panel panel-default">
            <header class="panel-heading">
                <div class="panel-title"><?= escape($table->label()) ?> <small><?= escape(I18n::msg('yform_records_count', $result->total)) ?></small></div>
            </header>
            <div class="panel-body yform-list-toolbar">
                <form class="form-inline" method="get" action="">
                    <input type="hidden" name="page" value="yform/data">
                    <input type="hidden" name="table" value="<?= escape($table->name) ?>">
                    <div class="input-group">
                        <input class="form-control" type="search" name="search" value="<?= escape($list->search) ?>" placeholder="<?= escape(I18n::msg('yform_search_placeholder')) ?>">
                        <span class="input-group-btn"><button class="btn btn-default" type="submit"><i class="rex-icon fa-search"></i></button></span>
                    </div>
                    <?php if ('' !== $list->search): ?>
                        <a class="btn btn-link" href="<?= escape($list->url(['search' => null, 'p' => null])) ?>"><?= escape(I18n::msg('yform_search_reset')) ?></a>
                    <?php endif ?>
                </form>
            </div>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th class="rex-table-icon">
                            <?php if ($list->canEdit): ?>
                                <a class="rex-link-expanded" href="<?= escape($list->url(['func' => 'add'])) ?>" title="<?= escape(I18n::msg('yform_add')) ?>"><i class="rex-icon rex-icon-add"></i></a>
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
                        <th class="rex-table-action" colspan="2"><?= escape(I18n::msg('yform_actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->items->isEmpty()): ?>
                        <tr><td colspan="<?= count($list->columns) + 3 ?>" class="text-muted"><?= escape(I18n::msg('yform_no_records')) ?></td></tr>
                    <?php endif ?>
                    <?php foreach ($result->items as $record):
                        $editUrl = $list->url(['func' => 'edit', 'id' => (int) $record->id]) ?>
                        <tr>
                            <td class="rex-table-icon"><a class="rex-link-expanded" href="<?= escape($editUrl) ?>"><i class="rex-icon <?= escape($table->icon()) ?>"></i></a></td>
                            <?php foreach ($list->columns as $name => $column): ?>
                                <td class="yform-col-<?= escape($name) ?>" data-title="<?= escape($column['label']) ?>"><?= $list->cell($name, $record) ?></td>
                            <?php endforeach ?>
                            <td class="rex-table-action">
                                <a href="<?= escape($editUrl) ?>"><i class="rex-icon <?= $list->canEdit ? 'rex-icon-edit' : 'fa-eye' ?>"></i> <?= escape(I18n::msg($list->canEdit ? 'yform_edit' : 'yform_view')) ?></a>
                            </td>
                            <td class="rex-table-action">
                                <?php if ($list->canEdit): ?>
                                    <form method="post" action="<?= escape($list->url(['func' => 'delete', 'id' => (int) $record->id])) ?>" class="yform-inline-form">
                                        <?= $deleteToken->getHiddenField() ?>
                                        <button type="submit" class="btn btn-link btn-xs yform-delete" data-confirm="<?= escape(I18n::msg('yform_delete_confirm')) ?>"><i class="rex-icon rex-icon-delete"></i> <?= escape(I18n::msg('yform_delete')) ?></button>
                                    </form>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php if ($result->pages > 1): ?>
                <footer class="panel-footer">
                    <nav><ul class="pagination">
                        <li class="<?= $result->hasPrevious() ? '' : 'disabled' ?>"><a href="<?= escape($list->url(['p' => max(1, $result->page - 1)])) ?>">&laquo;</a></li>
                        <?php for ($p = max(1, $result->page - 4); $p <= min($result->pages, $result->page + 4); ++$p): ?>
                            <li class="<?= $p === $result->page ? 'active' : '' ?>"><a href="<?= escape($list->url(['p' => $p])) ?>"><?= $p ?></a></li>
                        <?php endfor ?>
                        <li class="<?= $result->hasNext() ? '' : 'disabled' ?>"><a href="<?= escape($list->url(['p' => min($result->pages, $result->page + 1)])) ?>">&raquo;</a></li>
                    </ul></nav>
                </footer>
            <?php endif ?>
        </div>
    </section>
    <?php
};
