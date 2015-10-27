<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

// ********************************************* TABLE ADD/EDIT/LIST

$func = rex_request('func', 'string', '');
$page = rex_request('page', 'string', '');
$table_id = rex_request('table_id', 'int');

$show_list = true;


if ( $func == 'tableset_export' && rex::getUser()->isAdmin() ) {

    $yform_tables = array();
    foreach (rex_yform_manager_table::getAll() as $g_table) {
        $yform_tables[$g_table->getTableName()] = rex_i18n::translate("translate:".$g_table->getTableName()).' ['.$g_table->getTableName().']';
    }

    $yform = new rex_yform;
    $yform->setDebug(true);
    $yform->setHiddenField('page', $page);
    $yform->setHiddenField('func', $func);
    $yform->setObjectparams('real_field_names',true);
    $yform->setValueField('select', array('table_names', rex_i18n::msg('yform_manager_tables'), $yform_tables, 'multiple'=>1));
    $yform->setValidateField('empty', array('table_names', rex_i18n::msg('yform_manager_export_error_empty')));
    $form = $yform->getForm();

    if ($yform->objparams['form_show']) {

      echo '<div class="rex-addon-output"><h3 class="rex-hl2">' . rex_i18n::msg('yform_manager_tableset_export') . '</h3>
      <div class="rex-addon-content">
      <p>' . rex_i18n::msg('yform_manager_tableset_export_info') . '</p>';
      echo $form;
      echo '</div></div>';

      echo rex_view::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

      $show_list = false;

    } else {

        try {

            $table_names = rex_request("table_names");
            $return = rex_yform_manager_table_api::exportTablesets($table_names);

            $file_name = 'yform_manager_tableset_export_tables_'.date("YmdHis").'.json';

            ob_end_clean();

            header('Content-Type: application/json');
            header('Charset: UTF-8');
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private",false);
            header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
            header('Content-Length: ' . strlen($return));
            header('Pragma: public');
            header('Expires: 0');
            header('Content-Transfer-Encoding: binary');
            echo $return;

            exit;

        } catch (Exception $e) {
            $show_list = false;
            echo rex_warning(rex_i18n::msg('yform_manager_table_export_failed', '', $e->getMessage()));

        }

    }

} else if ( $func == 'tableset_import' && rex::getUser()->isAdmin() ) {

  $yform = new rex_yform;
  $yform->setDebug(true);
  $yform->setHiddenField('page', $page);
  $yform->setHiddenField('func', $func);
  $yform->setObjectparams('real_field_names',true);
  $yform->setValueField('upload', array(
      'name'     => 'importfile',
      'label'    => rex_i18n::msg('yform_manager_table_import_jsonimportfile'),
      'max_size' => '1000', // max size in kb or range 100,500
      'types'    => '.json', // allowed extensions ".gif,.png"
      'required' => 1,
      'messages' => array(
          rex_i18n::msg('yform_manager_table_import_warning_min'),
          rex_i18n::msg('yform_manager_table_import_warning_max'),
          rex_i18n::msg('yform_manager_table_import_warning_type'),
          rex_i18n::msg('yform_manager_table_import_warning_selectfile')
        ),
      'modus'    => 'no_save',
      'no_db'    => 'no_db'
  ));

  $form = $yform->getForm();


  if ($yform->objparams['form_show']) {

    echo '<div class="rex-addon-output"><h3 class="rex-hl2">' . rex_i18n::msg('yform_manager_tableset_import') . '</h3>
    <div class="rex-addon-content">';
    echo $form;
    echo '</div></div>';

    echo rex_view::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

    $show_list = false;

  } else {

      try {
          $content = file_get_contents($yform->objparams['value_pool']['email']['importfile']);
          rex_yform_manager_table_api::importTablesets($content);
          echo rex_info(rex_i18n::msg('yform_manager_table_import_success'));

      } catch (Exception $e) {
          echo rex_warning(rex_i18n::msg('yform_manager_table_import_failed', '', $e->getMessage()));

      }

  }


} else if ( $func == 'migrate' && rex::getUser()->isAdmin() ) {

  $available_tables = rex_sql::showTables();
  $yform_tables = array();
  $missing_tables = array();

  foreach (rex_yform_manager_table::getAll() as $g_table) {
    $yform_tables[] = $g_table->getTableName();
  }

  foreach ($available_tables as $a_table) {
    if ( !in_array($a_table, $yform_tables)) {
      $missing_tables[$a_table] = $a_table;
    }

  }

  $yform = new rex_yform;
  $yform->setDebug(true);
  $yform->setHiddenField('page', $page);
  $yform->setHiddenField('func', $func);
  $yform->setValueField('select', array('table_name', rex_i18n::msg('yform_table'), $missing_tables));
  $yform->setValueField('checkbox', array('convert_id', rex_i18n::msg('yform_manager_migrate_table_id_convert')));
  $form = $yform->getForm();

  if ($yform->objparams['form_show']) {

    echo '<div class="rex-addon-output"><h3 class="rex-hl2">' . rex_i18n::msg('yform_manager_table_migrate') . '</h3>
    <div class="rex-addon-content">
    <p>' . rex_i18n::msg('yform_manager_table_migrate_info') . '</p>';
    echo $form;
    echo '</div></div>';

    echo rex_view::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

    $show_list = false;

  } else {

    $table_name = $yform->objparams['value_pool']['sql']['table_name'];
    $convert_id = $yform->objparams['value_pool']['sql']['convert_id'];

    try {
      rex_yform_manager_table_api::migrateTable($table_name, $convert_id); // with convert id / auto_increment finder
      echo rex_info(rex_i18n::msg('yform_manager_table_migrated_success'));

    } catch (Exception $e) {
      echo rex_warning(rex_i18n::msg('yform_manager_table_migrated_failed', $table_name, $e->getMessage()));

    }

  }

} else if ( ($func == 'add' || $func == 'edit') && rex::getUser()->isAdmin() ) {

    $yform = new rex_yform;
    // $yform->setDebug(TRUE);
    $yform->setHiddenField('page', $page);
    $yform->setHiddenField('func', $func);

    $yform->setHiddenField('list', rex_request('list', 'string'));
    $yform->setHiddenField('sort', rex_request('sort', 'string'));
    $yform->setHiddenField('sorttype', rex_request('sorttype', 'string'));
    $yform->setHiddenField('start', rex_request('start', 'string'));

    $yform->setActionField('showtext', array('', rex_i18n::msg('yform_manager_table_entry_saved')));
    $yform->setObjectparams('main_table', rex_yform_manager_table::table());

    $yform->setValueField('prio', array('prio', rex_i18n::msg('yform_manager_table_prio'), 'name'));

    if ($func == 'edit') {
        $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_save'));
        $yform->setValueField('showvalue', array('table_name', rex_i18n::msg('yform_manager_table_name')));
        $yform->setHiddenField('table_id', $table_id);
        $yform->setActionField('db', array(rex_yform_manager_table::table(), "id=$table_id"));
        $yform->setObjectparams('main_id', $table_id);
        $yform->setObjectparams('main_where', "id=$table_id");
        $yform->setObjectparams('getdata', true); // Datein vorher auslesen

    } elseif ($func == 'add') {
        $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_add'));
        $yform->setValueField('text', array('table_name', rex_i18n::msg('yform_manager_table_name'), rex::getTablePrefix()));
        $yform->setValidateField('empty', array('table_name', rex_i18n::msg('yform_manager_table_enter_name')));
        $yform->setValidateField('customfunction', array('table_name', function ($label = '', $table = '', $params = '') {
            preg_match("/([a-z])+([0-9a-z\\_])*/", $table, $matches);
            return !count($matches) || current($matches) != $table;
        }, '', rex_i18n::msg('yform_manager_table_enter_specialchars')));
        $yform->setValidateField('customfunction', array('table_name', function ($label = '', $table = '', $params = '') {
            return (boolean) rex_yform_manager_table::get($table);
        }, '', rex_i18n::msg('yform_manager_table_exists')));
        $yform->setActionField('wrapper_value', array('table_name', '###value###')); // Tablename
        $yform->setActionField('db', array(rex_yform_manager_table::table()));

    }

    $yform->setValueField('text', array('name', rex_i18n::msg('yform_manager_name')));
    $yform->setValidateField('empty', array('name', rex_i18n::msg('yform_manager_table_enter_name')));

    $yform->setValueField('textarea', array('description', rex_i18n::msg('yform_manager_table_description'), 'css_class' => "short1"));
    $yform->setValueField('checkbox', array('status', rex_i18n::msg('yform_tbl_active')));
    // $yform->setValueField("fieldset",array("fs-list","Liste"));
    $yform->setValueField('text', array('list_amount', rex_i18n::msg('yform_manager_entries_per_page'), '50'));
    $yform->setValidateField('type', array('list_amount', 'int', rex_i18n::msg('yform_manager_enter_number')));

    $sortFields = array('id');
    if ($func === 'edit') {
        $sortFieldsSql = rex_sql::factory();
        $sortFieldsSql->setQuery('SELECT f.name FROM `' . rex_yform_manager_field::table() . '` f LEFT JOIN `' . rex_yform_manager_table::table() . '` t ON f.table_name = t.table_name WHERE t.id = ' . (int) $table_id . ' ORDER BY f.prio');
        while ($sortFieldsSql->hasNext()) {
            $sortFields[] = $sortFieldsSql->getValue('name');
            $sortFieldsSql->next();
        }
    }
    $yform->setValueField('select' , array('list_sortfield', rex_i18n::msg('yform_manager_sortfield'), implode(',', $sortFields)));
    $yform->setValueField('select', array('list_sortorder', rex_i18n::msg('yform_manager_sortorder'), array(
        'ASC' => rex_i18n::msg('yform_manager_sortorder_asc'),
        'DESC' => rex_i18n::msg('yform_manager_sortorder_desc'),
    )));

    $yform->setValueField('checkbox', array('search', rex_i18n::msg('yform_manager_search_active')));

    $yform->setValueField('checkbox', array('hidden', rex_i18n::msg('yform_manager_table_hide')));
    $yform->setValueField('checkbox', array('export', rex_i18n::msg('yform_manager_table_allow_export')));
    $yform->setValueField('checkbox', array('import', rex_i18n::msg('yform_manager_table_allow_import')));

    $form = $yform->getForm();

    if ($yform->objparams['form_show']) {

        if ($func == 'edit') {
            echo '<div class="rex-addon-output"><h3 class="rex-hl2">' . rex_i18n::msg('yform_manager_edit_table') . '</h3><div class="rex-addon-content">';
        } else {
            echo '<div class="rex-addon-output"><h3 class="rex-hl2">' . rex_i18n::msg('yform_manager_add_table') . '</h3><div class="rex-addon-content">';
        }

        echo $form;
        echo '</div></div>';

        echo rex_view::info('<a href="index.php?page=' . $page . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

        $show_list = false;

    } else {

        if ($func == 'edit') {
            echo rex_view::info(rex_i18n::msg('yform_manager_table_updated'));

        } elseif ($func == 'add') {

            $table_name = $yform->objparams['value_pool']['sql']['table_name'];
            $table = rex_yform_manager_table::get($table_name);

            if ($table) {
                $t = new rex_yform_manager();
                $t->setTable($table);
                $t->generateAll();
                echo rex_view::info(rex_i18n::msg('yform_manager_table_added'));
            }


        }

    }

}


if ($func == 'delete' && rex::getUser()->isAdmin()) {

    $table_name = rex_request('table_name', 'string');
    echo rex_yform_manager_table_api::removeTable($table_name);

    $func = '';
    echo rex_view::info(rex_i18n::msg('yform_manager_table_deleted'));
}


if ($show_list && rex::getUser()->isAdmin()) {

    // formatting func fuer status col
    function rex_yform_status_col($params)
    {
        global $I18N;
        $list = $params['list'];
        return $list->getValue('status') == 1 ? '<span style="color:green;">' . rex_i18n::msg('yform_tbl_active') . '</span>' : '<span style="color:red;">' . rex_i18n::msg('yform_tbl_inactive') . '</span>';
    }

    function rex_yform_hidden_col($params)
    {
        global $I18N;
        $list = $params['list'];
        return $list->getValue('hidden') == 1 ? '<span style="color:grey;">' . rex_i18n::msg('yform_hidden') . '</span>' : '<span>' . rex_i18n::msg('yform_visible') . '</span>';
    }

    function rex_yform_list_translate($params)
    {
        return rex_i18n::translate($params['subject']);
    }

    $table_echo = '<b>';
    $table_echo .= rex_i18n::msg('yform_manager_table').': <a href=index.php?page=' . $page . '&func=add>' . rex_i18n::msg('yform_manager_create') . '</a>';
    $table_echo .= ' | <a href=index.php?page=' . $page . '&func=migrate><b>' . rex_i18n::msg('yform_manager_migrate') . '</a>';
    $table_echo .= ' '.rex_i18n::msg('yform_manager_tableset').':</b> <a href=index.php?page=' . $page . '&func=tableset_export>' . rex_i18n::msg('yform_manager_export') . '</a>';
    $table_echo .= ' | <a href=index.php?page=' . $page . '&func=tableset_import>' . rex_i18n::msg('yform_manager_import') . '</a>';
    $table_echo .= '</b>';

    echo rex_view::content('block',$table_echo);

    $sql = 'select id, prio, name, table_name, status, hidden from `' . rex_yform_manager_table::table() . '` order by prio,table_name';

    $list = rex_list::factory($sql);
    $list->addParam('start', rex_request('start', 'int'));

    $list->removeColumn('id');

    $list->setColumnLabel('prio', rex_i18n::msg('yform_manager_table_prio_short'));
    $list->setColumnLabel('name', rex_i18n::msg('yform_manager_name'));
    $list->setColumnFormat('name', 'custom', 'rex_yform_list_translate');

    $list->setColumnLabel('table_name', rex_i18n::msg('yform_manager_table_name'));
    $list->setColumnParams('table_name', array('table_id' => '###id###', 'func' => 'edit'));

    $list->setColumnLabel('status', rex_i18n::msg('yform_manager_table_status'));
    $list->setColumnFormat('status', 'custom', 'rex_yform_status_col');

    $list->setColumnLabel('hidden', rex_i18n::msg('yform_manager_table_hidden'));
    $list->setColumnFormat('hidden', 'custom', 'rex_yform_hidden_col');

    $list->addColumn(rex_i18n::msg('yform_edit'), rex_i18n::msg('yform_edit'));
    $list->setColumnParams(rex_i18n::msg('yform_edit'), array('table_id' => '###id###', 'func' => 'edit'));

    $list->addColumn(rex_i18n::msg('yform_delete'), rex_i18n::msg('yform_delete'));
    $list->setColumnParams(rex_i18n::msg('yform_delete'), array('table_name' => '###table_name###', 'func' => 'delete'));
    $list->addLinkAttribute(rex_i18n::msg('yform_delete'), 'onclick', 'return confirm(\' [###table_name###] ' . rex_i18n::msg('yform_delete') . ' ?\')');

    $list->addColumn(rex_i18n::msg('yform_editfields'), rex_i18n::msg('yform_editfields'));
    $list->setColumnParams(rex_i18n::msg('yform_editfields'), array('page' => 'yform/manager/table_field', 'table_name' => '###table_name###'));

    echo $list->get();
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
