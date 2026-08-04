<?php

use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Http\Context;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\View\DataList;
use Redaxo\Core\View\Fragment;
use Redaxo\Core\View\Message;
use Redaxo\Core\View\View;
use Yakamara\YForm\Manager\Field;
use Yakamara\YForm\Manager\Table\Api;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\YForm;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo View::title(I18n::msg('yform'));
$_csrf_key = 'yform_table_edit';

// ********************************************* TABLE ADD/EDIT/LIST

$func = Request::request('func', 'string', '');
$page = Request::request('page', 'string', '');
$table_id = Request::request('table_id', 'int');

$show_list = true;

if ('tableset_import' == $func && Core::getUser()->admin) {
    $yform = new YForm();
    $yform->setDebug(true);
    $yform->setHiddenField('page', $page);
    $yform->setHiddenField('func', $func);
    $yform->setObjectparams('real_field_names', true);
    $yform->setValueField('upload', [
        'name' => 'importfile',
        'label' => I18n::msg('yform_manager_table_import_jsonimportfile'),
        'max_size' => '1000', // max size in kb or range 100,500
        'types' => '.json', // allowed extensions ".gif,.png"
        'required' => 1,
        'messages' => [
            I18n::msg('yform_manager_table_import_warning_min'),
            I18n::msg('yform_manager_table_import_warning_max'),
            I18n::msg('yform_manager_table_import_warning_type'),
            I18n::msg('yform_manager_table_import_warning_selectfile'),
        ],
        'modus' => 'no_save',
        'no_db' => true,
    ]);

    $form = $yform->getForm();

    if ($yform->objparams['form_show']) {
        $fragment = new Fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('title', I18n::msg('yform_manager_tableset_import'));
        $fragment->setVar('body', $form, false);
        // $fragment->setVar('buttons', $buttons, false);
        $form = $fragment->parse('core/page/section.php');

        echo $form;

        echo Message::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . I18n::msg('yform_back_to_overview') . '</b></a>');

        $show_list = false;
    } else {
        try {
            $content = file_get_contents($yform->objparams['value_pool']['email']['importfile']);
            Api::importTablesets($content);
            echo Message::info(I18n::msg('yform_manager_table_import_success'));
        } catch (Exception $e) {
            echo Message::warning(I18n::msg('yform_manager_table_import_failed', '', $e->getMessage()));
        }
    }
} elseif (('add' == $func || 'edit' == $func) && Core::getUser()->admin) {
    $table = null;
    if ('edit' == $func) {
        $table = Table::getById($table_id);
        if (!$table) {
            $func = 'add';
        }
    }

    $yform = new YForm();
    // $yform->setDebug(TRUE);
    $yform->setObjectparams('form_name', $_csrf_key);
    $yform->setHiddenField('page', $page);
    $yform->setHiddenField('func', $func);

    $yform->setHiddenField('list', Request::request('list', 'string'));
    $yform->setHiddenField('sort', Request::request('sort', 'string'));
    $yform->setHiddenField('sorttype', Request::request('sorttype', 'string'));
    $yform->setHiddenField('start', Request::request('start', 'string'));

    $yform->setActionField('showtext', ['', I18n::msg('yform_manager_table_entry_saved')]);
    $yform->setObjectparams('main_table', Table::table());

    $yform->setValueField('html', ['html' => '<div class="row"><div class="col-md-6">']);
    $yform->setValueField('html', ['html' => '<label>' . I18n::msg('yform_manager_table_basic_info') . '</label>']);
    $yform->setValueField('checkbox', ['status', I18n::msg('yform_tbl_active')]);
    $yform->setValueField('prio', ['prio', I18n::msg('yform_manager_table_prio'), 'name']);

    switch ($func) {
        case 'edit':
            $yform->setObjectparams('submit_btn_label', I18n::msg('yform_update_table'));
            $yform->setValueField('showvalue', ['table_name', I18n::msg('yform_manager_table_name')]);
            $yform->setHiddenField('table_id', $table->getId());
            $yform->setActionField('db', [Table::table(), 'id=' . $table->getId()]);
            $yform->setObjectparams('main_id', $table->getId());
            $yform->setObjectparams('main_where', 'id=' . $table->getId());
            $yform->setObjectparams('getdata', true);
            break;
        case 'add':
        default:
            $yform->setObjectparams('submit_btn_label', I18n::msg('yform_add_table'));
            $yform->setValueField('text', ['table_name', I18n::msg('yform_manager_table_name'), Core::getTablePrefix()]);
            $yform->setValidateField('empty', ['table_name', I18n::msg('yform_manager_table_enter_name')]);
            $yform->setValidateField('customfunction', ['table_name', static function ($label = '', $table = '', $params = '') {
                preg_match('/([a-z])+([0-9a-z\\_])*/', $table, $matches);
                return !count($matches) || current($matches) != $table;
            }, '', I18n::msg('yform_manager_table_enter_specialchars')]);
            $yform->setValidateField('customfunction', ['table_name', static function ($label = '', $table = '', $params = '') {
                return (bool) Table::get($table);
            }, '', I18n::msg('yform_manager_table_exists')]);
            $yform->setActionField('db', [Table::table()]);
            break;
    }

    $yform->setValueField('text', ['name', I18n::msg('yform_manager_name')]);
    $yform->setValidateField('empty', ['name', I18n::msg('yform_manager_table_enter_name')]);
    $yform->setValueField('textarea', ['description', '<br />' . I18n::msg('yform_manager_table_description'), 'attributes' => '{"class":"form-control yform-textarea-short"}']);
    $yform->setValueField('text', ['table_icon', I18n::msg('yform_manager_custom_icon'), 'attributes' => '{"class":"form-control yform-table-icon"}']);

    $yform->setValueField('html', ['html' => '</div><div class="col-md-6">']);

    $yform->setValueField('html', ['html' => '<label>' . I18n::msg('yform_manager_table_func') . '</label>']);

    $yform->setValueField('checkbox', ['search', I18n::msg('yform_manager_search_active')]);
    $yform->setValueField('checkbox', ['hidden', I18n::msg('yform_manager_table_hide')]);
    $yform->setValueField('checkbox', ['history', I18n::msg('yform_manager_table_history')]);
    $yform->setValueField('checkbox', ['schema_overwrite', I18n::msg('yform_manager_table_schema_overwrite'), 'default' => true]);

    $yform->setValueField('html', ['html' => '<label><br />' . I18n::msg('yform_manager_table_user_func') . '</label>']);

    $yform->setValueField('checkbox', ['export', I18n::msg('yform_manager_table_allow_export')]);
    $yform->setValueField('checkbox', ['import', I18n::msg('yform_manager_table_allow_import')]);
    $yform->setValueField('checkbox', ['mass_deletion', I18n::msg('yform_manager_table_allow_mass_deletion')]);
    $yform->setValueField('checkbox', ['mass_edit', I18n::msg('yform_manager_table_allow_mass_edit')]);

    $yform->setValueField('html', ['html' => '']);

    $yform->setValueField('text', ['name' => 'list_amount', 'label' => '<br />' . I18n::msg('yform_manager_entries_per_page'), 'default' => '50']);
    $yform->setValidateField('type', ['list_amount', 'int', I18n::msg('yform_manager_enter_number')]);

    $sortFields = ['id'];
    if ('edit' === $func) {
        $sortFieldsSql = Sql::factory();
        $sortFieldsSql->setQuery('SELECT f.name FROM `' . Field::table() . '` f LEFT JOIN `' . Table::table() . '` t ON f.table_name = t.table_name WHERE t.id = :id ORDER BY f.prio', [
            'id' => (int) $table_id,
        ]);
        while ($sortFieldsSql->hasNext()) {
            $sortFields[] = $sortFieldsSql->getValue('name');
            $sortFieldsSql->next();
        }
    }

    $yform->setValueField('html', ['html' => '<br /><div class="row"><div class="col-md-6">']);
    $yform->setValueField('choice', ['name' => 'list_sortfield', 'label' => I18n::msg('yform_manager_sortfield'), 'choices' => implode(',', $sortFields)]);
    $yform->setValueField('html', ['html' => '</div><div class="col-md-6">']);
    $yform->setValueField('choice', ['name' => 'list_sortorder', 'label' => I18n::msg('yform_manager_sortorder'), 'choices' => [
        'ASC' => I18n::msg('yform_manager_sortorder_asc'),
        'DESC' => I18n::msg('yform_manager_sortorder_desc'),
    ]]);

    $yform->setValueField('html', ['html' => '</div></div>']);

    $yform->setValueField('html', ['html' => '</div></div>']);

    $form = $yform->getForm();

    if ($yform->objparams['form_show']) {
        if ('edit' == $func) {
            $fragment = new Fragment();
            $fragment->setVar('size', 'xs', false);
            $fragment->setVar('buttons', [
                [
                    'label' => I18n::msg('yform_data_view'),
                    'url' => 'index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName(),
                    'attributes' => [
                        'class' => [
                            'btn-default',
                        ],
                    ],
                ],
            ], false);
            $panel_options = '<small class="rex-panel-option-title">' . I18n::msg('yform_datas') . '</small> ' . $fragment->parse('core/buttons/button_group.php');

            $fragment = new Fragment();
            $fragment->setVar('size', 'xs', false);
            $fragment->setVar('buttons', [
                [
                    'label' => I18n::msg('yform_edit'),
                    'url' => 'index.php?page=yform/manager/table_field&table_name=' . $table->getTableName(),
                    'attributes' => [
                        'class' => [
                            'btn-default',
                        ],
                    ],
                ],
            ], false);
            $panel_options .= '<small class="rex-panel-option-title">' . I18n::msg('yform_manager_fields') . '</small> ' . $fragment->parse('core/buttons/button_group.php');

            $title = I18n::msg('yform_manager_edit_table') . ' `' . $table->getTableName() . '`';
        } else {
            $title = I18n::msg('yform_manager_add_table');
        }

        $fragment = new Fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('options', (isset($panel_options)) ? $panel_options : '', false);
        $fragment->setVar('title', $title);
        $fragment->setVar('body', $form, false);
        // $fragment->setVar('buttons', $buttons, false);
        $form = $fragment->parse('core/page/section.php');

        echo $form;
        echo Message::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . I18n::msg('yform_back_to_overview') . '</b></a>');

        $show_list = false;
    } else {
        switch ($func) {
            case 'edit':
                $table_name = $yform->objparams['value_pool']['email']['table_name'];
                $table = Table::get($table_name);
                if ($table) {
                    Api::generateTableAndFields($table);
                }
                echo Message::info(I18n::msg('yform_manager_table_updated'));
                break;
            case 'add':
            default:
                Table::deleteCache();
                $table_name = $yform->objparams['value_pool']['sql']['table_name'];
                $table = Table::get($table_name);
                if ($table) {
                    Api::generateTableAndFields($table);
                    echo Message::success(I18n::msg('yform_manager_table_added'));
                }
                break;
        }
    }
}

