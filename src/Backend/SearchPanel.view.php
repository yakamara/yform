<?php

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Backend\SearchPanel;
use Yakamara\YForm\Search\TextFilter;

use function Redaxo\Core\View\escape;

return static function (SearchPanel $panel): void {
    $list = $panel->list;
    $table = $list->table;
    ?>
    <form class="panel panel-edit yform-filter" method="get" action="">
        <header class="panel-heading">
            <div class="panel-title"><?= I18n::msg('yform_search') ?></div>
        </header>
        <div class="panel-body">
            <?php foreach (['page' => 'yform/data', 'table' => $table->name, 'sort' => $list->sort, 'direction' => $list->direction] as $param => $value): ?>
                <input type="hidden" name="<?= escape($param) ?>" value="<?= escape($value) ?>">
            <?php endforeach ?>
            <?php foreach ($list->filters as $name => $filter):
                $id = 'yform-filter-' . $table->name . '-' . $name ?>
                <div class="form-group<?= isset($list->filterInput[$name]) ? ' is-active' : '' ?>">
                    <label class="control-label" for="<?= escape($id) ?>"><?= escape($filter->field->translatedLabel()) ?></label>
                    <?= $filter->render('filter[' . $name . ']', $id, $list->filterInput[$name] ?? null) ?>
                    <?php if ($filter instanceof TextFilter): ?>
                        <p class="help-block small"><?= I18n::msg('yform_filter_text_help') ?></p>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
            <div class="yform-filter-buttons">
                <button class="btn btn-primary" type="submit"><?= I18n::msg('yform_filter_apply') ?></button>
                <?php if ($list->isSearching()): ?>
                    <a class="btn btn-default" href="<?= escape($list->url(['filter' => null, 'p' => null, 'func' => null, 'id' => null])) ?>"><?= I18n::msg('yform_filter_reset') ?></a>
                <?php endif ?>
            </div>
        </div>
    </form>
    <?php
};
