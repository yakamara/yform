<?php

use Redaxo\YForm\Cronjob\HistoryDelete;

/**
 * @var rex_addon $this
 * @psalm-scope-this rex_addon
 */

rex_yform::addTemplatePath(rex_path::addon('yform', 'ytemplates'));

if (rex::isBackend() && rex::getUser()) {
    /* @var $this rex_addon */
    rex_view::addCssFile($this->getAssetsUrl('yform-styles.css'));

    rex_view::addJsFile($this->getAssetsUrl('manager.js'));
    rex_view::addJsFile($this->getAssetsUrl('relations.js'));
    rex_view::addJsFile($this->getAssetsUrl('widget.js'));

    // Tools
    rex_view::addJsFile($this->getAssetsUrl('daterangepicker/moment.min.js'));
    rex_view::addJsFile($this->getAssetsUrl('daterangepicker/daterangepicker.js'));
    rex_view::addCssFile($this->getAssetsUrl('daterangepicker/daterangepicker.css'));
    rex_view::addJsFile($this->getAssetsUrl('inputmask/dist/jquery.inputmask.min.js'));
    rex_view::addJsFile($this->getAssetsUrl('tools.js'));

    rex_extension::register('PACKAGES_INCLUDED', function () {
        if ($this->getProperty('compile')) {
            $compiler = new rex_scss_compiler();
            $compiler->setRootDir($this->getPath('scss/'));
            $compiler->setScssFile($this->getPath('scss/styles.scss'));
            $compiler->setCssFile($this->getPath('assets/yform-styles.css'));
            $compiler->compile();
            rex_dir::copy($this->getPath('assets'), $this->getAssetsPath());
        }
    });

    rex_extension::register('PAGE_CHECKED', static function (rex_extension_point $ep) {
        $page = rex_be_controller::getPageObject('yform');

        if (!$page) {
            return;
        }

        $subpages = $page->getSubpages();
        if (isset($subpages['manager']) && rex::getUser()->isAdmin()) {
            $manager = $subpages['manager'];
            unset($subpages['manager']);
            $subpages = array_merge(['manager' => $manager], $subpages);
            $page->setSubpages($subpages);
        }
        if (!$subpages || 1 === count($subpages) && isset($subpages['manager'])) {
            $page->setHidden(true);
        }
    });
}

// E-Mail

rex_extension::register('EDITOR_URL', static function (rex_extension_point $ep) {
    if (preg_match('@^rex:///yform/email/template/(.*)/(.*)@', $ep->getParam('file'), $match)) {
        return rex_url::backendPage(
            'yform/email/index',
            [
                'func' => 'edit',
                'template_key' => $match[1],
            ],
        );
    }
});

// REST

rex_extension::register('PACKAGES_INCLUDED', static function () {
    if (!rex::isBackend()) {
        rex_yform_rest::handleRoutes();
    }
});

// Manager

rex_complex_perm::register('yform_manager_table_edit', 'rex_yform_manager_table_perm_edit');
rex_complex_perm::register('yform_manager_table_view', 'rex_yform_manager_table_perm_view');

if (rex::isBackend() && rex::getUser()) {
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
        if ($table->isActive() && $table->isGranted('VIEW', rex::getUser())) {
            $be_page = new rex_be_page_main('yform_tables', $table->getTableName(), rex_escape($table->getNameLocalized()));
            $be_page->setHref('index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName());
            $icon = rex_escape($table->getCustomIcon() ?: 'rex-icon-module');
            $be_page->setIcon('rex-icon ' . $icon);
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

rex_extension::register('MEDIA_IS_IN_USE', 'rex_yform_value_be_media::isMediaInUse');
rex_extension::register('PACKAGES_INCLUDED', 'rex_yform_value_be_link::isArticleInUse');

rex_extension::register('YFORM_SAVED', static function (rex_extension_point $ep) {
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

    if ($table->hasHistory() && $dataset->isHistoryEnabled()) {
        $action = 'insert' === $ep->getParam('action') ? rex_yform_manager_dataset::ACTION_CREATE : rex_yform_manager_dataset::ACTION_UPDATE;
        $dataset->makeSnapshot($action);
    }
});

if (rex_addon::get('cronjob')->isAvailable()) {
    rex_cronjob_manager::registerType(HistoryDelete::class);
}
