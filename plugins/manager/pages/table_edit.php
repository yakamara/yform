<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

echo rex_view::title(rex_i18n::msg('yform'));
$_csrf_key = 'yform_table_edit';


// ********************************************* TABLE ADD/EDIT/LIST

$func = rex_request('func', 'string', '');
$page = rex_request('page', 'string', '');
$table_id = rex_request('table_id', 'int');

$show_list = true;

if ($func == 'tableset_import' && rex::getUser()->isAdmin()) {
    $yform = new rex_yform();
    $yform->setDebug(true);
    $yform->setHiddenField('page', $page);
    $yform->setHiddenField('func', $func);
    $yform->setObjectparams('real_field_names', true);
    $yform->setValueField('upload', [
    'name' => 'importfile',
    'label' => rex_i18n::msg('yform_manager_table_import_jsonimportfile'),
    'max_size' => '1000', // max size in kb or range 100,500
    'types' => '.json', // allowed extensions ".gif,.png"
    'required' => 1,
    'messages' => [
        rex_i18n::msg('yform_manager_table_import_warning_min'),
        rex_i18n::msg('yform_manager_table_import_warning_max'),
        rex_i18n::msg('yform_manager_table_import_warning_type'),
        rex_i18n::msg('yform_manager_table_import_warning_selectfile'),
    ],
    'modus' => 'no_save',
    'no_db' => 'no_db',
    ]);

    $form = $yform->getForm();

    if ($yform->objparams['form_show']) {
        $fragment = new rex_fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('title', rex_i18n::msg('yform_manager_tableset_import'));
        $fragment->setVar('body', $form, false);
        // $fragment->setVar('buttons', $buttons, false);
        $form = $fragment->parse('core/page/section.php');

        echo $form;

        echo rex_view::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

        $show_list = false;
    } else {
        try {
            $content = file_get_contents($yform->objparams['value_pool']['email']['importfile']);
            rex_yform_manager_table_api::importTablesets($content);
            echo rex_view::info(rex_i18n::msg('yform_manager_table_import_success'));
        } catch (Exception $e) {
            echo rex_view::warning(rex_i18n::msg('yform_manager_table_import_failed', '', $e->getMessage()));
        }
    }
} elseif (($func == 'add' || $func == 'edit') && rex::getUser()->isAdmin()) {
    $yform = new rex_yform();
    // $yform->setDebug(TRUE);
    $yform->setObjectparams('form_name', $_csrf_key);
    $yform->setHiddenField('page', $page);
    $yform->setHiddenField('func', $func);

    $yform->setHiddenField('list', rex_request('list', 'string'));
    $yform->setHiddenField('sort', rex_request('sort', 'string'));
    $yform->setHiddenField('sorttype', rex_request('sorttype', 'string'));
    $yform->setHiddenField('start', rex_request('start', 'string'));

    $yform->setActionField('showtext', ['', rex_i18n::msg('yform_manager_table_entry_saved')]);
    $yform->setObjectparams('main_table', rex_yform_manager_table::table());

    $yform->setValueField('prio', ['prio', rex_i18n::msg('yform_manager_table_prio'), 'name']);

    if ($func == 'edit') {
        $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_save'));
        $yform->setValueField('showvalue', ['table_name', rex_i18n::msg('yform_manager_table_name')]);
        $yform->setHiddenField('table_id', $table_id);
        $yform->setActionField('db', [rex_yform_manager_table::table(), "id=$table_id"]);
        $yform->setObjectparams('main_id', $table_id);
        $yform->setObjectparams('main_where', "id=$table_id");
        $yform->setObjectparams('getdata', true);
    } elseif ($func == 'add') {
        $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_add'));
        $yform->setValueField('text', ['table_name', rex_i18n::msg('yform_manager_table_name'), rex::getTablePrefix()]);
        $yform->setValidateField('empty', ['table_name', rex_i18n::msg('yform_manager_table_enter_name')]);
        $yform->setValidateField('customfunction', ['table_name', function ($label = '', $table = '', $params = '') {
            preg_match('/([a-z])+([0-9a-z\\_])*/', $table, $matches);
            return !count($matches) || current($matches) != $table;
        }, '', rex_i18n::msg('yform_manager_table_enter_specialchars')]);
        $yform->setValidateField('customfunction', ['table_name', function ($label = '', $table = '', $params = '') {
            return (bool) rex_yform_manager_table::get($table);
        }, '', rex_i18n::msg('yform_manager_table_exists')]);
        $yform->setActionField('db', [rex_yform_manager_table::table()]);
    }

    $yform->setValueField('text', ['name', rex_i18n::msg('yform_manager_name')]);
    $yform->setValidateField('empty', ['name', rex_i18n::msg('yform_manager_table_enter_name')]);

    $yform->setValueField('textarea', ['description', rex_i18n::msg('yform_manager_table_description'), 'css_class' => 'short1']);
    $yform->setValueField('checkbox', ['status', rex_i18n::msg('yform_tbl_active')]);
    $yform->setValueField('text', ['list_amount', rex_i18n::msg('yform_manager_entries_per_page'), '50']);
    $yform->setValidateField('type', ['list_amount', 'int', rex_i18n::msg('yform_manager_enter_number')]);

    $sortFields = ['id'];
    if ($func === 'edit') {
        $sortFieldsSql = rex_sql::factory();
        $sortFieldsSql->setQuery('SELECT f.name FROM `' . rex_yform_manager_field::table() . '` f LEFT JOIN `' . rex_yform_manager_table::table() . '` t ON f.table_name = t.table_name WHERE t.id = ' . (int) $table_id . ' ORDER BY f.prio');
        while ($sortFieldsSql->hasNext()) {
            $sortFields[] = $sortFieldsSql->getValue('name');
            $sortFieldsSql->next();
        }
    }

    $yform->setValueField('choice', ['name' => 'list_sortfield', 'label' => rex_i18n::msg('yform_manager_sortfield'), 'choices' => implode(',', $sortFields)]);

    $yform->setValueField('choice', ['name' => 'list_sortorder', 'label' => rex_i18n::msg('yform_manager_sortorder'), 'choices' => [
        'ASC' => rex_i18n::msg('yform_manager_sortorder_asc'),
        'DESC' => rex_i18n::msg('yform_manager_sortorder_desc'),
    ]]);

    $yform->setValueField('checkbox', ['search', rex_i18n::msg('yform_manager_search_active')]);
    $yform->setValueField('checkbox', ['hidden', rex_i18n::msg('yform_manager_table_hide')]);
    $yform->setValueField('checkbox', ['export', rex_i18n::msg('yform_manager_table_allow_export')]);
    $yform->setValueField('checkbox', ['import', rex_i18n::msg('yform_manager_table_allow_import')]);
    $yform->setValueField('checkbox', ['mass_deletion', rex_i18n::msg('yform_manager_table_allow_mass_deletion')]);
    $yform->setValueField('checkbox', ['mass_edit', rex_i18n::msg('yform_manager_table_allow_mass_edit')]);
    $yform->setValueField('checkbox', ['history', rex_i18n::msg('yform_manager_table_history')]);

    $form = $yform->getForm();

    if ($yform->objparams['form_show']) {
        if ($func == 'edit') {
            $title = rex_i18n::msg('yform_manager_edit_table');
        } else {
            $title = rex_i18n::msg('yform_manager_add_table');
        }

        $fragment = new rex_fragment();
        $fragment->setVar('class', 'edit', false);
        $fragment->setVar('title', $title);
        $fragment->setVar('body', $form, false);
        // $fragment->setVar('buttons', $buttons, false);
        $form = $fragment->parse('core/page/section.php');

        echo $form;

        echo rex_view::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

        $show_list = false;
    } else {
        if ($func == 'edit') {
            $table_name = $yform->objparams['value_pool']['email']['table_name'];
            $table = rex_yform_manager_table::get($table_name);

            if ($table) {
                $t = new rex_yform_manager();
                $t->setTable($table);
                $t->generateAll();
            }

            echo rex_view::info(rex_i18n::msg('yform_manager_table_updated'));
        } elseif ($func == 'add') {
            rex_yform_manager_table::deleteCache();
            $table_name = $yform->objparams['value_pool']['sql']['table_name'];
            $table = rex_yform_manager_table::get($table_name);

            if ($table) {
                $t = new rex_yform_manager();
                $t->setTable($table);
                $t->generateAll();
                echo rex_view::success(rex_i18n::msg('yform_manager_table_added'));
            }
        }
    }
}

