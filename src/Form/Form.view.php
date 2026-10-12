<?php

use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\HtmlAttributes;
use Yakamara\YForm\Form\Form;

use function Redaxo\Core\View\escape;

return static function (Form $form): void {
    $theme = $form->theme;
    $layout = $form->layout;
    $useTabs = $layout->hasTabs() && $theme->supportsTabs();
    // Own tabs (no framework plugin): the navigation stays hidden and all panes visible until yform-frontend.js takes over.
    $ownTabs = $theme->usesOwnTabs();
    $activeTab = $form->activeTab();

    $renderSection = static function ($section) use ($form, $theme): void {
        $rows = '';
        foreach ($section->rows as $row) {
            $cells = '';
            $visible = 0;
            foreach ($row->cells as $cell) {
                if (!$form->isVisible($cell->field)) {
                    continue;
                }
                ++$visible;
                $cells .= '<div class="' . escape($theme->columnClass($cell->width)) . '">' . $form->renderField($cell->field) . '</div>';
            }
            if ($visible > 0) {
                $rows .= '<div class="' . escape($theme->row) . '">' . $cells . '</div>';
            }
        }
        if ('' === $rows && null === $section->legend) {
            return;
        }
        if (null !== $section->legend) {
            echo '<fieldset', '' !== $theme->fieldset ? ' class="' . escape($theme->fieldset) . '"' : '', '>';
            echo '<legend', '' !== $theme->legend ? ' class="' . escape($theme->legend) . '"' : '', '>', escape($section->legend), '</legend>';
            echo $rows, '</fieldset>';
            return;
        }
        echo $rows;
    };

    $formAttributes = new HtmlAttributes([
        'action' => $form->actionUrl ?? '',
        'method' => $form->method,
        'class' => ['yform', $theme->form => '' !== $theme->form],
        'id' => $form->name,
        'enctype' => 'multipart/form-data',
        'novalidate' => false,
    ]);
    ?>
    <form <?= $formAttributes ?>>
        <input type="hidden" name="<?= escape($form->name) ?>[_yform]" value="1">
        <?php if ($form->csrf): ?><?= $form->csrfToken()->getHiddenField() ?><?php endif ?>
        <?php foreach ($form->hiddenFields as $name => $value): ?>
            <input type="hidden" name="<?= escape($name) ?>" value="<?= escape((string) $value) ?>">
        <?php endforeach ?>

        <?php if ($errors = $form->globalErrors()): ?>
            <div class="<?= escape($theme->alertError) ?>"><?php foreach ($errors as $error): ?><div><?= escape($error) ?></div><?php endforeach ?></div>
        <?php elseif (!$form->errors->isEmpty()): ?>
            <div class="<?= escape($theme->alertError) ?>"><?= I18n::msg('yform_form_has_errors') ?></div>
        <?php endif ?>

        <?php if ($useTabs): ?>
            <ul <?= new HtmlAttributes(['class' => $theme->tabs, 'role' => 'tablist', 'data-yform-tabs' => $ownTabs, 'hidden' => $ownTabs]) ?>>
                <?php foreach ($layout->tabs as $index => $tab):
                    $active = $index === $activeTab;
                    $activeOnItem = $theme->tabActiveOnItem && '' !== $theme->tabActive;
                    $item = new HtmlAttributes([
                        'class' => [$theme->tabItem => '' !== $theme->tabItem, $theme->tabActive => $active && $activeOnItem],
                        'role' => 'presentation',
                        'data-yform-tab-active' => $ownTabs && $activeOnItem ? $theme->tabActive : null,
                    ]);
                    $link = new HtmlAttributes([
                        'class' => [$theme->tabLink => '' !== $theme->tabLink, $theme->tabActive => $active && !$theme->tabActiveOnItem && '' !== $theme->tabActive],
                        'href' => '#' . $form->name . '-tab-' . $index,
                        ...($ownTabs ? ['data-yform-tab' => true, 'data-yform-tab-active' => $theme->tabActiveOnItem ? null : $theme->tabActive] : [$theme->tabToggle => 'tab']),
                        'role' => 'tab',
                        'aria-selected' => $active ? 'true' : 'false',
                    ]) ?>
                    <li <?= $item ?>><a <?= $link ?>><?= escape($tab->label ?? '') ?></a></li>
                <?php endforeach ?>
            </ul>
            <div <?= new HtmlAttributes(['class' => [$theme->tabContent => '' !== $theme->tabContent]]) ?>>
                <?php foreach ($layout->tabs as $index => $tab):
                    $pane = new HtmlAttributes([
                        'class' => [$theme->tabPane => '' !== $theme->tabPane, $theme->tabPaneActive => $index === $activeTab && '' !== $theme->tabPaneActive],
                        'id' => $form->name . '-tab-' . $index,
                        'role' => 'tabpanel',
                        'data-yform-tab-pane-active' => $ownTabs && '' !== $theme->tabPaneActive ? $theme->tabPaneActive : null,
                    ]) ?>
                    <div <?= $pane ?>>
                        <?php if ($ownTabs && null !== $tab->label): ?><h3 class="yform-tab-heading"><?= escape($tab->label) ?></h3><?php endif ?>
                        <?php foreach ($tab->sections as $section) {
                            $renderSection($section);
                        } ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php else: ?>
            <?php foreach ($layout->tabs as $tab): ?>
                <?php if (null !== $tab->label && $layout->hasTabs()): ?><h3><?= escape($tab->label) ?></h3><?php endif ?>
                <?php foreach ($tab->sections as $section) {
                    $renderSection($section);
                } ?>
            <?php endforeach ?>
        <?php endif ?>

        <?php if ($form->buttons): ?>
            <div class="<?= escape($theme->buttons) ?>">
                <?php if ('' !== $theme->buttonToolbar): ?><div class="<?= escape($theme->buttonToolbar) ?>"><?php endif ?>
                <?php foreach ($form->buttons as $action => $button): ?>
                    <button type="submit" name="<?= escape($form->name) ?>[_action]" value="<?= escape($action) ?>" class="<?= escape($button['primary'] ? $theme->submit : $theme->secondary) ?>"><?= escape($button['label']) ?></button>
                <?php endforeach ?>
                <?php if ('' !== $theme->buttonToolbar): ?></div><?php endif ?>
            </div>
        <?php endif ?>
    </form>
    <?php
};
