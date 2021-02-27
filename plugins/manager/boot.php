<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 */

rex_yform::addTemplatePath(rex_path::plugin('yform', 'manager', 'ytemplates'));
rex_complex_perm::register('yform_manager_table', 'rex_yform_manager_table_perm');

if (rex::isBackend() && rex::getUser()) {
    rex_view::addJsFile($this->getAssetsUrl('manager.js'));
    rex_view::addCssFile($this->getAssetsUrl('manager.css'));
    rex_view::addJsFile($this->getAssetsUrl('widget.js'));

    if (!rex::getUser()->isAdmin()) {
        $page = $this->getProperty('page');
        $page['hidden'] = true;
        $this->setProperty('page', $page);
    }

    try {
        $tables = rex_yform_manager_table::getAll();
    } catch (Exception $e) {
        $tables = [];
    }
    $pages = [];

    $prio = 1;
    foreach ($tables as $table) {
        if ($table->isActive() && rex::getUser()->getComplexPerm('yform_manager_table')->hasPerm($table->getTableName())) {
             $be_page = new rex_be_page_main('yform_tables', $table->getTableName(), $table->getNameLocalized() );
            $be_page->setHref('index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName());
            $be_page->setIcon('rex-icon rex-icon-module');
            $be_page->setPrio($prio);

            if ($table->isHidden()) {
                $be_page->setHidden();
            }

            if ('yform/manager/data_edit' == rex_request('page', 'string') && rex_request('table_name', 'string') == $table->getTableName()) {
                $be_page->setIsActive();

                $main_page = $this->getAddon()->getProperty('page');
                $main_page['isActive'] = false;

                $rex_yform_manager_opener = rex_request('rex_yform_manager_opener', 'array');
                $rex_yform_manager_popup = rex_request('rex_yform_manager_popup', 'int');
                $rex_yform_filter = rex_request('rex_yform_filter', 'array');

                if ((isset($rex_yform_manager_opener['id']) && '' != $rex_yform_manager_opener['id']) || 1 == $rex_yform_manager_popup) {
                    $main_page['popup'] = true;
                }

                $this->getAddon()->setProperty('page', $main_page);
            }

            $pages[] = $be_page;

            ++$prio;
        }
    }

    $this->setProperty('pages', $pages);
}

\rex_extension::register('MEDIA_IS_IN_USE', 'rex_yform_value_be_media::isMediaInUse');
\rex_extension::register('PACKAGES_INCLUDED', 'rex_yform_value_be_link::isArticleInUse');

rex_extension::register('REX_YFORM_SAVED', static function (rex_extension_point $ep) {
    if ($ep->getSubject() instanceof Exception) {
        return;
    }

    $table = rex_yform_manager_table::get($ep->getParam('table'));
    if (!$table) {
        return;
    }

    $dataset = $ep->getParam('form')->getParam('manager_dataset');
    if (!$dataset) {
        $dataset = rex_yform_manager_dataset::getRaw($ep->getParam('id'), $table->getTableName());
    }
    $dataset->invalidateData();

    if ($table->hasHistory()) {
        $action = 'insert' === $ep->getParam('action') ? rex_yform_manager_dataset::ACTION_CREATE : rex_yform_manager_dataset::ACTION_UPDATE;
        $dataset->makeSnapshot($action);
    }
});
