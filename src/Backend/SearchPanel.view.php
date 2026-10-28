<?php

use Redaxo\Core\Translation\I18n;
use Yakamara\YForm\Backend\SearchPanel;
use Yakamara\YForm\Search\TextFilter;

use function Redaxo\Core\View\escape;

return static function (SearchPanel $panel): void {
    $list = $panel->list;
    $table = $list->table;
    ?>
    <section class="rex-section yform-filter" data-variant="edit">
        <header class="rex-section-header">
            <h2 class="rex-section-title"><?= I18n::msg('yform_search') ?></h2>
        </header>
        <form class="rex-section-body" method="get" action="">
            <?php foreach (['page' => 'yform/data', 'table' => $table->name, 'sort' => $list->sort, 'direction' => $list->direction] as $param => $value): ?>
                <input type="hidden" name="<?= escape($param) ?>" value="<?= escape($value) ?>">
            <?php endforeach ?>
            <?php foreach ($list->filters as $name => $filter):
                $id = 'yform-filter-' . $table->name . '-' . $name ?>
                <div class="rex-field" data-layout="vertical"<?= isset($list->filterInput[$name]) ? ' data-yform-filter-active' : '' ?>>
                    <label class="rex-field-label" for="<?= escape($id) ?>"><?= escape($filter->field->translatedLabel()) ?></label>
                    <?= $filter->render('filter[' . $name . ']', $id, $list->filterInput[$name] ?? null) ?>
                    <?php if ($filter instanceof TextFilter): ?>
                        <p class="rex-field-note rex-u-small"><?= I18n::msg('yform_filter_text_help') ?></p>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
            <div class="yform-filter-buttons">
                <button class="rex-button" data-variant="primary" type="submit"><?= I18n::msg('yform_filter_apply') ?></button>
                <?php if ($list->isSearching()): ?>
                    <a class="rex-button" href="<?= escape($list->url(['filter' => null, 'p' => null, 'func' => null, 'id' => null])) ?>"><?= I18n::msg('yform_filter_reset') ?></a>
                <?php endif ?>
            </div>
        </form>
    </section>
    <?php
};
