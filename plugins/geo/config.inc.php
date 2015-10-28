<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$REX['ADDON']['yform']['classpaths']['value'][] = $REX['INCLUDE_PATH'] . '/addons/yform/plugins/geo/classes/value/';

$REX['ADDON']['yform']['templatepaths'][] = $REX['INCLUDE_PATH'] . '/addons/yform/plugins/geo/templates/';

if ($REX['REDAXO'] && !$REX['SETUP']) {

    // rex_i18n::msgappendFile($REX['INCLUDE_PATH'].'/addons/yform/plugins/geo/lang/');

    rex_register_extension('yform_MANAGER_TABLE_FIELD_FUNC', 'rex_yform_geo_page');
    function rex_yform_geo_page($params)
    {

        include $REX['INCLUDE_PATH'] . '/addons/yform/plugins/geo/pages/ep_geotagging.inc.php';
        return true;
    }

    rex_register_extension('yform_MANAGER_DATA_EDIT_FUNC', 'rex_yform_geo_data');
    function rex_yform_geo_data($params)
    {

        return true;
    }

    $REX['ADDON']['version']['geo'] = '4.12';
    $REX['ADDON']['author']['geo'] = 'Jan Kristinus';
    $REX['ADDON']['supportpage']['geo'] = 'www.yakamara.de/tag/redaxo/';

}
