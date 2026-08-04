<?php

use Redaxo\Core\Backend\Controller;
use Redaxo\Core\Core;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\Message;
use Redaxo\Core\View\View;
use Yakamara\YForm\Manager\Manager;
use Yakamara\YForm\Manager\Table\Table;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// echo View::title(I18n::msg('yform'));

$table_name = Request::request('table_name', 'string');
$table = Table::get($table_name);

if ($table && $table->isGranted('VIEW', Core::getUser())) {
    try {
        $page = new Manager();
        $page->setTable($table);
        $page->setLinkVars(['page' => Controller::getCurrentPage(), 'table_name' => $table->getTableName()]);
        echo $page->getDataPage();
    } catch (Exception $e) {
        echo Message::warning(nl2br($e->getMessage() . "\n" . $e->getTraceAsString()));
    }
} else {
    if (!$table) {
        echo Message::warning(I18n::msg('yform_table_not_found'));
    } else {
        echo Message::warning(I18n::msg('yform_manager_table_nopermission'));
    }
}
