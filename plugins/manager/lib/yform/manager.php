<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

if (!function_exists('rex_yform_manager_checkField')) {
    function rex_yform_manager_checkField($l, $v, $p)
    {
        return rex_yform_manager::checkField($l, $v, $p);
    }
}

class rex_yform_manager
{
    /** @var rex_yform_manager_table */
    var $table = '';
    var $linkvars = array();
    var $type = '';
    var $dataPageFunctions = array();
    var $DataPageFilterFields = array();
    static $debug = false;

    protected static $reservedFieldColumns = array('id', 'table_name', 'prio', 'type_id', 'type_name', 'list_hidden', 'search');

    function __construct()
    {
        $this->setDataPageFunctions();
    }


    // ----- Permissions
    function setDataPageFunctions($f = array('add', 'delete', 'search', 'export', 'import', 'truncate_table'))
    {
        $this->dataPageFunctions = $f;
    }

    function hasDataPageFunction($f)
    {
        return in_array($f, $this->dataPageFunctions) ? true : false;
    }


    // ----- Seitenausgabe
    function setLinkVars($linkvars)
    {
        $this->linkvars = array_merge($this->linkvars, $linkvars);
    }

    function getLinkVars()
    {
        return $this->linkvars;
    }


    // ---------------------------------- data functions
    public function getDataPage()
    {
        rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_DATA_PAGE', $this));

        // ********************************************* DATA ADD/EDIT/LIST

        $func = rex_request('func', 'string', '');
        $data_id = rex_request('data_id', 'int', '');
        $show_list = true;

        // -------------- rex_yform_manager_filter and sets

        $rex_yform_filter = rex_request('rex_yform_filter', 'array');
        $rex_yform_set = rex_request('rex_yform_set', 'array');

        // -------------- opener - popup for selection
        $popup = false;
        $rex_yform_manager_opener = rex_request('rex_yform_manager_opener', 'array');
        if (count($rex_yform_manager_opener) > 0) {
            if (isset($rex_yform_manager_opener['id']) && $rex_yform_manager_opener['id'] != '') {
                $popup = true; // id, field, multiple
            }
        }

        // -------------- filter - popup for selection
        if (count($rex_yform_filter) > 0) {
            $popup = true;

        }
        if (is_bool($p = rex_request('popup', 'bool', null))) {
            $popup = $p;
            $this->setLinkVars(array('popup' => $p ? 1 : 0));
        }

        // SearchObject
        $searchObject = new rex_yform_manager_search($this->table);

        $searchObject->setLinkVars(array("list" => rex_request('list', 'string', '')));
        $searchObject->setLinkVars(array("start" => rex_request('start', 'string', '')));
        $searchObject->setLinkVars(array("sort" => rex_request('sort', 'string', '')));
        $searchObject->setLinkVars(array("sorttype" => rex_request('sorttype', 'string', '')));
        $searchObject->setLinkVars($this->getLinkVars());

        if (count($rex_yform_filter) > 0) {
            foreach ($rex_yform_filter as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        $searchObject->setLinkVars(array('rex_yform_filter[' . $k . '][' . $k2 . ']' => $v2));
                    }
                } else {
                    $searchObject->setLinkVars(array('rex_yform_filter[' . $k . ']' => $v));
                }
            }
        }
        if (count($rex_yform_set) > 0) {
            foreach ($rex_yform_set as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        $searchObject->setLinkVars(array('rex_yform_set[' . $k . '][' . $k2 . ']' => $v2));
                    }
                } else {
                    $searchObject->setLinkVars(array('rex_yform_set[' . $k . ']' => $v));
                }
            }
        }
        if (count($rex_yform_manager_opener) > 0) {
            foreach ($rex_yform_manager_opener as $k => $v) {
                $searchObject->setLinkVars(array('rex_yform_manager_opener[' . $k . ']' => $v));
            }
        }

        $searchform = '';
        if ($this->hasDataPageFunction('search')) {
            $fragment = new rex_fragment();
            $fragment->setVar('class', 'edit', false);
            $fragment->setVar('title', rex_i18n::msg('yform_manager_search'));
            $fragment->setVar('body', $searchObject->getForm(), false);
            $searchform = $fragment->parse('core/page/section.php');

        }

        // -------------- DEFAULT - LISTE AUSGEBEN
        $link_vars = '';
        foreach ($this->getLinkVars() as $k => $v) {
            $link_vars .= '&' . urlencode($k) . '=' . urlencode($v);

        }

        echo rex_view::title(rex_i18n::msg('yform_table') . ': ' . rex_i18n::translate($this->table->getName()) . ' <small>[' . $this->table->getTablename() . ']</small>', '');


        echo rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_REX_INFO', ''));

        $show_editpage = true;
        $show_editpage = rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_DATA_EDIT_FUNC', $show_editpage,
        [
        'table' => $this->table,
        'link_vars' => $this->getLinkVars(),
        ]
        ));

        if ($show_editpage) {

            // -------------- DB FELDER HOLEN
            $field_names = array();
            foreach ($this->table->getValueFields() as $field) {
                $field_names[] = $field->getName();
            }

            // -------------- DB DATA HOLEN
            $data = array();
            if ($data_id != '') {
                $gd = rex_sql::factory();
                $gd->setQuery('select * from ' . $this->table->getTableName() . ' where id=' . $data_id);
                if ($gd->getRows() == 1) {
                    $datas = $gd->getArray();
                    $data = current($datas);
                } else {
                    $data_id = '';
                }
            }

            // -------------- Opener
            foreach ($rex_yform_manager_opener as $k => $v) {
                $link_vars .= '&rex_yform_manager_opener[' . $k . ']=' . urlencode($v);
            }

            // -------------- Searchfields / Searchtext
            $link_vars .= '&' . http_build_query($searchObject->getSearchVars());


            // -------------- FILTER UND SETS PRFEN
            $em_url_filter = '';
            if (count($rex_yform_filter) > 0) {
                foreach ($rex_yform_filter as $k => $v) {
                    if (!in_array($k, $field_names)) {
                        unset($rex_yform_filter[$k]);
                    }
                }
                $em_url_filter .= '&' . http_build_query(compact('rex_yform_filter'));
            };
            $em_url_set = '';
            if (count($rex_yform_set) > 0) {
                foreach ($rex_yform_set as $k => $v) {
                    if (!in_array($k, $field_names)) {
                        unset($rex_yform_set[$k]);
                    }
                }
                $em_url_filter .= '&' . http_build_query(compact('rex_yform_set'));
            };
            $em_url = $em_url_filter . $em_url_set;
            $em_rex_list = '';
            $em_rex_list .= '&list=' . urlencode(rex_request('list', 'string'));
            $em_rex_list .= '&sort=' . urlencode(rex_request('sort', 'string'));
            $em_rex_list .= '&sorttype=' . urlencode(rex_request('sorttype', 'string'));
            $em_rex_list .= '&start=' . urlencode(rex_request('start', 'string'));

            // ---------- Popup - no menue, header ...
            if ($popup) {
                echo '<link rel="stylesheet" type="text/css" href="'.rex_plugin::get('yform','manager')->getAssetsUrl('popup.css').'" />';

            }

            // -------------- Import
            if (!$popup && $func == 'import' && $this->hasDataPageFunction('import')) {
                include rex_path::plugin('yform', 'manager', 'pages/data_import.php');
                echo rex_view::info('<a href="index.php?' . $link_vars . $em_url . $em_rex_list . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');
            }

            // -------------- delete entry
            if ($func == 'delete' && $data_id != '' && $this->hasDataPageFunction('delete')) {
                if ($this->table->deleteDataset($data_id)) {
                    echo rex_view::success(rex_i18n::msg('yform_datadeleted'));
                    $func = '';
                }
            }

            // -------------- delete dataset
            if ($func == 'dataset_delete' && $this->hasDataPageFunction('truncate_table')) {
                $delsql = rex_sql::factory();
                $delsql->setDebug(self::$debug);
                $delsql->setQuery('select id from `' . $this->table->getTablename() . '` ' . $this->getDataListQueryWhere($rex_yform_filter, $searchObject));
                foreach ($delsql as $row) {
                    $this->table->deleteDataset($delsql->getValue('id'));
                }
                echo rex_view::success(rex_i18n::msg('yform_dataset_deleted'));
                $func = '';
            }

            // -------------- truncate table
            if ($func == 'truncate_table' && $this->hasDataPageFunction('truncate_table')) {
                $delsql = rex_sql::factory();
                $delsql->setDebug(self::$debug);
                $delsql->setQuery('select id from `' . $this->table->getTablename() . '`');
                foreach ($delsql as $row) {
                    $this->table->deleteDataset($delsql->getValue('id'));
                }
                echo rex_view::success(rex_i18n::msg('yform_table_truncated'));
                $func = '';
            }

            // -------------- export dataset
            if ($func == 'dataset_export' && $this->hasDataPageFunction('export')) {

                ob_end_clean();

                $sql = $this->getDataListQuery($rex_yform_filter, $searchObject);

                $data = '';
                $fields = array();
                $g = rex_sql::factory();
                $g->setQuery($sql);
                $array = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_TABLE_EXPORT', $g->getArray(), array('table' => $this->table)));

                foreach ($array as $d) {
                    if ($data == '') {
                        foreach ($d as $a => $b) {
                            $fields[] = '"' . $a . '"';
                        }
                        $data = implode(';', $fields);
                    }

                    foreach ($d as $a => $b) {
                        $d[$a] = '"' . str_replace('"', '""', $b) . '"';
                    }
                    $data .= "\n" . implode(';', $d);
                }

                // ----- download - save as

                $filename = 'export_data_' . date('YmdHis') . '.csv';
                $filesize = strlen($data);
                $filetype = 'application/octetstream';
                $expires = 'Mon, 01 Jan 2000 01:01:01 GMT';
                $last_modified = 'Mon, 01 Jan 2000 01:01:01 GMT';

                header('Expires: ' . $expires); // Date in the past
                header('Last-Modified: ' . $last_modified); // always modified
                header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                header('Pragma: private');
                header('Cache-control: private, must-revalidate');
                header('Content-Type: ' . $filetype . '; name="' . $filename . '"');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Description: "' . $filename . '"');
                header('Content-Length: ' . $filesize);

                echo $data;

                exit;

            }



            // -------------- form
            if (($func == 'add'  && $this->hasDataPageFunction('add')) || $func == 'edit') {
                $back = rex_view::info('<a href="index.php?' . $link_vars . $em_url . $em_rex_list . '"><b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b></a>');

                $yform = $this->getDataForm();
                foreach ($this->getLinkVars() as $k => $v) {
                    $yform->setHiddenField($k, $v);
                }
                if (count($rex_yform_manager_opener) > 0) {
                    foreach ($rex_yform_manager_opener as $k => $v) {
                        $yform->setHiddenField('rex_yform_manager_opener[' . $k . ']', $v);
                    }
                };

                if (count($rex_yform_filter) > 0) {
                    foreach ($rex_yform_filter as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                $yform->setHiddenField('rex_yform_filter[' . $k . '][' . $k2 . ']', $v2);
                            }
                        } else {
                            $yform->setHiddenField('rex_yform_filter[' . $k . ']', $v);
                        }
                    }
                };
                if (count($rex_yform_set) > 0) {
                    foreach ($rex_yform_set as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                $yform->setHiddenField('rex_yform_set[' . $k . '][' . $k2 . ']', $v2);
                            }
                        } else {
                            $yform->setHiddenField('rex_yform_set[' . $k . ']', $v);
                        }
                    }
                };

                foreach($searchObject->getSearchVars() as $s_var => $values) {
                    foreach($values as $k => $v) {
                        $yform->setHiddenField($s_var.'['.$k.']', $v);
                    }
                }

                // for rexlist
                $yform->setHiddenField('list', rex_request('list', 'string'));
                $yform->setHiddenField('sort', rex_request('sort', 'string'));
                $yform->setHiddenField('sorttype', rex_request('sorttype', 'string'));
                $yform->setHiddenField('start', rex_request('start', 'string'));

                if (rex_request('rex_yform_show_formularblock', 'string') != '') {
                    // Optional .. kann auch geloescht werden. Dient nur zu Hilfe beim Aufbau
                    // von yform-Formularen über php
                    // Textblock gibt den formalarblock als text aus, um diesen in das yform modul einsetzen zu können.
                    //  rex_yform_show_formularblock=1
                    $text_block = '';
                    foreach ($this->table->getFields() as $field) {
                        $class = 'rex_yform_'.$field->getType().'_'.$field->getTypeName();

                        $cl = new $class;
                        $definitions = $cl->getDefinitions();

                        $values = array();
                        $i = 1;
                        foreach ($definitions['values'] as $key => $_) {
                            $key = $this->getFieldName($key, $field->getType());
                            if (isset($field[$key])) {
                                $values[] = $field[$key];
                            } elseif (isset($field['f' . $i])) {
                                $values[] = $field['f' . $i];
                            } else {
                                $values[] = '';
                            }
                            $i++;
                        }

                        if ($field->getType() == 'value') {
                            $text_block .= "\n" . '$yform->setValueField("' . $field->getTypeName() . '",array("' . implode('","', $values) . '"));';

                        } else if ($field->getType() == 'validate') {
                            $text_block .= "\n" . '$yform->setValidateField("' . $field->getTypeName() . '",array("' . implode('","', $values) . '"));';

                        } else if ($field->getType() == 'action') {
                            $text_block .= "\n" . '$yform->setActionField("' . $field->getTypeName() . '",array("' . implode('","', $values) . '"));';

                        }
                        // $text_block .= "\n".$field["type_name"].'|'.implode("|",$values);
                    }
                    echo '<pre>' . $text_block . '</pre>';
                }

                $yform->setObjectparams('rex_yform_set', $rex_yform_set);

                $yform_clone = clone $yform;
                $yform->setHiddenField('func', $func); // damit es neu im clone gesetzt werden kann

                if ($func == 'edit') {
                    $yform->setHiddenField('data_id', $data_id);
                    $yform->setActionField('db', array($this->table->getTablename(), "id=$data_id"));
                    $yform->setObjectparams('main_id', $data_id);
                    $yform->setObjectparams('main_where', "id=$data_id");
                    $yform->setObjectparams('getdata', true);
                    $yform->setValueField('submits', array("name"=>"submit", "labels" => rex_i18n::msg('yform_save').",".rex_i18n::msg('yform_save_apply'), "values"=>"1,2", "no_db" => true, "css_classes" => "btn-save,btn-apply"));

                } elseif ($func == 'add') {
                    $yform->setActionField('db', array($this->table->getTablename()));
                    $yform->setValueField('submits', array("name"=>"submit", "labels" => rex_i18n::msg('yform_add').",".rex_i18n::msg('yform_add_apply'), "values"=>"1,2", "no_db" => true, "css_classes" => "btn-save,btn-apply"));

                }

                $form = $this->executeDataFormActions($yform, $data_id, $data);

                if ($yform->objparams['actions_executed']) {
                    if ($func == 'edit') {
                        echo rex_view::info(rex_i18n::msg('yform_thankyouforupdate'));
                    } elseif ($func == 'add') {
                        echo rex_view::info(rex_i18n::msg('yform_thankyouforentry'));

                        $submit_type = 1; // normal, 2=apply
                        foreach($yform->objparams["values"] as $f) {
                            if ($f->getName() == "submit") {
                                if ($f->getValue() == 2) { // apply
                                    $submit_type = 2;
                                }
                            }
                        }

                        if ($submit_type == 2) {
                            $data_id = $yform->objparams['main_id'];
                            $func = "edit";
                            $yform = $yform_clone;
                            $yform->setHiddenField('func', $func);
                            $yform->setHiddenField('data_id', $data_id);
                            $yform->setActionField('db', array($this->table->getTablename(), "id=$data_id"));
                            $yform->setObjectparams('main_id', $data_id);
                            $yform->setObjectparams('main_where', "id=$data_id");
                            $yform->setObjectparams('getdata', true);
                            $yform->setValueField('submits', array("name"=>"submit", "labels" => rex_i18n::msg('yform_save').",".rex_i18n::msg('yform_save_apply'), "values"=>"1,2", "no_db" => true, "css_classes" => "btn-save,btn-apply"));
                            $yform->setObjectparams('form_showformafterupdate', 1);
                            $yform->executeFields();

                            $form = $yform->executeActions();

                        }

                    }

                }

                if ($yform->objparams['form_show'] || ($yform->objparams['form_showformafterupdate'] )) {

                    if ($func == 'add') {
                        $title = rex_i18n::msg('yform_adddata');
                    } else {
                        $title = rex_i18n::msg('yform_editdata');
                    }

                    $fragment = new rex_fragment();
                    $fragment->setVar('class', 'edit', false);
                    $fragment->setVar('title', $title);
                    $fragment->setVar('body', $form, false);
                    // $fragment->setVar('buttons', $buttons, false);
                    $form = $fragment->parse('core/page/section.php');

                    echo $form;

                    echo rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_FORM', '', array('form' => $form, 'func' => $func, 'this' => $this, 'table' => $this->table)));

                    echo $back;

                    $show_list = false;

                }

            }


            // ********************************************* LIST
            if ($show_list) {

                $sql = $this->getDataListQuery($rex_yform_filter, $searchObject);

                // ---------- LISTE AUSGEBEN

                /** @type rex_list $list */
                $list = rex_list::factory($sql, $this->table->getListAmount());

                if ($this->hasDataPageFunction('add')) {
                    $tdIcon = '<i class="rex-icon rex-icon-table"></i>';
                    $thIcon = '<a href="index.php?' . $link_vars . '&func=add&' . $em_url . $em_rex_list . '"' . rex::getAccesskey(rex_i18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
                    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);

                    if (! isset($rex_yform_manager_opener['id'])) {
                        $list->setColumnParams($thIcon, ['data_id' => '###id###', 'func' => 'edit', 'start' => rex_request('start', 'string'), 'sort' => rex_request('sort', 'string'), 'sorttype' => rex_request('sorttype', 'string'), 'list' => rex_request('list', 'string')]);
                    }

                }
                // $list->setColumnFormat('id', 'Id');

                foreach ($this->getLinkVars() as $k => $v) {
                    $list->addParam($k, $v);
                }
                $list->addParam('table_name', $this->table->getTablename());

                if (count($rex_yform_filter) > 0) {
                    foreach ($rex_yform_filter as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                $list->addParam('rex_yform_filter[' . $k . '][' . $k2 . ']', $v2);
                            }
                        } else {
                            $list->addParam('rex_yform_filter[' . $k . ']', $v);
                        }
                    }
                }
                if (count($rex_yform_set) > 0) {
                    foreach ($rex_yform_set as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                $list->addParam('rex_yform_set[' . $k . '][' . $k2 . ']', $v2);
                            }
                        } else {
                            $list->addParam('rex_yform_set[' . $k . ']', $v);
                        }
                    }
                }
                if (count($rex_yform_manager_opener) > 0) {
                    foreach ($rex_yform_manager_opener as $k => $v) {
                        $list->addParam('rex_yform_manager_opener[' . $k . ']', $v);
                    }
                }

                foreach($searchObject->getSearchVars() as $s_var => $values) {
                    foreach($values as $k => $v) {
                        $list->addParam($s_var.'['.$k.']', $v);
                    }
                }

                $list->setColumnLabel('id', rex_i18n::msg('yform_id'));
                $list->setColumnLayout('id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id" data-title="' . rex_i18n::msg('id') . '">###VALUE###</td>']);
                $list->setColumnParams('id', array('data_id' => '###id###', 'func' => 'edit' ));
                $list->setColumnSortable('id');

                foreach ($this->table->getFields() as $field) {

                    if (!$field->isHiddenInList() && $field->getTypeName()) {
                        if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getListValue')) {
                            $list->setColumnFormat(
                            $field->getName(),
                            'custom',
                            array('rex_yform_value_' . $field->getTypeName(), 'getListValue'),
                            array('field' => $field->toArray(), 'fields' => $this->table->getFields()));
                        }
                    }

                    if ($field->getType() == 'value') {
                        if ($field->isHiddenInList()) {
                            $list->removeColumn($field->getName());
                        } else {
                            $list->setColumnSortable($field->getName());
                            $list->setColumnLabel($field->getName(), $field->getLabel());
                        }
                    }
                }

                if (isset($rex_yform_manager_opener['id'])) {

                    $list->addColumn(rex_i18n::msg('yform_data_select'),'');
                    $list->setColumnFormat(
                    rex_i18n::msg('yform_data_select'),
                    'custom',
                    function($params) {
                        $value = '';

                        list($table_name, $field_name) = explode(".",$params["params"]["opener_field"]);
                        $table = rex_yform_manager_table::get($table_name);
                        if ($table) {
                            $fields = $table->getValueFields(array("name" => $field_name));
                            if (isset($fields[$field_name])) {
                                $target_table = $fields[$field_name]->getElement('table');
                                $target_field = $fields[$field_name]->getElement('field');
                                $values = rex_yform_value_be_manager_relation::getListValues($target_table, $target_field);
                                $value = $values[$params['list']->getValue('id')];

                            }
                        }

                        return '<a href="javascript:yform_manager_setData(' . $params["params"]["opener_id"] . ',###id###,\''.htmlspecialchars($value).' [id=###id###]\',' . $params["params"]["opener_multiple"] . ')">'.rex_i18n::msg('yform_data_select').'</a>';
                    },
                    array(
                    "opener_id" => $rex_yform_manager_opener["id"],
                    "opener_field" => $rex_yform_manager_opener["field"],
                    "opener_multiple" => $rex_yform_manager_opener["multiple"],
                    )
                    );


                } else {
                    $list->addColumn(rex_i18n::msg('yform_function'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('yform_edit'));
                    $list->setColumnLayout(rex_i18n::msg('yform_function'), ['<th class="rex-table-action">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
                    $list->setColumnParams(rex_i18n::msg('yform_function'), array('data_id' => '###id###', 'func' => 'edit', 'start' => rex_request('start', 'string'), 'sort' => rex_request('sort', 'string'), 'sorttype' => rex_request('sorttype', 'string'), 'list' => rex_request('list', 'string')));

                    if ($this->hasDataPageFunction('delete')) {
                        // Column layout fuer edit neu setzen
                        $list->setColumnLayout(rex_i18n::msg('yform_function'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);

                        $list->addColumn(rex_i18n::msg('yform_delete'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('yform_delete'));
                        $list->setColumnLayout(rex_i18n::msg('yform_delete'), ['', '<td class="rex-table-action">###VALUE###</td>']);
                        $list->setColumnParams(rex_i18n::msg('yform_delete'), array('data_id' => '###id###', 'func' => 'delete', 'start' => rex_request('start', 'string'), 'sort' => rex_request('sort', 'string'), 'sorttype' => rex_request('sorttype', 'string'), 'list' => rex_request('list', 'string')));
                        $list->addLinkAttribute(rex_i18n::msg('yform_delete'), 'onclick', 'return confirm(\' id=###id### ' . rex_i18n::msg('yform_delete') . ' ?\')');
                    }
                }

                // *********************************************

                $list = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_LIST', $list, array('table' => $this->table)));

                if ($rex_yform_filter) {
                    $filter = array();
                    $getFilter = function (rex_yform_manager_field $field, $value) {
                        if ('be_manager_relation' == $field->getTypeName()) {
                            $listValues = rex_yform_value_be_manager_relation::getListValues($field->getElement('table'), $field->getElement('field'), array('id' => $value));
                            if (isset($listValues[$value])) {
                                $value = $listValues[$value];
                            }
                        }
                        return '<b>' . rex_i18n::translate($field->getLabel()) .':</b> ' . $value;
                    };
                    foreach ($rex_yform_filter as $key => $value) {
                        if (is_array($value)) {
                            $relTable = rex_yform_manager_table::get($this->table->getValueField($key)->getElement('table'));
                            foreach ($value as $k => $v) {
                                $filter[] = $getFilter($relTable->getValueField($k), $v);
                            }
                        } else {
                            $filter[] = $getFilter($this->table->getValueField($key), $value);
                        }
                    }
                    echo rex_view::info(implode('<br>', $filter));
                }

                $panel_options  = '';
                $data_links = [];
                if ($this->table->isSearchable()  && $this->hasDataPageFunction('search')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_search');
                    $item['attributes']['class'][] = 'btn-search';
                    $item['attributes']['id'] = 'searchtoggler';
                    $data_links[] = $item;
                }
                if (count($data_links) > 0) {
                    $fragment = new rex_fragment();
                    $fragment->setVar('size', 'xs', false);
                    $fragment->setVar('buttons', $data_links, false);
                    $panel_options .= '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_data') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
                }

                // INFO LINK
                $dataset_links = [];
                if (($this->table->isExportable() == 1 && $this->hasDataPageFunction('export'))) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_export');
                    $item['url'] = 'index.php?' . $link_vars . '&func=dataset_export&' . $em_url . $em_rex_list;
                    $item['attributes']['class'][] = 'btn-default';
                    $dataset_links[] = $item;
                }
                if ($this->table->isMassDeletionAllowed() && $this->hasDataPageFunction('truncate_table')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_delete');
                    $item['url'] = 'index.php?' . $link_vars . '&func=dataset_delete&' . $em_url . $em_rex_list;
                    $item['attributes']['class'][] = 'btn-delete';
                    $item['attributes']['id'] = 'dataset-delete';
                    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_dataset_delete_confirm') . '\');';
                    $dataset_links[] = $item;
                }
                if (count($dataset_links) > 0) {
                    $fragment = new rex_fragment();
                    $fragment->setVar('size', 'xs', false);
                    $fragment->setVar('buttons', $dataset_links, false);
                    $panel_options .= '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_dataset') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
                }

                $table_links = array();
                if (!$popup && $this->table->isImportable() && $this->hasDataPageFunction('import')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_import');
                    $item['url'] = 'index.php?' . htmlspecialchars($link_vars) . '&amp;func=import';
                    $item['attributes']['class'][] = 'btn-default';
                    $table_links[] = $item;
                }
                if (rex::getUser()->isAdmin()) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_edit');
                    $item['url'] = 'index.php?page=yform/manager&table_id=' . $this->table->getId() . '&func=edit';
                    $item['attributes']['class'][] = 'btn-default';
                    $table_links[] = $item;
                }
                if ($this->table->isMassDeletionAllowed() && $this->hasDataPageFunction('truncate_table')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_truncate_table');
                    $item['url'] = 'index.php?' . $link_vars . '&func=truncate_table&' . $em_url . $em_rex_list;
                    $item['attributes']['class'][] = 'btn-delete';
                    $item['attributes']['id'] = 'truncate-table';
                    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_truncate_table_confirm') . '\');';
                    $table_links[] = $item;
                }
                if (count($table_links) > 0) {
                    $fragment = new rex_fragment();
                    $fragment->setVar('size', 'xs', false);
                    $fragment->setVar('buttons', $table_links, false);
                    $panel_options .= '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_table') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
                }

                $field_links = array();
                if (rex::getUser()->isAdmin()) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_edit');
                    $item['url'] = 'index.php?page=yform/manager/table_field&table_name=' . $this->table->getTableName();
                    $item['attributes']['class'][] = 'btn-default';
                    $field_links[] = $item;
                }
                if (count($field_links) > 0) {
                    $fragment = new rex_fragment();
                    $fragment->setVar('size', 'xs', false);
                    $fragment->setVar('buttons', $field_links, false);
                    $panel_options .= '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_manager_fields') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
                }


                // SEARCHBLOCK
                $searchVars = $searchObject->getSearchVars();
                $display = count($searchVars["rex_yform_searchvars"]) >0 ? 'block' : 'none';
                echo '<div id="searchblock" style="display:' . $display . ';">' . $searchform . '</div>';

                $content = $list->get();

                $fragment = new rex_fragment();
                $fragment->setVar('title', rex_i18n::msg('yform_tabledata_overview'));
                $fragment->setVar('options', $panel_options, false);
                $fragment->setVar('content', $content, false);
                $content = $fragment->parse('core/page/section.php');

                $content .= '
                 <script type="text/javascript">/* <![CDATA[ */
                     jQuery("#searchtoggler").click(function(){jQuery("#searchblock").slideToggle("fast");});
                     jQuery("#yform_help_empty_toggler").click(function(){jQuery("#yform_help_empty").slideToggle("fast");});
                     jQuery("#yform_search_reset").click(function(){window.location.href = "index.php?page=yform/manager/data_edit&table_name=' . $this->table->getTableName() . '";});
                     jQuery("#truncate-table").click(function(){if(confirm("' . rex_i18n::msg('yform_truncate_table_confirm') . '")){return true;} else {return false;}});
                     jQuery("#dataset-delete").click(function(){if(confirm("' . rex_i18n::msg('yform_dataset_delete_confirm') . '")){return true;} else {return false;}});
                 /* ]]> */</script>';

                echo $content;

            }

        } // end: $show_editpage

    }

    /**
     * @return rex_yform
     */
    protected function getDataForm()
    {
        $yform = new rex_yform();
        $yform->setDebug(self::$debug);

        foreach ($this->table->getFields() as $field) {
            $class = 'rex_yform_'.$field->getType().'_'.$field->getTypeName();

            /** @var rex_yform_base_abstract $cl */
            $cl = new $class;
            $definitions = $cl->getDefinitions();

            $values = array();
            $i = 1;
            foreach ($definitions['values'] as $key => $_) {
                $key = $this->getFieldName($key, $field->getType());
                /*if ($field->getElement($key)) {
                    $values[] = $field->getElement($key);
                } elseif ($field->getElement('f' . $i)) {
                    $values[] = $field->getElement('f' . $i);
                } else {
                    $values[] = '';
                }*/
                $values[] = $field->getElement($key);
                $i++;
            }

            if ($field->getType() == 'value') {
                if (in_array($values[1], $this->getFilterFields())) {
                    // Feld vorhanden -> ignorieren -> hidden feld machen
                    // TODO: Feld trotzdem noch aufnehmen, damit validierungen etc noch funktionieren
                } else {
                    $yform->setValueField($field->getTypeName(), $values);
                }

            } elseif ($field->getType() == 'validate') {
                $yform->setValidateField($field->getTypeName(), $values);

            } elseif ($field->getType() == 'action') {
                $yform->setActionField($field->getTypeName(), $values);
            }
        }

        $yform->setObjectparams('main_table', $this->table->getTablename()); // für db speicherungen und unique abfragen

        return $yform;
    }

    protected function executeDataFormActions(rex_yform $yform, $data_id, $data)
    {
        if ($data_id) {
            $yform = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_UPDATE', $yform, array('table' => $this->table, 'data_id' => $data_id, 'data' => $data)));

        } else {
            $yform = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_ADD', $yform, array('table' => $this->table)));

        }

        $yform->executeFields();

        $submit_type = 1; // normal, 2=apply
        foreach($yform->objparams["values"] as $f) {
            if ($f->getName() == "submit") {
                if ($f->getValue() == 2) { // apply
                    $yform->setObjectparams('form_showformafterupdate', 1);
                    $yform->executeFields();
                    $submit_type = 2;
                }
            }
        }

        $form = $yform->executeActions();

        if ($yform->objparams['actions_executed']) {
            if ($data_id) {
                $yform = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_UPDATED', $yform, array('table' => $this->table, 'data_id' => $data_id, 'data' => $data)));
            } else {
                $yform = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_ADDED', $yform, array('table' => $this->table)));
            }
        }

        return $form;
    }


    public function getDataListQueryWhere($rex_yform_filter = array(), $searchObject)
    {
        $sql_o = rex_sql::factory();

        $sql = array();
        if (count($rex_yform_filter) > 0) {
            $sql_filter = '';
            foreach ($rex_yform_filter as $k => $v) {
                if ($sql_filter != '') {
                    $sql_filter .= ' AND ';
                }
                if (!is_array($v)) {
                    $sql_filter .= $sql_o->escapeIdentifier($k) . ' = ' . $sql_o->escape($v);
                } elseif ($relation = $this->table->getRelation($k)) {
                    foreach ($v as $k2 => $v2) {
                        $sql_filter .= '(SELECT ' . $sql_o->escapeIdentifier($k2) . ' FROM ' . $sql_o->escapeIdentifier($relation['table']) . ' WHERE id = t0.' . $sql_o->escapeIdentifier($k) . ') = ' . $sql_o->escape($v2);
                    }
                }
            }
            $sql[] = $sql_filter;
        }

        $searchFilter = $searchObject->getQueryFilterArray();
        if (count($searchFilter) > 0) {
            $sql[] = '( ' . implode(' AND ', $searchFilter) . ' )';
        }

        if (count($sql) > 0) {
            $sql = ' where ' . implode(' and ', $sql);
        } else {
            $sql = '';
        }

        return $sql;
    }


    public function getDataListQuery($rex_yform_filter = array(), $searchObject)
    {


        $sql = 'select * from ' . $this->table->getTablename() . ' t0';
        $sql_felder = rex_sql::factory();
        $sql_felder->setQuery('SELECT * FROM ' . rex_yform_manager_field::table() . ' WHERE table_name="' . $this->table->getTablename() . '" AND type_id="value" ORDER BY prio');

        $max = $sql_felder->getRows();
        if ($max > 0) {
            $existingFields = array_map(function ($column) {
                return $column['name'];
            }, rex_sql::showColumns($this->table->getTablename()));

            $fields = array();
            for ($i = 0; $i < $sql_felder->getRows(); $i++) {
                if (in_array($sql_felder->getValue('name'), $existingFields)) {
                    $fields[] = '`' . $sql_felder->getValue('name') . '`';
                } else {
                    $fields[] = 'NULL AS `' . $sql_felder->getValue('name') . '`';
                }
                $sql_felder->next();
            }
            $sql = 'select `id`,' . implode(',', $fields) . ' from `' . $this->table->getTablename() . '` t0';
        }

        $sql .= $this->getDataListQueryWhere($rex_yform_filter, $searchObject);
        if ($this->table->getSortFieldName() != "") {
            $sql .= ' ORDER BY `' . $this->table->getSortFieldName() . '` ' . $this->table->getSortOrderName();
        }
        $sql = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_LIST_SQL', $sql, array('table' => $this->table)));

        return $sql;
    }


    // ---------------------------------- table functions
    public function setTable(rex_yform_manager_table $table)
    {
        $this->table = $table;
    }


    // ---------------------------------- field functions
    function getFieldPage()
    {
        // ********************************************* FIELD ADD/EDIT/LIST

        $func = rex_request('func', 'string', 'list');
        $type_id = rex_request('type_id', 'string');
        $type_name = rex_request('type_name', 'string');
        $field_id = rex_request('field_id', 'int');

        $link_vars = '';
        foreach ($this->getLinkVars() as $k => $v) {
            $link_vars .= '&' . urlencode($k) . '=' . urlencode($v);
        }

        $TYPE = array('value' => rex_i18n::msg('yform_values'), 'validate' => rex_i18n::msg('yform_validates'), 'action' => rex_i18n::msg('yform_action'));


        // ********************************** TABELLE HOLEN
        $table = $this->table;

        $table_info = '<b>' . rex_i18n::translate($table->getName()) . ' [' . $table->getTableName() . ']</b> ';
        echo rex_view::info($table_info);

        // ********************************************* Missing Fields
        $mfields = $table->getMissingFields();
        // ksort($mfields);
        $type_real_field = rex_request('type_real_field', 'string');
        if ($type_real_field != '' && !array_key_exists($type_real_field, $mfields)) {
            $type_real_field = '';
        }

        if ($type_real_field != '') {
            $panel = '';
            $panel .= '<dl class="dl-horizontal text-left">';

            $rfields = $this->table->getColumns();
            foreach ($rfields[$type_real_field] as $k => $v) {
                $panel .= '<dt>' . ucfirst($k) . ':</dt><dd>' . $v . '</dd>';
            }
            $panel .= '</dl>';

            $fragment = new rex_fragment();
            $fragment->setVar('class', 'info');
            $fragment->setVar('title', 'Folgendes Feld wird verwendet: ' . $type_real_field);
            $fragment->setVar('body', $panel, false);
            echo $fragment->parse('core/page/section.php');

        }



        // ********************************************* CHOOSE FIELD
        $types = rex_yform::getTypeArray();
        if ($func == 'choosenadd') {

            $link = 'index.php?' . $link_vars . '&table_name=' . $table->getTableName() . '&func=add&';

            $content = [];
            $panels = [];

            if (!$table->hasId()) {

                $content[] = rex_i18n::msg('yform_id_is_missing').''.rex_i18n::msg('yform_id_missing_info');

            } else {

                if ($type_real_field == '' && count($mfields) > 0 ) {
                    $tmp = '';
                    $d = 0;
                    foreach ($mfields as $k => $v) {
                        $d++;
                        $l = 'index.php?' . $link_vars . '&table_name=' . $table->getTableName() . '&func=choosenadd&type_real_field=' . $k . '&type_layout=t';
                        $tmp .= '<a class="btn btn-default" href="' . $l . '">' . $k . '</a> ';
                    }

                    $fragment = new rex_fragment();
                    $fragment->setVar('class', 'info');
                    $fragment->setVar('title', 'Es gibt noch Felder in der Tabelle welche nicht zugewiesen sind.');
                    $fragment->setVar('body', $tmp, false);
                    echo $fragment->parse('core/page/section.php');
                }

                $tmp = '';
                if (isset($types['value'])) {
                    ksort($types['value']);
                    $tmp .= '<table class="table table-hover">';
                    foreach ($types['value'] as $k => $v) {
                        $tmp .= '<tr><th><a class="btn btn-default btn-block" href="' . $link . 'type_id=value&type_name=' . $k . '&type_real_field=' . $type_real_field . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['description'] . '</td></tr>';
                    }
                    $tmp .= '</table>';
                }
                $fragment = new rex_fragment();
                $fragment->setVar('title', $TYPE['value']);
                $fragment->setVar('content', $tmp, false);
                $panels[] =  $fragment->parse('core/page/section.php');


                $tmp = '';
                if (isset($types['validate'])) {
                    ksort($types['validate']);
                    $tmp .= '<table class="table table-hover">';
                    foreach ($types['validate'] as $k => $v) {
                        $tmp .= '<tr><th><a class="btn btn-default btn-block" href="' . $link . 'type_id=validate&type_name=' . $k . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['description'] . '</td></tr>';
                    }
                    $tmp .= '</table>';
                }

                $fragment = new rex_fragment();
                $fragment->setVar('title', $TYPE['validate']);
                $fragment->setVar('content', $tmp, false);
                $panels[] =  $fragment->parse('core/page/section.php');

            }

            $fragment = new rex_fragment();
            $fragment->setVar('title', rex_i18n::msg('yform_choosenadd'));
            $fragment->setVar('body', rex_i18n::msg('yform_choosenadd_description'), false);
            echo $fragment->parse('core/page/section.php');

            $fragment = new rex_fragment();
            $fragment->setVar('content', $panels, false);
            echo $fragment->parse('core/page/grid.php');

            $table_echo = '<a class="btn btn-default" href="index.php?' . $link_vars . '&amp;table_name=' . $table->getTableName() . '">' . rex_i18n::msg('yform_back_to_overview') . '</a>';

            $fragment = new rex_fragment();
            $fragment->setVar('footer', $table_echo, false);
            echo $fragment->parse('core/page/section.php');

        }





        // ********************************************* FORMULAR


        if ( ($func == 'add' || $func == 'edit' )  && isset($types[$type_id][$type_name]) ) {

            $yform = new rex_yform;
            $yform->setDebug(false);

            foreach ($this->getLinkVars() as $k => $v) {
                $yform->setHiddenField($k, $v);
            }

            $yform->setHiddenField('func', $func);
            $yform->setHiddenField('table_name', $table->getTableName());
            $yform->setHiddenField('type_real_field', $type_real_field);

            $yform->setHiddenField('list', rex_request('list', 'string'));
            $yform->setHiddenField('sort', rex_request('sort', 'string'));
            $yform->setHiddenField('sorttype', rex_request('sorttype', 'string'));
            $yform->setHiddenField('start', rex_request('start', 'string'));

            $yform->setValueField('hidden', array('table_name', $table->getTableName()));
            $yform->setValueField('hidden', array('type_name', $type_name, 'REQUEST'));
            $yform->setValueField('hidden', array('type_id', $type_id, 'REQUEST'));

            $yform->setValueField('prio', array('prio', 'Prioritaet', array('name', 'type_id', 'type_name'), array('table_name')));

            $selectFields = array();
            $i = 1;
            foreach ($types[$type_id][$type_name]['values'] as $k => $v) {
                $field = $this->getFieldName($k, $type_id);
                $selectFields['f' . $i] = $field;
                $i++;

                switch ($v['type']) {

                    case 'name':

                        if ($func == 'edit' ) {
                            $yform->setValueField('showvalue', array($field, 'Name'));
                        } else {
                            if (!isset($v['value']) && $type_real_field != '') {
                                $v['value'] = $type_real_field;
                            } elseif (!isset($v['value'])) {
                                $v['value'] = '';
                            }

                            $yform->setValueField('text', array($field, 'Name', $v['value']));
                            $yform->setValidateField('empty', array($field, rex_i18n::msg('yform_validatenamenotempty')));
                            $yform->setValidateField('preg_match', array($field, "/(([a-zA-Z])+([a-zA-Z0-9\_])*)/", rex_i18n::msg('yform_validatenamepregmatch')));
                            $yform->setValidateField('customfunction', array($field, 'rex_yform_manager_checkField', array('table_name' => $table->getTableName()), rex_i18n::msg('yform_validatenamecheck')));
                        }
                        break;

                    case 'no_db':
                        if (!isset($v['default']) || $v['default'] != 1) {
                            $v['default'] = 0;
                        }

                        $yform->setValueField('checkbox', array($field, rex_i18n::msg('yform_donotsaveindb'), 'no_db', $v['default']));
                        break;

                    case 'boolean':
                        // checkbox|check_design|Bezeichnung|Value|1/0|[no_db]
                        if (!isset($v['default'])) {
                            $v['default'] = '';
                        }
                        $yform->setValueField('checkbox', array($field, $v['label'], '', $v['default']));
                        break;

                    case 'select':
                        // select|gender|Geschlecht *|Frau=w;Herr=m|[no_db]|defaultwert|multiple=1
                        $yform->setValueField('select', array($field, $v['label'], $v['options'], '', $v['default'], 0));
                        break;

                    case 'table':
                        // ist fest eingetragen, damit keine Dinge durcheinandergehen

                        if ($func == 'edit' ) {
                            $yform->setValueField('showvalue', array($field, $v['label']));
                        } else {
                            $_tables = rex_yform_manager_table::getAll();
                            $_options = array();
                            if (isset($v['empty_option']) && $v['empty_option']) {
                                $_options[0] = '–=';
                            }
                            foreach ($_tables as $_table) {
                                $_options[$_table['table_name']] = str_replace('=', '-', rex_i18n::translate($_table['name']) . ' [' . $_table['table_name'] . ']' ) . '=' . $_table['table_name'];
                                $_options[$_table['table_name']] = str_replace(',', '.', $_options[$_table['table_name']]);
                            }
                            if (!isset($v['default'])) {
                                $v['default'] = '';
                            }
                            $yform->setValueField('select', array($field, $v['label'], implode(',', $_options), '', $v['default'], 0));

                        }
                        break;

                    case 'textarea':
                        $yform->setValueField('textarea', array($field, $v['label']));
                        break;

                    case 'table.field':
                        // Todo:

                    case 'select_name':
                        $_fields = array();
                        foreach ($table->getValueFields() as $_k => $_v) {
                            $_fields[] = $_k;
                        }
                        $yform->setValueField('select', array($field, $v['label'], implode(',', $_fields), '', '', 0));
                        break;

                    case 'select_names':
                        $_fields = array();
                        foreach ($table->getValueFields() as $_k => $_v) {
                            $_fields[] = $_k;
                        }
                        $yform->setValueField('select', array($field, $v['label'], implode(',', $_fields), '', '', 1, 5));
                        break;

                    default:
                        // nur beim "Bezeichnungsfeld"
                        if ($field == 'label' && $type_real_field != '' && !isset($v['value'])) {
                            $v['value'] = $type_real_field;
                        } elseif (!isset($v['value'])) {
                            $v['value'] = '';
                        }
                        $yform->setValueField('text', array($field, $v['label'], $v['value']));
                }

            }

            $yform->setActionField('showtext', array('', '<p>' . rex_i18n::msg('yform_thankyouforentry') . '</p>'));
            $yform->setObjectparams('main_table', rex_yform_manager_field::table());

            if ($func == 'edit') {
                $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_save'));
                $yform->setHiddenField('field_id', $field_id);
                $yform->setActionField('manage_db', array(rex_yform_manager_field::table(), "id=$field_id"));
                $yform->setObjectparams('main_id', $field_id);
                $yform->setObjectparams('main_where', "id=$field_id");
                $sql = rex_sql::factory();
                $sql->setQuery('SELECT * FROM ' . rex_yform_manager_field::table() . " WHERE id=$field_id");
                foreach ($selectFields as $alias => $field) {
                    if ($alias != $field) {
                        if ((!$sql->hasValue($field) || null === $sql->getValue($field) || '' === $sql->getValue($field)) && $sql->hasValue($alias)) {
                            $sql->setValue($field, $sql->getValue($alias));
                        }
                        $yform->setValueField('hidden', array($alias, ''));
                    }
                }
                $yform->setObjectparams('sql_object', $sql);
                $yform->setObjectparams('getdata', true);

            } elseif ($func == 'add') {
                $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_add'));
                $yform->setActionField('manage_db', array(rex_yform_manager_field::table()));

            }

            if ($type_id == 'value') {
                $yform->setValueField('checkbox', array('list_hidden', rex_i18n::msg('yform_hideinlist'), 1, '1'));
                $yform->setValueField('checkbox', array('search', rex_i18n::msg('yform_useassearchfieldalidatenamenotempty'), 1, '1'));

            } elseif ($type_id == 'validate') {
                $yform->setValueField('hidden', array('list_hidden', 1));

            }

            $form = $yform->getForm();

            if ($yform->objparams['form_show']) {

                if ($func == 'add') {
                    $title = rex_i18n::msg('yform_addfield') . ' "' . $type_name. '"';
                } else {
                    $title = rex_i18n::msg('yform_editfield') . ' "' . $type_name .'"';
                }

                $fragment = new rex_fragment();
                $fragment->setVar('class', 'edit', false);
                $fragment->setVar('title', $title);
                $fragment->setVar('body', $form, false);
                // $fragment->setVar('buttons', $buttons, false);
                $form = $fragment->parse('core/page/section.php');

                echo $form;
                $table_echo = '<a class="btn btn-default" href="index.php?' . $link_vars . '&amp;table_name=' . $table->getTableName() . '">' . rex_i18n::msg('yform_back_to_overview') . '</a>';
                $fragment = new rex_fragment();
                $fragment->setVar('footer', $table_echo, false);
                echo $fragment->parse('core/page/section.php');

                $func = '';
            } else {
                if ($func == 'edit') {
                    $this->generateAll();
                    echo rex_view::success(rex_i18n::msg('yform_thankyouforupdate'));

                } elseif ($func == 'add') {
                    $this->generateAll();
                    echo rex_view::success(rex_i18n::msg('yform_thankyouforentry'));

                }
                $func = 'list';
            }
        }





        // ********************************************* LOESCHEN
        if ($func == 'delete') {

            $sf = rex_sql::factory();
            $sf->debugsql = self::$debug;
            $sf->setQuery('select * from ' . rex_yform_manager_field::table() . ' where table_name="' . $table->getTableName() . '" and id=' . $field_id);
            $sfa = $sf->getArray();
            if (count($sfa) == 1) {
                $query = 'delete from ' . rex_yform_manager_field::table() . ' where table_name="' . $table->getTableName() . '" and id=' . $field_id;
                $delsql = rex_sql::factory();
                $delsql->debugsql = self::$debug;
                $delsql->setQuery($query);
                echo rex_view::success(rex_i18n::msg('yform_tablefielddeleted'));
                $this->generateAll();

            } else {
                echo rex_view::warning(rex_i18n::msg('yform_tablefieldnotfound'));
            }
            $func = 'list';
        }








        // ********************************************* CREATE/UPDATE FIELDS
        if ($func == 'updatetable') {
            $this->generateAll();
            echo rex_view::info(rex_i18n::msg('yform_tablesupdated'));
            $func = 'list';
        }

        if ($func == 'updatetablewithdelete') {
            $this->generateAll(array('delete_fields' => true));
            echo rex_view::info(rex_i18n::msg('yform_tablesupdated'));
            $func = 'list';
        }



        if ($func == 'show_form_notation') {

            $formbuilder_fields = $table->getFields();

            $notation_php   = '';
            $notation_pipe  = '';
            $notation_email = '';

            $notation_php_pre = array(
            '$yform = new rex_yform();',
            '$yform->setObjectparams(\'form_skin\', \'bootstrap\');',
            '$yform->setObjectparams(\'form_showformafterupdate\', 0);',
            '$yform->setObjectparams(\'real_field_names\', true);',
            );

            $notation_php .= implode("\n", $notation_php_pre) . "\n";


            $notation_pipe_pre = array(
            'objparams|form_skin|bootstrap',
            'objparams|form_showformafterupdate|0',
            'objparams|real_field_names|true',
            );

            $notation_pipe .= implode("\n", $notation_pipe_pre) . "\n";


            foreach ($formbuilder_fields as $field) {
                $class = 'rex_yform_'.$field->getType().'_'.$field->getTypeName();

                $cl = new $class;
                $definitions = $cl->getDefinitions();

                $values = array();
                $i = 1;
                foreach ($definitions['values'] as $key => $_) {
                    $key = $this->getFieldName($key, $field['type_id']);
                    if (isset($field[$key])) {
                        $values[] = htmlspecialchars($field[$key]);
                    } elseif (isset($field['f' . $i])) {
                        $values[] = htmlspecialchars($field['f' . $i]);
                    } else {
                        $values[] = '';
                    }
                    $i++;
                }

                if ($field['type_id'] == 'value') {

                    $notation_php .= "\n" . '$yform->setValueField(\'' . $field['type_name'] . '\', array("' . rtrim(implode('","', $values), '","') . '"));';
                    $notation_pipe .= "\n" . $field['type_name'] . '|' . rtrim(implode('|', $values), '|') . '|';
                    $notation_email .= "\n" . $field['name'] . ': REX_YFORM_DATA[field="' . $field['name'] . '"]';

                } elseif ($field['type_id'] == 'validate') {

                    $notation_php .= "\n" . '$yform->setValidateField(\'' . $field['type_name'] . '\', array("' . rtrim(implode('","', $values), '","') . '"));';
                    $notation_pipe .= "\n" . $field['type_id'] . '|' . $field['type_name'] . '|' . rtrim(implode('|', $values), '|') . '|';

                } elseif ($field['type_id'] == 'action') {

                    $notation_php .= "\n" . '$yform->setActionField(\'' . $field['type_name'] . '\', array("' . rtrim(implode('","', $values), '","') . '"));';
                    $notation_pipe .= "\n" . $field['type_id'] . '|' . $field['type_name'] . '|' . rtrim(implode('|', $values), '|') . '|';
                }
            }

            $notation_php  .= "\n\n"  . '$yform->setActionField(\'tpl2email\', array(\'emailtemplate\', \'emaillabel\', \'email@domain.de\'));';
            $notation_php .= "\n".'echo $yform->getForm();';

            $notation_pipe .= "\n\n"  . 'action|tpl2email|emailtemplate|emaillabel|email@domain.de';

            $fragment = new rex_fragment();
            $fragment->setVar('title', 'PHP');
            $fragment->setVar('body', '<pre class="pre-scrollable">' . $notation_php . '</pre>', false);
            $content = $fragment->parse('core/page/section.php');
            echo $content;

            $fragment = new rex_fragment();
            $fragment->setVar('title', 'Pipe');
            $fragment->setVar('body', '<pre class="pre-scrollable">' . $notation_pipe . '</pre>', false);
            $content = $fragment->parse('core/page/section.php');
            echo $content;

            $fragment = new rex_fragment();
            $fragment->setVar('title', 'E-Mail');
            $fragment->setVar('body', '<pre class="pre-scrollable">' . $notation_email . '</pre>', false);
            $content = $fragment->parse('core/page/section.php');
            echo $content;

            $func = 'list';
        }


        // ********************************************* LIST
        if ($func == 'list') {

            $show_list = true;
            $show_list = rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_TABLE_FIELD_FUNC', $show_list,
            [
            'table' => $table,
            'link_vars' => $this->getLinkVars(),
            ]
            )
            );

            if ($show_list) {

                function rex_yform_list_format($p, $value = '') {
                    if ($value != '') {
                        $p['value'] = $value;
                    }
                    switch ($p['list']->getValue('type_id')) {
                        case 'validate':
                            $style = 'color:#aaa;'; // background-color:#cfd9d9;
                            break;
                        case 'action':
                            $style = 'background-color:#cfd9d9;';
                            break;
                        default:
                            $style = 'background-color:#eff9f9;';
                            break;
                    }
                    return '<td style="' . $style . '">' . $p['value'] . '</td>';
                }

                function rex_yform_list_edit_format($p)
                {
                    return rex_yform_list_format($p, $p['list']->getColumnLink(rex_i18n::msg('yform_function'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('yform_edit')));
                }

                function rex_yform_list_delete_format($p)
                {
                    return rex_yform_list_format($p, $p['list']->getColumnLink(rex_i18n::msg('yform_delete'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('yform_delete')));
                }

                $context = new rex_context(
                $this->getLinkVars()
                );
                $items = [];
                $item = [];
                $item['label'] = rex_i18n::msg('yform_manager_show_form_notation');
                $item['url'] = $context->getUrl(['table_name' => $table->getTableName(), 'func' => 'show_form_notation']);
                $item['attributes']['class'][] = 'btn-default';
                if (rex_request('func', 'string') == 'show_form_notation') {
                    $item['attributes']['class'][] = 'active';
                }
                $items[] = $item;

                $item = [];
                $item['label'] = rex_i18n::msg('yform_updatetable');
                $item['url'] = $context->getUrl(['table_name' => $table->getTableName(), 'func' => 'updatetable']);
                $item['attributes']['class'][] = 'btn-default';
                if (rex_request('func', 'string') == 'updatetable') {
                    $item['attributes']['class'][] = 'active';
                }
                $items[] = $item;

                $item = [];
                $item['label'] = rex_i18n::msg('yform_updatetable_with_delete');
                $item['url'] = $context->getUrl(['table_name' => $table->getTableName(), 'func' => 'updatetablewithdelete']);
                $item['attributes']['class'][] = 'btn-default';
                if (rex_request('func', 'string') == 'updatetablewithdelete') {
                    $item['attributes']['class'][] = 'active';
                }
                $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_updatetable_with_delete_confirm') . '\')';

                $items[] = $item;

                $fragment = new rex_fragment();
                $fragment->setVar('buttons', $items, false);
                $fragment->setVar('size', 'xs', false);
                $panel_options = $fragment->parse('core/buttons/button_group.php');




                $sql = 'select id, prio, type_id, type_name, name from ' . rex_yform_manager_field::table() . ' where table_name="' . $table->getTableName() . '" order by prio';
                $list = rex_list::factory($sql, 30);
                // $list->debug = 1;
                // $list->setColumnFormat('id', 'Id');

                $tdIcon = '<i class="rex-icon rex-icon-table"></i>';
                $thIcon = '<a href="' . $list->getUrl(['table_name' => $table->getTableName(), 'func' => 'choosenadd']) . '"' . rex::getAccesskey(rex_i18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
                $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
                $list->setColumnParams($thIcon, ['field_id' => '###id###', 'func' => 'edit', 'type_name' => '###type_name###', 'type_id' => '###type_id###']);

                foreach ($this->getLinkVars() as $k => $v) {
                    $list->addParam($k, $v);
                }
                $list->addParam('start', rex_request('start', 'int'));

                $list->addParam('table_name', $table->getTableName());

                $list->removeColumn('id');

                $list->setColumnLabel('prio', rex_i18n::msg('yform_manager_table_prio_short'));
                //$list->setColumnLayout('prio', ['<th class="rex-table-priority">###VALUE###</th>', '<td class="rex-table-priority" data-title="' . rex_i18n::msg('yform_manager_table_prio_short') . '">###VALUE###</td>']);
                $list->setColumnLayout('prio', array('<th>###VALUE###</th>', '###VALUE###'));
                $list->setColumnFormat('prio', 'custom', 'rex_yform_list_format' );

                $list->setColumnLabel('type_id', rex_i18n::msg('yform_manager_type_id'));
                $list->setColumnLayout('type_id', array('<th>###VALUE###</th>', '###VALUE###'));
                $list->setColumnFormat('type_id', 'custom', 'rex_yform_list_format' );

                $list->setColumnLabel('type_name', rex_i18n::msg('yform_manager_type_name'));
                $list->setColumnLayout('type_name', array('<th>###VALUE###</th>', '###VALUE###'));
                $list->setColumnFormat('type_name', 'custom', 'rex_yform_list_format' );

                $list->setColumnLabel('name', rex_i18n::msg('yform_manager_name'));
                $list->setColumnLayout('name', array('<th>###VALUE###</th>', '###VALUE###')); // ###VALUE###
                $list->setColumnFormat('name', 'custom', 'rex_yform_list_format' );

                $list->addColumn(rex_i18n::msg('yform_function'), '<i class="rex-icon rex-icon-edit"></i> ' . rex_i18n::msg('yform_edit'));
                $list->setColumnParams(rex_i18n::msg('yform_function'), array('field_id' => '###id###', 'func' => 'edit', 'type_name' => '###type_name###', 'type_id' => '###type_id###', ));
                $list->setColumnLayout(rex_i18n::msg('yform_function'), array('<th class="rex-table-action" colspan="2">###VALUE###</th>', '###VALUE###'));
                $list->setColumnFormat(rex_i18n::msg('yform_function'), 'custom', 'rex_yform_list_edit_format' );

                $list->addColumn(rex_i18n::msg('yform_delete'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('yform_delete'));
                $list->setColumnParams(rex_i18n::msg('yform_delete'), array('field_id' => '###id###', 'func' => 'delete'));
                $list->setColumnLayout(rex_i18n::msg('yform_delete'), array('', '###VALUE###'));
                $list->setColumnFormat(rex_i18n::msg('yform_delete'), 'custom', 'rex_yform_list_delete_format' );
                $list->addLinkAttribute(rex_i18n::msg('yform_delete'), 'onclick', 'return confirm(\' [###type_id###, ###type_name###, ###name###] ' . rex_i18n::msg('yform_delete') . ' ?\')');

                $content = $list->get();

                $fragment = new rex_fragment();
                $fragment->setVar('title', rex_i18n::msg('yform_manager_tablefield_overview'));
                $fragment->setVar('options', $panel_options, false);
                $fragment->setVar('content', $content, false);
                $content = $fragment->parse('core/page/section.php');
                echo $content;

            }

        }


    }

    private function getFieldName($key, $type)
    {
        if (is_int($key)) {
            $key++;
            if (1 === $key) {
                return 'name';
            }
            if (2 === $key && 'value' === $type) {
                return 'label';
            }
            return 'f' . $key;
        }

        if (in_array($key, self::$reservedFieldColumns)) {
            $key = 'field_' . $key;
        }
        return $key;
    }



    // ----- Allgemeine Methoden



    // ----- Felder

    static function checkField($l, $v, $p)
    {
        $q = 'select * from ' . rex_yform_manager_field::table() . ' where table_name="' . $p['table_name'] . '" and type_id="value" and ' . $l . '="' . $v . '" LIMIT 1';
        $c = rex_sql::factory();
        $c->debugsql = self::$debug;
        $c->setQuery($q);
        if ($c->getRows() > 0) {
            return true;
        }
        return false;
    }

    function setFilterFields($DataPageFilterFields = array())
    {
        $this->DataPageFilterFields = $DataPageFilterFields;
    }

    function getFilterFields()
    {
        if (!is_array($this->DataPageFilterFields)) {
            return array();
        } else {
            return $this->DataPageFilterFields;
        }
    }

    function createTable($mifix = '', $data_table, $params = array(), $debug = false)
    {

        // Tabelle erstellen wenn noch nicht vorhanden
        $c = rex_sql::factory();
        $c->debugsql = $debug;
        $c->setQuery('CREATE TABLE IF NOT EXISTS `' . $data_table . '` ( `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        // Tabellenset in die Basics einbauen, wenn noch nicht vorhanden
        $c = rex_sql::factory();
        $c->debugsql = $debug;
        $c->setQuery('DELETE FROM ' . rex_yform_manager_table::table() . ' where table_name="' . $data_table . '"');
        $c->setTable(rex_yform_manager_table::table());

        $params['table_name'] = $data_table;
        if (!isset($params['status'])) {
            $params['status'] = 1;
        }

        if (!isset($params['name'])) {
            $params['name'] = 'Tabelle "' . $data_table . '"';
        }

        if (!isset($params['prio'])) {
            $params['prio'] = 100;
        }

        if (!isset($params['search'])) {
            $params['search'] = 0;
        }

        if (!isset($params['hidden'])) {
            $params['hidden'] = 0;
        }

        if (!isset($params['export'])) {
            $params['export'] = 0;
        }

        foreach ($params as $k => $v) {
            $c->setValue($k, $v);
        }

        $c->insert();

        return true;

    }

    /**
     * @deprecated
     */
    function addDataFields($data_table, $fields, $debug = false)
    {
        rex_yform_manager_table_api::generateTablesAndFields();
    }


    /**
     * @deprecated
     */
    function generateAll($f = array())
    {
        rex_yform_manager_table_api::generateTablesAndFields(isset($f['delete_fields']) ? $f['delete_fields'] : false);
    }

    static function checkMediaInUse($params)
    {
        $warning = $params['subject'];

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT `table_name`, `type_name`, `name` FROM `' . rex_yform_manager_field::table() . '` WHERE `type_id`="value" AND `type_name` IN("be_medialist","be_mediapool","mediafile")');

        $rows = $sql->getRows();

        if ($rows == 0) {
            return $warning;
        }

        $where = array();
        $filename = addslashes($params['filename']);
        while ($sql->hasNext()) {
            $table = $sql->getValue('table_name');
            switch ($sql->getValue('type_name')) {
                case 'be_mediapool':
                case 'mediafile':
                    $where[$table][] = $sql->getValue('name') . '="' . $filename . '"';
                    break;
                case 'be_medialist':
                    $where[$table][] = 'FIND_IN_SET("' . $filename . '", ' . $sql->getValue('name') . ')';
                    break;
                default :
                    trigger_error('Unexpected fieldtype "' . $sql->getValue('type_name') . '"!', E_USER_ERROR);
            }
            $sql->next();
        }

        $tupel = '';
        foreach ($where as $table => $cond) {
            $sql->setQuery('SELECT id FROM ' . $table . ' WHERE ' . implode(' OR ', $cond));

            while ($sql->hasNext()) {
                $sql_tupel = rex_sql::factory();
                $sql_tupel->setQuery('SELECT name FROM `' . rex_yform_manager_table::table() . '` WHERE `table_name`="' . $table . '"');

                $tupel .= '<li><a href="javascript:openPage(\'index.php?page=yform/manager/data_edit&amp;table_name=' . $table . '&amp;data_id=' . $sql->getValue('id') . '&amp;func=edit\')">' . $sql_tupel->getValue('name') . ' [id=' . $sql->getValue('id') . ']</a></li>';

                $sql->next();
            }
        }

        if ($tupel != '') {
            $warning[] = 'Tabelle<br /><ul>' . $tupel . '</ul>';
        }

        return $warning;
    }


}
