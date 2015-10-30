<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 *
 * @var rex_plugin $this
 */

if (rex::isBackend() && rex::getUser()) {

    $tables = rex_yform_manager_table::getAll();
    $pages = [];

    foreach ($tables as $table) {
        $table_perm = 'yform[table:' . $table['table_name'] . ']';

        if ($table['status'] == 1 && $table['hidden'] != 1) {
            $be_page = new rex_be_page_main('yform_tables', $table['table_name'], $table['name']);
            $be_page->setHref('index.php?page=yform/manager/data_edit&table_name=' . $table['table_name']);
            $be_page->setIcon('rex-icon rex-icon-module');
            $be_page->setRequiredPermissions([$table_perm]);
            $pages[] = $be_page;

            // TODO rechte noch verfügbar machen

            // TODO aktive Navigation noch einbauen

            /*
                if (rex_request('tripage', 'string') == 'data_edit') {
                    $REX['ADDON']['navigation']['yform'] = array(
                        'activateCondition' => array('page' => 'yformmm'),
                        'hidden' => false
                    );
                }

            */
        }
    }

    $this->setProperty('pages', $pages);

}

