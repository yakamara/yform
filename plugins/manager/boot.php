<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */


if (rex::isBackend()) {
    rex_extension::register('PAGES_PREPARED', function ($params) {

        $pages = $params->getSubject();

        // echo '<pre>'; var_dump($pages);

        $tables = rex_yform_manager_table::getAll();


        // rex_package::get('yform');

        foreach ($tables as $table) {

            $table_perm = 'yform[table:' . $table['table_name'] . ']';
            $REX['EXTPERM'][] = $table_perm;

            if ($table['status'] == 1 && $table['hidden'] != 1 && rex::getUser() && (rex::getUser()->isAdmin() || rex::getUser()->hasPerm($table_perm))) {
                $table_name = rex_i18n::translate($table['name']);

                $table_link = 'index.php?page=yform/manager/data_edit&table_name=' . $table['table_name'];
                // echo "<br />".rex_i18n::msg($table['table_name'], $table_name)." ...   ".$table_link;

                // $be_page = new rex_be_page($table_name, rex_i18n::msg($table_name));
                //$be_page->setHref('index.php?page=yform/manager/data_edit&table_name=' . $table['table_name']);
//                $page->setPath(rex_path::core('pages/login.php'));
//                $page->setHasNavigation(false);

                // $pages["yform_tables"] = $be_page;


                // addPage(rex_be_page $page)

                // setBlock(
                // rex_be_page_main


                //             $subpages[] = new rex_be_main_page('manager', $be_page);

            }
        }

// rex_be_controller::setCurrentPage('login');
        return $pages;

    });

}




    // $subpages = rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_SUBPAGES_TABLES', $subpages));



//}




/*
if ($REX['REDAXO'] && !$REX['SETUP']) {

    rex_i18n::msgappendFile($REX['INCLUDE_PATH'] . '/addons/yform/plugins/manager/lang/');

    $REX['ADDON']['version'][$mypage] = '4.12';
    $REX['ADDON']['author'][$mypage] = 'Jan Kristinus';
    $REX['ADDON']['supportpage'][$mypage] = 'www.yakamara.de/tag/redaxo/';
    $REX['ADDON']['navigation'][$mypage] = array(
        // rootPage nur aktivieren wenn sie direkt ausgewaehlt ist
        // da alle pages main-pages und daher separate oberpunkte sind
        'activateCondition' => array('page' => $mypage, 'subpage' => 'manager'),
        'hidden' => false
    );

    if ($REX['USER'] && !rex::getUser()->isAdmin()) {
        $REX['ADDON']['navigation'][$mypage]['hidden'] = true;
    }

    $REX['ADDON']['yform']['SUBPAGES'][] = array('manager' , rex_i18n::msg('yform_table_manager'));

    rex_register_extension('OOMEDIA_IS_IN_USE', 'rex_yform_manager::checkMediaInUse');

    rex_register_extension('ADDONS_INCLUDED', function () {

        $tables = rex_yform_manager_table::getAll();

        $subpages = array();

        foreach ($tables as $table) {

            $table_perm = 'yform[table:' . $table['table_name'] . ']';
            $REX['EXTPERM'][] = $table_perm;

            if ($table['status'] == 1 && $table['hidden'] != 1 && $REX['USER'] && (rex::getUser()->isAdmin() || $REX['USER']->hasPerm($table_perm))) {
                $table_name = rex_i18n::translate($table['name']);

                  rex_i18n::msg($table['table_name'], $table_name);

                $be_page = new rex_be_page($table_name, array('page' => 'yform', 'subpage' => 'manager', 'tripage' => 'data_edit', 'table_name' => $table['table_name']));
                $be_page->setHref('index.php?page=yform&subpage=manager&tripage=data_edit&table_name=' . $table['table_name']);

                $subpages[] = new rex_be_main_page('manager', $be_page);

            }
        }

        $subpages = rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_SUBPAGES_TABLES', $subpages));

        OOPlugin::setProperty('yform', 'manager', 'pages', $subpages);

    });

    // hack - if data edit, then deactivate yform navigation
    if (rex_request('tripage', 'string') == 'data_edit') {
        $REX['ADDON']['navigation']['yform'] = array(
            'activateCondition' => array('page' => 'yformmm'),
            'hidden' => false
        );
    }


}
*/