if ('delete' == $func && Core::getUser()->admin) {
    if (!CsrfToken::factory($_csrf_key)->isValid()) {
        echo Message::error(I18n::msg('csrf_token_invalid'));
    } else {
        $table_name = Request::request('table_name', 'string');
        Api::removeTable($table_name);

        $func = '';
        echo Message::success(I18n::msg('yform_manager_table_deleted'));
    }
}

if ($show_list && Core::getUser()->admin) {
    // formatting func fuer status col
    function rex_yform_status_col($params)
    {
        $list = $params['list'];
        return 1 == $list->getValue('status') ? '<span class="rex-online"><i class="rex-icon rex-icon-online"></i> ' . I18n::msg('yform_tbl_active') . '</span>' : '<span class="rex-offline"><i class="rex-icon rex-icon-offline"></i> ' . I18n::msg('yform_tbl_inactive') . '</span>';
    }

    function rex_yform_hidden_col($params)
    {
        $list = $params['list'];
        return 1 == $list->getValue('hidden') ? '<span class="text-muted">' . I18n::msg('yform_hidden') . '</span>' : '<span>' . I18n::msg('yform_visible') . '</span>';
    }

    function rex_yform_features_col($params)
    {
        $list = $params['list'];
        $info = [];
        if (1 == $list->getValue('import')) {
            $info[] = 'import';
        }
        if (1 == $list->getValue('export')) {
            $info[] = 'export';
        }
        if (1 == $list->getValue('search')) {
            $info[] = 'search';
        }
        if (1 == $list->getValue('mass_deletion')) {
            $info[] = 'mass_deletion';
        }
        if (1 == $list->getValue('mass_edit')) {
            $info[] = 'mass_edit';
        }
        if (1 == $list->getValue('history')) {
            $info[] = 'history';
        }

        return implode(', ', $info); // $list->getValue('hidden') == 1 ? '<span class="text-muted">' . I18n::msg('yform_hidden') . '</span>' : '<span>' . I18n::msg('yform_visible') . '</span>';
    }

    function rex_yform_list_translate($params)
    {
        return I18n::translate($params['subject']);
    }

    $context = new Context([
        'page' => $page,
    ]);
    $items = [];

    $fragment = new Fragment();
    $fragment->setVar('buttons', $items, false);
    $fragment->setVar('size', 'xs', false);
    $panel_options = $fragment->parse('core/buttons/button_group.php');

    $sql = 'select id, prio, name, table_name, status, hidden, import, export, search, mass_deletion, mass_edit, history  from `' . Table::table() . '`';

    $list = DataList::factory($sql, 500, defaultSort: [
        'prio' => 'asc',
        'table_name' => 'asc',
    ]);

    $list->addTableAttribute('class', 'table-hover');
    $list->addParam('start', Request::request('start', 'int'));

    $list->setColumnSortable('prio');
    $list->setColumnSortable('name');
    $list->setColumnSortable('table_name');
    $list->setColumnSortable('status');
    $list->setColumnSortable('hidden');

    $tdIcon = '<i class="rex-icon rex-icon-table"></i>';
    $thIcon = '<a class="rex-link-expanded" href="' . $list->getUrl(['func' => 'add']) . '"' . Core::getAccesskey(I18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'table_id' => '###id###']);

    $list->removeColumn('id');
    $list->removeColumn('import');
    $list->removeColumn('export');
    $list->removeColumn('search');
    $list->removeColumn('mass_deletion');
    $list->removeColumn('mass_edit');
    $list->removeColumn('history');

    $list->setColumnLabel('prio', I18n::msg('yform_manager_table_prio_short'));

    $list->setColumnLabel('name', I18n::msg('yform_manager_name'));
    $list->setColumnFormat('name', 'custom', static function ($params) {
        $name = $params['value'];
        if ($name === $params['list']->getValue('table_name')) {
            $name = 'translate:' . $name;
        }
        $name = I18n::translate($name);
        if (preg_match('/^\[translate:(.*?)\]$/', $name, $match)) {
            $name = $match[1];
        }
        return $name . ' <p><a href="index.php?page=yform/manager/data_edit&table_name=###table_name###"><i class="rex-icon rex-icon-edit"></i> ' . I18n::msg('yform_edit_datatable') . '</a></p>';
    });

    $list->setColumnLabel('table_name', I18n::msg('yform_manager_table_name'));
    $list->setColumnFormat('table_name', 'custom', static function ($params) {
        $name = $params['value'];
        return $name . ' <p><a href="index.php?page=yform/manager/table_edit&func=edit&table_id=###id###&table_name=###table_name###"><i class="rex-icon rex-icon-edit"></i> ' . I18n::msg('yform_manager_edit_table') . '</a></p>';
    });

    $list->setColumnLabel('status', I18n::msg('yform_manager_table_status'));
    $list->setColumnFormat('status', 'custom', 'rex_yform_status_col');

    $list->setColumnLabel('hidden', I18n::msg('yform_manager_table_hidden'));
    $list->setColumnFormat('hidden', 'custom', 'rex_yform_hidden_col');

    $list->addColumn(I18n::msg('yform_delete_definitions'), '<i class="rex-icon rex-icon-delete"></i> ' . I18n::msg('yform_delete_definitions'));
    $list->setColumnLayout(I18n::msg('yform_delete_definitions'), ['<th></th>', '<td class="rex-table-action">###VALUE###<p class="help-block small">' . I18n::msg('yform_delete_definitions_info') . '</p></td>']);
    $list->setColumnParams(I18n::msg('yform_delete_definitions'), ['table_name' => '###table_name###', 'func' => 'delete'] + CsrfToken::factory($_csrf_key)->getUrlParams());
    $list->addLinkAttribute(I18n::msg('yform_delete_definitions'), 'onclick', 'return confirm(\' [###table_name###] ' . I18n::msg('yform_delete_definitions') . ' ?\')');

    $list->addColumn(I18n::msg('yform_editfields'), I18n::msg('yform_editfields'));
    $list->setColumnLayout(I18n::msg('yform_editfields'), ['<th></th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(I18n::msg('yform_editfields'), ['page' => 'yform/manager/table_field', 'table_name' => '###table_name###']);

    $content = $list->get();

    $fragment = new Fragment();
    $fragment->setVar('title', I18n::msg('yform_table_overview'));
    $fragment->setVar('options', $panel_options, false);
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');
    echo $content;
}
