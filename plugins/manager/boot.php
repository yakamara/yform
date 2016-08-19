<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 */

rex_yform::addTemplatePath(rex_path::plugin('yform','manager','ytemplates'));
rex_complex_perm::register('yform_manager_table', 'rex_yform_manager_table_perm');

if (rex::isBackend() && rex::getUser()) {

    rex_view::addJsFile($this->getAssetsUrl('manager.js'));

    if (!rex::getUser()->isAdmin()) {
        $page = $this->getProperty('page');
        $page['hidden'] = true;
        $this->setProperty('page', $page);
    }

    $tables = rex_yform_manager_table::getAll();
    $pages = [];

    $prio = 1;
    foreach ($tables as $table) {

        if ($table->isActive() && rex::getUser()->getComplexPerm('yform_manager_table')->hasPerm($table->getTableName())) {

            $be_page = new rex_be_page_main('yform_tables', $table->getTableName(), rex_i18n::translate($table->getName()));
            $be_page->setHref('index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName());
            $be_page->setIcon('rex-icon rex-icon-module');
            $be_page->setPrio($prio);

            if ($table->isHidden()) {
                $be_page->setHidden();

            }

            if (rex_request('page','string') == 'yform/manager/data_edit' && rex_request('table_name', 'string') == $table->getTableName()) {
                $be_page->setIsActive();

                $main_page = $this->getAddon()->getProperty('page');
                $main_page['isActive'] = false;
                $this->getAddon()->setProperty('page', $main_page);

            }

            $pages[] = $be_page;

            $prio++;

        }
    }

    $this->setProperty('pages', $pages);

}

rex_extension::register('REX_YFORM_SAVED', function (rex_extension_point $ep) {
    if ($ep->getSubject() instanceof Exception) {
        return;
    }

    $table = rex_yform_manager_table::get($ep->getParam('table'));
    if (!$table) {
        return;
    }

    $dataset = $ep->getParam('form')->getParam('manager_dataset');
    if (!$dataset) {
        $dataset = rex_yform_manager_dataset::get($table->getTableName(), $ep->getParam('id'));
    }
    $dataset->invalidateData();

    if ($table->hasHistory()) {
        $action = 'insert' === $ep->getParam('action') ? rex_yform_manager_dataset::ACTION_CREATE : rex_yform_manager_dataset::ACTION_UPDATE;
        $dataset->makeSnapshot($action);
    }
});
