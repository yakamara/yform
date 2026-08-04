<?php

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

echo View::title(I18n::msg('yform'));

$table_name = Request::request('table_name', 'string');
$table = Table::get($table_name);

if ($table) {
    try {
        $page = new Manager();
        $page->setTable($table);
        $page->setLinkVars(['page' => 'yform/manager/table_field']);
        echo $page->getFieldPage();
    } catch (Exception $e) {
        $message = nl2br($e->getMessage() . "\n" . $e->getTraceAsString());
        echo Message::warning($message);
    }
} else {
    echo Message::warning(I18n::msg('yform_table_not_found'));
}
