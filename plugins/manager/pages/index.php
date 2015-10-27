<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

$subpage = rex_be_controller::getCurrentPagePart(2);
$tripage = rex_be_controller::getCurrentPagePart(3);

switch ($tripage) {
    case 'table_field':
        echo rex_view::title(rex_i18n::msg('yform'));
        require rex_path::plugin('yform', 'manager','pages/table_field.php');
        break;

    case 'data_edit':
        require rex_path::plugin('yform', 'manager','pages/data_edit.php');
        break;

    default:
        // rex_title('yform', $REX['ADDON']['yform']['SUBPAGES']);
        echo rex_view::title(rex_i18n::msg('yform'));
        require rex_path::plugin('yform', 'manager','pages/table_edit.php');

}