if ($func == 'delete' && rex::getUser()->isAdmin()) {

    if (!rex_csrf_token::factory($_csrf_key)->isValid()) {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        $table_name = rex_request('table_name', 'string');
        echo rex_yform_manager_table_api::removeTable($table_name);

        $func = '';
        echo rex_view::success(rex_i18n::msg('yform_manager_table_deleted'));
    }
}

if ($show_list && rex::getUser()->isAdmin()) {
    // formatting func fuer status col
    function rex_yform_status_col($params)
    {
        $list = $params['list'];
        return $list->getValue('status') == 1 ? '<span class="rex-online"><i class="rex-icon rex-icon-online"></i> ' . rex_i18n::msg('yform_tbl_active') . '</span>' : '<span class="rex-offline"><i class="rex-icon rex-icon-offline"></i> ' . rex_i18n::msg('yform_tbl_inactive') . '</span>';
    }

    function rex_yform_hidden_col($params)
    {
        $list = $params['list'];
        return $list->getValue('hidden') == 1 ? '<span class="text-muted">' . rex_i18n::msg('yform_hidden') . '</span>' : '<span>' . rex_i18n::msg('yform_visible') . '</span>';
    }

    function rex_yform_features_col($params)
    {
        $list = $params['list'];
        $info = [];
        if ($list->getValue('import') == 1) {
            $info[] = 'import';
        }
        if ($list->getValue('export') == 1) {
            $info[] = 'export';
        }
        if ($list->getValue('search') == 1) {
            $info[] = 'search';
        }
        if ($list->getValue('mass_deletion') == 1) {
            $info[] = 'mass_deletion';
        }
        if ($list->getValue('mass_edit') == 1) {
            $info[] = 'mass_edit';
        }
        if ($list->getValue('history') == 1) {
            $info[] = 'history';
        }

        return implode(', ', $info); // $list->getValue('hidden') == 1 ? '<span class="text-muted">' . rex_i18n::msg('yform_hidden') . '</span>' : '<span>' . rex_i18n::msg('yform_visible') . '</span>';
    }

    function rex_yform_list_translate($params)
    {
        return rex_i18n::translate($params['subject']);
    }

    $context = new rex_context([
        'page' => $page,
    ]);
    $items = [];

    $fragment = new rex_fragment();
    $fragment->setVar('buttons', $items, false);
    $fragment->setVar('size', 'xs', false);
    $panel_options = $fragment->parse('core/buttons/button_group.php');

    $sql = 'select id, prio, name, table_name, status, hidden, import, export, search, mass_deletion, mass_edit, history  from `' . rex_yform_manager_table::table() . '` order by prio,table_name';

    $list = rex_list::factory($sql);
    $list->addParam('start', rex_request('start', 'int'));

    $tdIcon = '<i class="rex-icon rex-icon-table"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '"' . rex::getAccesskey($this->i18n('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'table_id' => '###id###']);

    $list->removeColumn('id');
    $list->removeColumn('import');
    $list->removeColumn('export');
    $list->removeColumn('search');
    $list->removeColumn('mass_deletion');
    $list->removeColumn('mass_edit');
    $list->removeColumn('history');

    $list->setColumnLabel('prio', rex_i18n::msg('yform_manager_table_prio_short'));
    $list->setColumnLabel('name', rex_i18n::msg('yform_manager_name'));
    $list->setColumnFormat('name', 'custom', 'rex_yform_list_translate');

    $list->setColumnLabel('table_name', rex_i18n::msg('yform_manager_table_name'));
    $list->setColumnFormat('table_name', 'custom', function ($params) {
        return '<a href="index.php?page=yform/manager/data_edit&table_name=' . $params['value'] . '">' . $params['value'] . '</a>';
    });

    $list->setColumnLabel('status', rex_i18n::msg('yform_manager_table_status'));
    $list->setColumnFormat('status', 'custom', 'rex_yform_status_col');

    $list->setColumnLabel('hidden', rex_i18n::msg('yform_manager_table_hidden'));
    $list->setColumnFormat('hidden', 'custom', 'rex_yform_hidden_col');

    $list->addColumn('features', '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('yform_features'));
    $list->setColumnLabel('features', rex_i18n::msg('yform_manager_table_features'));
    $list->setColumnFormat('features', 'custom', 'rex_yform_features_col');

    $list->addColumn(rex_i18n::msg('yform_edit'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('yform_edit'));
    $list->setColumnLabel(rex_i18n::msg('yform_edit'), rex_i18n::msg('yform_function'));
    $list->setColumnLayout(rex_i18n::msg('yform_edit'), ['<th class="rex-table-action" colspan="3">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('yform_edit'), ['table_id' => '###id###', 'func' => 'edit']);

    $list->addColumn(rex_i18n::msg('yform_delete_definitions'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('yform_delete_definitions'));
    $list->setColumnLayout(rex_i18n::msg('yform_delete_definitions'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('yform_delete_definitions'), ['table_name' => '###table_name###', 'func' => 'delete'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
    $list->addLinkAttribute(rex_i18n::msg('yform_delete_definitions'), 'onclick', 'return confirm(\' [###table_name###] ' . rex_i18n::msg('yform_delete_definitions') . ' ?\')');

    $list->addColumn(rex_i18n::msg('yform_editfields'), rex_i18n::msg('yform_editfields'));
    $list->setColumnLayout(rex_i18n::msg('yform_editfields'), ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams(rex_i18n::msg('yform_editfields'), ['page' => 'yform/manager/table_field', 'table_name' => '###table_name###']);

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('yform_table_overview'));
    $fragment->setVar('options', $panel_options, false);
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');
    echo $content;
}

// ********************************************* LISTE OF TABLES TO EDIT FOR NOt ADMINS

if (!rex::getUser()->isAdmin()) {
    echo '<div class="rex-addon-output">';
    echo '<h2 class="rex-hl2">' . rex_i18n::msg('yform_table_overview') . '</h2>';
    echo '<div class="rex-addon-content"><ul>';

    $tables = rex_yform_manager_table::getAll();
    foreach ($tables as $table) {
        if ($table->isActive() && !$table->isHidden() && (rex::getUser()->isAdmin() || rex::getUser()->hasPerm($table->getPermKey()))) {
            echo '<li><a href="index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName() . '">' . rex_i18n::translate($table->getName()) . '</a></li>';
        }
    }

    echo '</ul></div>';
    echo '</div>';
}
