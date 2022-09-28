<?php

/**
 * yform.
 *
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
    /** @var rex_yform_manager_table|null */
    public $table;
    public $linkvars = [];
    public $type = '';
    public $dataPageFunctions = [];
    public static $debug = false;

    protected static $reservedFieldColumns = ['id', 'table_name', 'prio', 'type_id', 'type_name', 'list_hidden', 'search'];

    public function __construct()
    {
        $this->setDataPageFunctions();
    }

    // ----- Permissions
    public function setDataPageFunctions($f = ['add', 'delete', 'search', 'export', 'import', 'truncate_table'])
    {
        $this->dataPageFunctions = $f;
    }

    public function hasDataPageFunction($f)
    {
        return in_array($f, $this->dataPageFunctions) ? true : false;
    }

    // ----- Seitenausgabe
    public function setLinkVars($linkvars)
    {
        $this->linkvars = array_merge($this->linkvars, $linkvars);
    }

    public function getLinkVars()
    {
        return $this->linkvars;
    }

    public function getDataPage()
    {
        rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_DATA_PAGE', $this));

        $func = rex_request('func', 'string', '');
        $data_id = rex_request('data_id', 'int', 0);

        // -------------- rex_yform_manager_filter and sets

        $field_names = [];
        foreach ($this->table->getValueFields() as $field) {
            $field_names[] = $field->getName();
        }

        $rex_yform_filter = rex_request('rex_yform_filter', 'array');
        foreach ($rex_yform_filter as $k => $v) {
            if (!in_array($k, $field_names)) {
                unset($rex_yform_filter[$k]);
            }
        }

        $rex_yform_set = rex_request('rex_yform_set', 'array');
        foreach ($rex_yform_set as $k => $v) {
            if (!in_array($k, $field_names)) {
                unset($rex_yform_set[$k]);
            }
        }

        $rex_yform_list = [];
        $rex_yform_list['list'] = rex_request('list', 'string');
        $rex_yform_list['sort'] = rex_request('sort', 'string');
        $rex_yform_list['sorttype'] = rex_request('sorttype', 'string');
        $rex_yform_list['start'] = rex_request('start', 'int', null) ?? rex_request($rex_yform_list['list'].'_start', 'int', null) ?? 0;

        $_csrf_key = $this->table->getCSRFKey();
        $rex_yform_list += rex_csrf_token::factory($_csrf_key)->getUrlParams();

        $popup = false;
        $rex_yform_manager_opener = rex_request('rex_yform_manager_opener', 'array');
        if (isset($rex_yform_manager_opener['id']) && '' != $rex_yform_manager_opener['id']) {
            $popup = true; // id, field, multiple
        }

        $rex_yform_manager_popup = rex_request('rex_yform_manager_popup', 'int');
        if (1 == $rex_yform_manager_popup) {
            $popup = true;
        }

        $rex_yform_filter = rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_DATA_EDIT_FILTER', $rex_yform_filter, [
            'table' => $this->table,
        ]));

        $rex_yform_set = rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_DATA_EDIT_SET', $rex_yform_set, [
            'table' => $this->table,
        ]));

        $searchObject = new rex_yform_manager_search($this->table);
        $searchObject->setSearchLinkVars($this->getLinkVars());
        $searchObject->setSearchLinkVars($rex_yform_list);
        $searchObject->setSearchLinkVars(['rex_yform_filter' => $rex_yform_filter]);
        $searchObject->setSearchLinkVars(['rex_yform_set' => $rex_yform_set]);
        $searchObject->setSearchLinkVars(['rex_yform_manager_opener' => $rex_yform_manager_opener]);
        $searchObject->setSearchLinkVars(['rex_yform_manager_popup' => $rex_yform_manager_popup]);

        $searchform = '';
        if ($this->hasDataPageFunction('search')) {
            $fragment = new rex_fragment();
            $fragment->setVar('class', 'edit', false);
            $fragment->setVar('title', rex_i18n::msg('yform_manager_search'));
            $fragment->setVar('body', $searchObject->getForm(), false);
            $searchform = $fragment->parse('core/page/section.php');
            $this->setLinkVars($searchObject->getSearchVars());
        }

        $description = $popup || ('' == $this->table->getDescription()) ? '' : '<br />' . $this->table->getDescription();

        echo rex_extension::registerPoint(
            new rex_extension_point(
                'YFORM_MANAGER_DATA_PAGE_HEADER',
                rex_view::title(rex_i18n::msg('yform_table') . ': ' . $this->table->getNameLocalized() . ' <small>[' . $this->table->getTablename() . ']' . $description . '</small>', ''),
                [
                    'yform' => $this,
                ]
            )
        );

        echo rex_extension::registerPoint(new rex_extension_point('YFORM_MANAGER_REX_INFO', ''));

        // -------------- Searchfields / Searchtext
        $rex_link_vars = array_merge(
            $this->getLinkVars(),
            $rex_yform_list,
            ['rex_yform_manager_opener' => $rex_yform_manager_opener],
            ['rex_yform_manager_popup' => $rex_yform_manager_popup],
            ['rex_yform_filter' => $rex_yform_filter],
            ['rex_yform_set' => $rex_yform_set]
        );

        if ($data_id > 0) {
            $data_query = $this->table->query()
                ->alias('t0')
                ->where('t0.id', $data_id);
            $data_query = $this->getDataListQuery($data_query, array_merge($rex_yform_filter, $rex_yform_set), $searchObject);
            $data_collection = $data_query->find();

            if (1 == count($data_collection)) {
                $data_id = $data_collection[0]->getId();
            } else {
                $data_id = null;
            }
        }

        $mainFragment = new rex_fragment();
        $mainMessages = [];

        if ($this->table->isGranted('EDIT', rex::getUser())) {
            $func = !in_array($func, ['delete', 'dataset_delete', 'truncate_table', 'add',
                'edit', 'import', 'history', 'dataset_export', 'collection_edit', ]) ? '' : $func;
        } else {
            $func = ('edit' != $func) ? '' : 'edit';
        }

        if ('' != $func) {
            if (!rex_csrf_token::factory($_csrf_key)->isValid()) {
                $mainMessages[] = [
                    'type' => 'error',
                    'message' => rex_i18n::msg('csrf_token_invalid'),
                ];
            }
        }

        switch ($func) {
            case 'import':
                if (!$popup && $this->hasDataPageFunction('import')) {
                    $mainMessages[] = [
                        'type' => 'info',
                        'message' => '<b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b>',
                        'link' => 'index.php?' . http_build_query(array_merge($rex_link_vars)),
                    ];

                    ob_start();
                    include rex_path::plugin('yform', 'manager', 'pages/data_import.php');
                    $dataImport = ob_get_contents();
                    ob_end_clean();

                    $mainFragment->setVar('importPage', $dataImport, false);
                }
                break;
            case 'history':
                if (!$popup) {
                    $mainMessages[] = [
                        'type' => 'info',
                        'message' => '<b>&laquo; ' . rex_i18n::msg('yform_back_to_overview') . '</b>',
                        'link' => 'index.php?' . http_build_query(array_merge($rex_link_vars)),
                    ];

                    ob_start();
                    include rex_path::plugin('yform', 'manager', 'pages/data_history.php');
                    $dataHistory = ob_get_contents();
                    ob_end_clean();

                    $mainFragment->setVar('historyPage', $dataHistory, false);
                }
                break;
            case 'delete':
                if ($data_id && $this->hasDataPageFunction('delete')) {
                    if ($this->table->getRawDataset($data_id)->delete()) {
                        $mainMessages[] = [
                            'type' => 'success',
                            'message' => rex_i18n::msg('yform_datadeleted'),
                        ];
                    }
                }
                break;
            case 'dataset_delete':
                if ($this->hasDataPageFunction('truncate_table')) {
                    $query = $this->table->query()->alias('t0');
                    $query = $this->getDataListQuery($query, array_merge($rex_yform_filter, $rex_yform_set), $searchObject);
                    $collection = $query->find();
                    $collection->delete();
                    $mainMessages[] = [
                        'type' => 'success',
                        'message' => rex_i18n::msg('yform_dataset_deleted'),
                    ];
                }
                break;
            case 'truncate_table':
                if (!$popup && $this->hasDataPageFunction('truncate_table')) {
                    $this->table->query()->find()->delete();
                    $mainMessages[] = [
                        'type' => 'success',
                        'message' => rex_i18n::msg('yform_table_truncated'),
                    ];
                }
                break;
            case 'dataset_export':
                if (!$popup && $this->hasDataPageFunction('export')) {
                    ob_end_clean();
                    include rex_path::plugin('yform', 'manager', 'pages/data_export.php');
                    exit;
                }
                break;
            case 'add':
            case 'edit':
            case 'collection_edit':
                if (
                    ('add' == $func && $this->hasDataPageFunction('add')) ||
                    ('edit' == $func && $data_id) ||
                    ('collection_edit' == $func && $this->table->isMassEditAllowed())
                ) {
                    if ('collection_edit' === $func) {
                        $query = $this->table->query()->alias('t0');
                        $query = $this->getDataListQuery($query, array_merge($rex_yform_filter, $rex_yform_set), $searchObject);
                        $data = $query->find();
                        $yform = $data->getForm();
                        $yform->setObjectparams('csrf_protection', false);
                    } else {
                        $data = 'add' == $func ? $this->table->createDataset() : $this->table->getRawDataset($data_id);
                        $yform = $data->getForm();
                        $yform->setObjectparams('form_name', 'data_edit-'.$this->table->getTableName());
                    }

                    $yform->canEdit(rex_yform_manager_table_authorization::onAttribute('EDIT', $this->table, rex::getUser()));
                    $yform->canView(rex_yform_manager_table_authorization::onAttribute('VIEW', $this->table, rex::getUser()));

                    $yform->setHiddenFields($this->getLinkVars());
                    $yform->setHiddenFields($rex_yform_list);
                    $yform->setHiddenFields(['rex_yform_filter' => $rex_yform_filter]);
                    $yform->setHiddenFields(['rex_yform_set' => $rex_yform_set]);
                    $yform->setHiddenFields(['rex_yform_manager_opener' => $rex_yform_manager_opener]);
                    $yform->setHiddenFields(['rex_yform_manager_popup' => $rex_yform_manager_popup]);

                    if ('' != rex_request('rex_yform_show_formularblock', 'string')) {
                        // Optional .. kann auch geloescht werden. Dient nur zu Hilfe beim Aufbau
                        // von yform-Formularen über php
                        // Textblock gibt den formalarblock als text aus, um diesen in das yform modul einsetzen zu können.
                        //  rex_yform_show_formularblock=1
                        $text_block = '';
                        foreach ($this->table->getFields() as $field) {
                            $class = 'rex_yform_'.$field->getType().'_'.$field->getTypeName();

                            /** @var rex_yform_base_abstract $cl */
                            $cl = new $class();
                            $definitions = $cl->getDefinitions();

                            $values = [];
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
                                ++$i;
                            }

                            if ('value' == $field->getType()) {
                                $text_block .= "\n" . '$yform->setValueField("' . $field->getTypeName() . '",array("' . implode('","', $values) . '"));';
                            } elseif ('validate' == $field->getType()) {
                                $text_block .= "\n" . '$yform->setValidateField("' . $field->getTypeName() . '",array("' . implode('","', $values) . '"));';
                            } elseif ('action' == $field->getType()) {
                                $text_block .= "\n" . '$yform->setActionField("' . $field->getTypeName() . '",array("' . implode('","', $values) . '"));';
                            }
                            // $text_block .= "\n".$field["type_name"].'|'.implode("|",$values);
                        }
                        echo '<pre>' . $text_block . '</pre>';
                    }

                    $yform->setObjectparams('fixdata', $rex_yform_set);
                    $yform_clone = clone $yform;
                    $yform->setHiddenField('func', $func); // damit es neu im clone gesetzt werden kann

                    $buttonLabels = '';
                    switch ($func) {
                        case 'edit':
                            $yform->setHiddenField('data_id', $data_id);
                            $yform->setObjectparams('getdata', true);
                            $buttonLabels = rex_i18n::msg('yform_save').','.rex_i18n::msg('yform_save_apply');
                            break;
                        case 'add':
                            $buttonLabels = rex_i18n::msg('yform_add').','.rex_i18n::msg('yform_add_apply');
                            break;
                        case 'collection_edit':
                            $buttonLabels = rex_i18n::msg('yform_save').','.rex_i18n::msg('yform_save_apply');
                    }

                    if ($yform->isEditable()) {
                        $yform->setValueField('submit', ['name' => 'submit', 'labels' => $buttonLabels, 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
                    } else {
                        $yform->setFieldValue('send', [], '');
                        $yform->setObjectparams('submit_btn_show', false);
                        if (isset($rex_yform_manager_opener['id'])) {
                            // TODO:
                            // Übernehmen aus dem Datensatz heraus

                            // $yform->setValueField('submit', ['name' => 'submit', 'labels' => rex_i18n::msg('yform_save').','.rex_i18n::msg('yform_save_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);

                            /*return '<a href="javascript:setYFormDataset('.$params['params']['opener_id'].',###id###,\''.rex_escape(
                                    $value,
                                    'js'
                                ).' [id=###id###]\','.$params['params']['opener_multiple'].')">'.rex_i18n::msg(
                                    'yform_data_select'
                                ).'</a>';
                            */
                        }
                    }

                    $sql_db = rex_sql::factory();
                    $sql_db->beginTransaction();

                    $transactionErrorMessage = null;

                    try {
                        $form = $data->executeForm($yform, static function (rex_yform $yform) {
                            /** @var rex_yform_value_abstract $valueObject */
                            foreach ($yform->objparams['values'] as $valueObject) {
                                if ('submit' == $valueObject->getName()) {
                                    if (2 == $valueObject->getValue()) { // apply
                                        $yform->setObjectparams('form_showformafterupdate', 1);
                                        // $yform->executeFields();
                                    }
                                }
                            }
                        });

                        $sql_db->commit();
                        if ($yform->objparams['actions_executed']) {
                            if ('add' == $func) {
                                $submit_type = 1; // normal, 2=apply
                                foreach ($yform->objparams['values'] as $valueObject) {
                                    /** @var rex_yform_value_abstract $valueObject */
                                    if ('submit' == $valueObject->getName()) {
                                        if (2 == $valueObject->getValue()) { // apply
                                            $submit_type = 2;
                                        }
                                    }
                                }

                                if (2 == $submit_type) {
                                    $data_id = $yform->objparams['main_id'];
                                    $func = 'edit';
                                    $yform = $yform_clone;
                                    $yform->setFieldValue('send', [], '');
                                    $yform->setHiddenField('func', $func);
                                    $yform->setHiddenField('data_id', $data_id);
                                    $yform->setObjectparams('main_id', $data_id);
                                    $yform->setObjectparams('main_where', "id=$data_id");
                                    $yform->setObjectparams('getdata', true);
                                    $yform->setObjectparams('send', false);
                                    $yform->setValueField('submit', ['name' => 'submit', 'labels' => rex_i18n::msg('yform_save').','.rex_i18n::msg('yform_save_apply'), 'values' => '1,2', 'no_db' => true, 'css_classes' => 'btn-save,btn-apply']);
                                    $form = $yform->getForm();
                                }
                            }
                        }
                        if ($yform->objparams['form_show'] || $yform->objparams['form_showformafterupdate']) {
                            if ('collection_edit' === $func) {
                                $title = rex_i18n::msg('yform_editdata_collection', $data->count());
                            } elseif ('add' == $func) {
                                $title = rex_i18n::msg('yform_adddata');
                            } else {
                                $title = rex_i18n::rawMsg('yform_editdata', $data_id);
                            }

                            $fragment = new rex_fragment();
                            $fragment->setVar('class', 'edit', false);
                            $fragment->setVar('title', $title);
                            $fragment->setVar('body', $form, false);
                            // $fragment->setVar('buttons', $buttons, false);
                            $form = $fragment->parse('core/page/section.php');

                            $mainFragment->setVar('detailForm', $form, false);

                            $mainMessages[] = [
                                'type' => 'info',
                                'message' => rex_i18n::msg('yform_back_to_overview'),
                                'link' => 'index.php?' . http_build_query(array_merge($rex_link_vars)),
                            ];
                        }

                        if ($yform->objparams['actions_executed']) {
                            if ($yform->hasWarnings()) {
                                $mainMessages[] = [
                                    'type' => 'error',
                                    'message' => rex_i18n::msg('yform_errors_occurred'),
                                ];
                            } elseif (!$yform->isEditable()) {
                            } elseif ('collection_edit' == $func) {
                                $mainMessages[] = [
                                    'type' => 'info',
                                    'message' => rex_i18n::msg('yform_thankyouforupdates'),
                                ];
                            } elseif ('edit' == $func) {
                                $mainMessages[] = [
                                    'type' => 'info',
                                    'message' => rex_i18n::msg('yform_thankyouforupdate'),
                                ];
                            } else {
                                // -> add
                                $mainMessages[] = [
                                    'type' => 'info',
                                    'message' => rex_i18n::msg('yform_thankyouforentry'),
                                ];
                            }
                        }
                    } catch (\Throwable $e) {
                        $sql_db->rollBack();
                        $transactionErrorMessage = $e->getMessage();
                        if ($transactionErrorMessage) {
                            if (rex::getUser()->isAdmin()) {
                                dump($e);
                            }
                            $mainMessages[] = [
                                'type' => 'error',
                                'message' => rex_i18n::msg('yform_editdata_collection_error_abort', $transactionErrorMessage),
                            ];
                        }
                    }
                }
                break;
        }

        $query = $this->table->query()->alias('t0');
        $query = $this->getDataListQuery($query, $rex_yform_filter, $searchObject);

        /** @var rex_yform_list $list */
        $list = rex_yform_list::factory($query, $this->table->getListAmount());
        $list->addTableAttribute('class', 'table-striped table-hover yform-table-' . rex_string::normalize($this->table->getTableName()));

        $rex_yform_list[$list->getPager()->getCursorName()] = rex_request($list->getPager()->getCursorName(), 'int', 0);

        if ($this->hasDataPageFunction('add') && $this->table->isGranted('EDIT', rex::getUser())) {
            $thIcon = '<a class="rex-link-expanded" href="index.php?' . http_build_query(array_merge(['func' => 'add'], $rex_link_vars)) . '"' . rex::getAccesskey(rex_i18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
            $tdIcon = '<i class="rex-icon rex-icon-editmode"></i>';
            $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon" data-title="' . rex_i18n::msg('id') . '">###VALUE###</td>']);
            $list->setColumnParams($thIcon, array_merge(['data_id' => '###id###', 'func' => 'edit'], $rex_yform_list));
        } else {
            $thIcon = '_';
            $tdIcon = '<i class="rex-icon rex-icon-view"></i>';
            $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon" data-title="' . rex_i18n::msg('id') . '">###VALUE###</td>']);
            $list->setColumnParams($thIcon, array_merge(['data_id' => '###id###', 'func' => 'edit'], $rex_yform_list));
        }

        $list->setColumnLabel('id', rex_i18n::msg('yform_id'));
        $list->setColumnSortable('id');

        $link_list_params = array_merge(
            $this->getLinkVars(),
            ['table_name' => $this->table->getTablename()],
            ['rex_yform_filter' => $rex_yform_filter],
            ['rex_yform_set' => $rex_yform_set],
            ['rex_yform_manager_opener' => $rex_yform_manager_opener],
            ['rex_yform_manager_popup' => $rex_yform_manager_popup]
        );

        $recArray = static function ($key, $paramsArray) use ($list, &$recArray) {
            if (!is_array($paramsArray)) {
                $list->addParam($key, $paramsArray);
            } elseif (is_array($paramsArray)) {
                foreach ($paramsArray as $k => $v) {
                    $recArray($key.'['.$k.']', $v);
                }
            }
        };
        foreach ($link_list_params as $mainKey => $link_list_param) {
            $recArray($mainKey, $link_list_param);
        }

        foreach ($this->table->getFields() as $field) {
            if (!$field->isHiddenInList() && $field->getTypeName()) {
                if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getListValue')) {
                    $list->setColumnFormat(
                        $field->getName(),
                        'custom',
                        ['rex_yform_value_' . $field->getTypeName(), 'getListValue'],
                        ['field' => $field->toArray(), 'fields' => $this->table->getFields()]
                    );
                }
            }

            if ('value' == $field->getType()) {
                if ($field->isHiddenInList()) {
                    $list->removeColumn($field->getName());
                } else {
                    $list->setColumnSortable($field->getName());
                    $list->setColumnLabel($field->getName(), $field->getLabel());
                }
            }
        }

        $colspan = 1;
        if (isset($rex_yform_manager_opener['id'])) {
            $list->addColumn(rex_i18n::msg('yform_function'), '');
            $list->setColumnFormat(
                rex_i18n::msg('yform_function'),
                'custom',
                static function ($params) {
                    $value = '';

                    $tablefield = explode('.', $params['params']['opener_field']);
                    if (1 == count($tablefield)) {
                        if (isset($params['list']->getParams()['table_name'])) {
                            $target_table = $params['list']->getParams()['table_name'];
                            $target_field = $tablefield[0];
                            $values = rex_yform_value_be_manager_relation::getListValues($target_table, $target_field);
                            $value = $values[$params['list']->getValue('id')];
                        }
                    } else {
                        [$table_name, $field_name] = explode('.', $params['params']['opener_field']);
                        $table = rex_yform_manager_table::get($table_name);
                        if ($table) {
                            $fields = $table->getValueFields(['name' => $field_name]);
                            if (isset($fields[$field_name])) {
                                $target_table = $fields[$field_name]->getElement('table');
                                $target_field = $fields[$field_name]->getElement('field');

                                $values = rex_yform_value_be_manager_relation::getListValues(
                                    $target_table,
                                    $target_field
                                );
                                $value = $values[$params['list']->getValue('id')];
                            }
                        }
                    }
                    return '<a href="javascript:setYFormDataset('.$params['params']['opener_id'].',###id###,\''.rex_escape(
                        $value,
                        'js'
                    ).' [id=###id###]\','.$params['params']['opener_multiple'].')">'.rex_i18n::msg(
                        'yform_data_select'
                    ).'</a>';
                },
                [
                    'opener_id' => $rex_yform_manager_opener['id'],
                    'opener_field' => $rex_yform_manager_opener['field'],
                    'opener_multiple' => $rex_yform_manager_opener['multiple'],
                ]
            );
        } else {
            $actionButtons = [];

            $actionButtonParams = array_merge(
                $list->getParams(),
                $rex_yform_list,
                ['rex_yform_manager_opener' => $rex_yform_manager_opener],
                ['rex_yform_manager_popup' => $rex_yform_manager_popup]
            );

            if ($this->table->isGranted('EDIT', rex::getUser())) {
                $actionButtons['edit'] = '<a href="'.$list->getUrl(
                    array_merge($actionButtonParams, ['data_id' => '___id___', 'func' => 'edit']),
                    false
                ).'"><i class="rex-icon rex-icon-editmode"></i> ' . rex_i18n::msg('yform_edit').'</a>';
                if ($this->hasDataPageFunction('delete')) {
                    $actionButtons['delete'] = '<a onclick="return confirm(\' id=___id___ ' . rex_i18n::msg('yform_delete') . ' ?\')" href="'.$list->getUrl(
                        array_merge($actionButtonParams, ['data_id' => '___id___', 'func' => 'delete']),
                        false
                    ).'"><i class="rex-icon rex-icon-delete"></i> '.rex_i18n::msg('yform_delete').'</a>';
                }
                if (!$popup && $this->table->hasHistory()) {
                    $actionButtons['history'] = '<a href="'.$list->getUrl(
                        array_merge($actionButtonParams, ['dataset_id' => '___id___', 'func' => 'history', 'filter_dataset' => 1]),
                        false
                    ).'"><i class="rex-icon fa-history"></i> '.rex_i18n::msg('yform_history').'</a>';
                }
            } else {
                $actionButtons['view'] = '<a href="'.$list->getUrl(
                    array_merge($actionButtonParams, ['data_id' => '___id___', 'func' => 'edit']),
                    false
                ).'"><i class="rex-icon rex-icon-view"></i> ' . rex_i18n::msg('yform_view').'</a>';
            }

            $actionButtons = rex_extension::registerPoint(
                new rex_extension_point(
                    'YFORM_DATA_LIST_ACTION_BUTTONS',
                    $actionButtons,
                    [
                        'table' => $this->table,
                        'this' => $this,
                        'link_vars' => $rex_link_vars,
                    ]
                )
            );

            $fragment = new rex_fragment();
            $fragment->setVar('buttons', $actionButtons, false);
            $buttons = $fragment->parse('yform/manager/action_buttons.php');

            $list->addColumn(rex_i18n::msg('yform_function').' ', $buttons);
        }

        $list->setColumnLayout(rex_i18n::msg('yform_function').' ', ['<th class="rex-table-action" colspan="'.$colspan.'">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);

        $list = rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_LIST', $list, ['table' => $this->table]));

        if ($rex_yform_filter) {
            $filter = [];
            $getFilter = static function (rex_yform_manager_field $field, $value, $table) {
                $class = 'rex_yform_value_'.$field->getTypeName();
                $listValues = '';
                try {
                    $listValues = $class::getListValue([
                        'value' => $value,
                        'subject' => $value,
                        'field' => $field->getName(),
                        'params' => [
                            'field' => $field->toArray(),
                            'fields' => $table->getFields(),
                        ],
                    ]);
                } catch (Exception $e) {
                    dump($e);
                }

                return '<b>' . rex_i18n::translate($field->getLabel()) .':</b> ' . $listValues;
            };
            foreach ($rex_yform_filter as $key => $value) {
                $field = $this->table->getValueField($key);
                $filter[] = $getFilter($field, $value, $this->table);
            }
            echo rex_view::info(implode('<br>', $filter), 'rex-yform-filter');
        }

        $panel_options = [];

        if (!$popup) {
            $dataset_links = [];

            if ($this->table->isGranted('EDIT', rex::getUser())) {
                if ($this->table->isMassEditAllowed()) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_edit');
                    $item['url'] = 'index.php?' . http_build_query(array_merge(['func' => 'collection_edit'], $rex_link_vars));
                    $item['attributes']['class'][] = 'btn-default';
                    $item['attributes']['data-confirm'][] = rex_i18n::msg('yform_dataset_edit_confirm');
                    $dataset_links[] = $item;
                }

                if (1 == $this->table->isExportable() && $this->hasDataPageFunction('export')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_export');
                    $item['url'] = 'index.php?' . http_build_query(array_merge(['func' => 'dataset_export'], $rex_link_vars));
                    $item['attributes']['class'][] = 'btn-default';
                    $dataset_links[] = $item;
                }

                if ($this->table->isMassDeletionAllowed() && $this->hasDataPageFunction('truncate_table')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_delete');
                    $item['url'] = 'index.php?' . http_build_query(array_merge(['func' => 'dataset_delete'], $rex_link_vars));
                    $item['attributes']['class'][] = 'btn-delete';
                    $item['attributes']['id'] = 'dataset-delete';
                    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_dataset_delete_confirm') . '\');';
                    $dataset_links[] = $item;
                }
            }

            $table_links = [];

            if ($this->table->isGranted('EDIT', rex::getUser())) {
                if ($this->table->isImportable() && $this->hasDataPageFunction('import')) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_import');
                    $item['url'] = 'index.php?' . http_build_query(array_merge(['func' => 'import'], $rex_link_vars));
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
                    $item['url'] = 'index.php?' . http_build_query(array_merge(['func' => 'truncate_table'], $rex_link_vars));
                    $item['attributes']['class'][] = 'btn-delete';
                    $item['attributes']['id'] = 'truncate-table';
                    $item['attributes']['onclick'][] = 'return confirm(\'' . rex_i18n::msg('yform_truncate_table_confirm') . '\');';
                    $table_links[] = $item;
                }
                if ($this->table->hasHistory()) {
                    $item = [];
                    $item['label'] = rex_i18n::msg('yform_history');
                    $item['url'] = 'index.php?' . http_build_query(array_merge(['func' => 'history'], $rex_link_vars));
                    $item['attributes']['class'][] = 'btn-default';
                    $table_links[] = $item;
                }
            }

            $field_links = [];
            if ($this->table->isGranted('EDIT', rex::getUser()) && rex::getUser()->isAdmin()) {
                $item = [];
                $item['label'] = rex_i18n::msg('yform_edit');
                $item['url'] = 'index.php?page=yform/manager/table_field&table_name=' . $this->table->getTableName();
                $item['attributes']['class'][] = 'btn-default';
                $field_links[] = $item;
            }

            ['dataset_links' => $dataset_links, 'table_links' => $table_links, 'field_links' => $field_links] = rex_extension::registerPoint(
                new rex_extension_point(
                    'YFORM_DATA_LIST_LINKS',
                    ['dataset_links' => $dataset_links, 'table_links' => $table_links, 'field_links' => $field_links],
                    ['table' => $this->table, 'popup' => $popup]
                )
            );

            if (count($dataset_links) > 0) {
                $fragment = new rex_fragment();
                $fragment->setVar('size', 'xs', false);
                $fragment->setVar('buttons', $dataset_links, false);
                $panel_options[] = '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_dataset') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
            }

            if (count($table_links) > 0) {
                $fragment = new rex_fragment();
                $fragment->setVar('size', 'xs', false);
                $fragment->setVar('buttons', $table_links, false);
                $panel_options[] = '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_table') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
            }
            if (count($field_links) > 0) {
                $fragment = new rex_fragment();
                $fragment->setVar('size', 'xs', false);
                $fragment->setVar('buttons', $field_links, false);
                $panel_options[] = '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_manager_fields') . '</small> ' . $fragment->parse('core/buttons/button_group.php');
            }
        }

        $mainFragment->setVar('table', $this->table, false);
        $mainFragment->setVar('this', $this, false);
        $mainFragment->setVar('overview_title', rex_i18n::msg('yform_tabledata_overview'), false);
        $mainFragment->setVar('overview_options', $panel_options, false);
        $mainFragment->setVar('overview_list', $list, false);
        $mainFragment->setVar('messages', $mainMessages, false);
        $mainFragment->setVar('searchForm', $searchform, false);
        return $mainFragment->parse('yform/manager/page/layout.php');
    }

    public function getDataListQuery(rex_yform_manager_query $query, array $rex_filter = [], rex_yform_manager_search $searchObject = null)
    {
        $fields = $query->getTable()->getFields();

        foreach ($query->getTable()->getFields() as $field) {
            if (array_key_exists($field->getName(), $rex_filter) && 'value' == $field->getType()) { //  && $field->isSearchable()
                if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchFilter')) {
                    $query = call_user_func(
                        'rex_yform_value_' . $field->getTypeName() . '::getSearchFilter',
                        [
                            'field' => $field,
                            'fields' => $fields,
                            'value' => $rex_filter[$field->getName()],
                            'query' => $query,
                        ]
                    );
                    if ('rex_yform_manager_query' != get_class($query)) {
                        throw new Exception('getSearchFilter in rex_yform_value_' . $field->getTypeName() . ' does not return a rex_yform_manager_query');
                    }
                }
            }
        }
        if ($searchObject) {
            $query = $searchObject->getQueryFilter($query);
        }
        return rex_extension::registerPoint(new rex_extension_point('YFORM_DATA_LIST_QUERY', $query, ['filter' => $rex_filter]));
    }

    // ---------------------------------- table functions
    public function setTable(rex_yform_manager_table $table)
    {
        $this->table = $table;
    }

    // ---------------------------------- field functions
    public function getFieldPage()
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

        $TYPE = ['value' => rex_i18n::msg('yform_values'), 'validate' => rex_i18n::msg('yform_validates'), 'action' => rex_i18n::msg('yform_action')];

        $table = $this->table;

        $table_info = '<b>' . $table->getNameLocalized() . ' [<a href="index.php?page=yform/manager/table_edit&start=0&table_id='.$table->getId().'&func=edit">' . $table->getTableName() . '</a>]</b> ';
        echo rex_view::info($table_info);

        $_csrf_key = $this->table->getCSRFKey();

        if ('' != $func && in_array($func, ['delete', 'updatetablewithdelete', 'updatetable'])) {
            if (!rex_csrf_token::factory($_csrf_key)->isValid()) {
                echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
                $func = 'list';
            }
        }

        $mfields = $table->getMissingFields();
        $type_real_field = rex_request('type_real_field', 'string');
        if ('' != $type_real_field && !array_key_exists($type_real_field, $mfields)) {
            $type_real_field = '';
        }

        if ('' != $type_real_field) {
            $panel = '<dl class="dl-horizontal text-left">';

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

        $types = rex_yform::getTypeArray();
        if ('choosenadd' == $func) {
            $link = 'index.php?' . $link_vars . '&table_name=' . $table->getTableName() . '&func=add&';

            $content = [];
            $panels = [];

            if (!$table->hasId()) {
                $content[] = rex_i18n::msg('yform_id_is_missing').''.rex_i18n::msg('yform_id_missing_info');
            } else {
                if ('' == $type_real_field && count($mfields) > 0) {
                    $tmp = '';
                    $d = 0;
                    foreach ($mfields as $k => $v) {
                        ++$d;
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
                    $tmp_famous = '';
                    $tmp_deprecated = '';
                    $tmp = '';
                    foreach ($types['value'] as $k => $v) {
                        if (isset($v['manager']) && !$v['manager']) {
                        } elseif (isset($v['deprecated']) && $v['deprecated']) {
                            $tmp_deprecated .= '<tr class="yform-classes-deprecated"><th data-title="Value"><a class="btn btn-default btn-block" href="' . $link . 'type_id=value&type_name=' . $k . '&type_real_field=' . $type_real_field . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['deprecated'] .'<br />' . $v['description'] . '</td></tr>';
                        } elseif (isset($v['famous']) && $v['famous']) {
                            $tmp_famous .= '<tr class="yform-classes-famous"><th data-title="Value"><a class="btn btn-default btn-block" href="' . $link . 'type_id=value&type_name=' . $k . '&type_real_field=' . $type_real_field . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['description'] . '</td></tr>';
                        } else {
                            $tmp .= '<tr><th data-title="Value"><a class="btn btn-default btn-block" href="' . $link . 'type_id=value&type_name=' . $k . '&type_real_field=' . $type_real_field . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['description'] . '</td></tr>';
                        }
                    }
                    $tmp = '<table class="table table-hover yform-table-help">'.$tmp_famous.$tmp.$tmp_deprecated.'</table>';
                }
                $fragment = new rex_fragment();
                $fragment->setVar('title', $TYPE['value']);
                $fragment->setVar('content', $tmp, false);
                $panels[] = $fragment->parse('core/page/section.php');

                $tmp = '';
                if (isset($types['validate'])) {
                    ksort($types['validate']);
                    $tmp_famous = '';
                    $tmp = '';
                    foreach ($types['validate'] as $k => $v) {
                        if (isset($v['famous']) && $v['famous']) {
                            $tmp_famous .= '<tr class="yform-classes-famous"><th data-title="Validate"><a class="btn btn-default btn-block" href="' . $link . 'type_id=validate&type_name=' . $k . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['description'] . '</td></tr>';
                        } else {
                            $tmp .= '<tr><th data-title="Validate"><a class="btn btn-default btn-block" href="' . $link . 'type_id=validate&type_name=' . $k . '"><code>' . $k . '</code></a></th><td class="vertical-middle">' . $v['description'] . '</td></tr>';
                        }
                    }
                    $tmp = '<table class="table table-hover yform-table-help">'.$tmp_famous.$tmp.'</table>';
                }

                $fragment = new rex_fragment();
                $fragment->setVar('title', $TYPE['validate']);
                $fragment->setVar('content', $tmp, false);
                $panels[] = $fragment->parse('core/page/section.php');
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

        if (('add' == $func || 'edit' == $func) && isset($types[$type_id][$type_name])) {
            $field = new rex_yform_manager_field(['type_id' => $type_id, 'type_name' => $type_name]);

            $yform = new rex_yform();
            $yform->setDebug(false);

            foreach ($this->getLinkVars() as $k => $v) {
                $yform->setHiddenField($k, $v);
            }

            $yform->setObjectparams('form_name', $_csrf_key);

            $yform->setHiddenField('func', $func);
            $yform->setHiddenField('table_name', $table->getTableName());
            $yform->setHiddenField('type_real_field', $type_real_field);

            $yform->setHiddenField('list', rex_request('list', 'string'));
            $yform->setHiddenField('sort', rex_request('sort', 'string'));
            $yform->setHiddenField('sorttype', rex_request('sorttype', 'string'));
            $yform->setHiddenField('start', rex_request('start', 'string'));

            $yform->setValueField('hidden', ['table_name', $table->getTableName()]);
            $yform->setValueField('hidden', ['type_name', $type_name, 'REQUEST']);
            $yform->setValueField('hidden', ['type_id', $type_id, 'REQUEST']);

            $yform->setValueField('prio', ['prio', rex_i18n::msg('yform_values_defaults_prio'), ['type_id', 'type_name', 'name', 'label'], ['table_name']]);

            $selectFields = [];
            $i = 1;
            foreach ($types[$type_id][$type_name]['values'] as $k => $v) {
                $k_field = $this->getFieldName($k, $type_id);
                $selectFields['f' . $i] = $k_field;
                ++$i;

                switch ($v['type']) {
                    case 'name':
                        $v['notice'] = ($v['notice'] ?? '');
                        if ('edit' == $func) {
                            $yform->setValueField('showvalue', [$k_field, rex_i18n::msg('yform_values_defaults_name'), 'notice' => $v['notice']]);
                        } else {
                            if (!isset($v['value']) && '' != $type_real_field) {
                                $v['value'] = $type_real_field;
                            } elseif (!isset($v['value'])) {
                                $v['value'] = '';
                            }

                            $yform->setValueField('text', [$k_field, rex_i18n::msg('yform_values_defaults_name'), $v['value'], 'notice' => $v['notice']]);
                            $yform->setValidateField('empty', [$k_field, rex_i18n::msg('yform_validatenamenotempty')]);
                            $yform->setValidateField('preg_match', [$k_field, "/(([a-zA-Z])+([a-zA-Z0-9\_])*)/", rex_i18n::msg('yform_validatenamepregmatch')]);
                            $yform->setValidateField('customfunction', [$k_field, 'rex_yform_manager_checkField', ['table_name' => $table->getTableName()], rex_i18n::msg('yform_validatenamecheck')]);
                        }
                        break;

                    case 'no_db':
                        if (!isset($v['values'])) {
                            $v['values'] = [0, 1];
                        }
                        if (!isset($v['default']) || 1 != $v['default']) {
                            $v['default'] = 0;
                        }
                        $yform->setValueField('checkbox', ['name' => $k_field, 'label' => rex_i18n::msg('yform_donotsaveindb'), 'values' => 'no_db', 'default' => $v['default']]);
                        break;

                    case 'boolean':
                        if (!isset($v['values'])) {
                            $v['values'] = [0, 1];
                        }
                        if (!isset($v['default'])) {
                            $v['default'] = '';
                        }
                        $v['notice'] = ($v['notice'] ?? '');
                        $yform->setValueField('checkbox', ['name' => $k_field, 'label' => $v['label'], 'default' => $v['default'], 'notice' => $v['notice']]);
                        break;

                    case 'table':
                        $_tables = rex_yform_manager_table::getAll();
                        $_options = [];
                        if (isset($v['empty_option']) && $v['empty_option']) {
                            $_options[0] = '–=';
                        }
                        foreach ($_tables as $_table) {
                            $_options[$_table['table_name']] = str_replace('=', '-', rex_i18n::translate($_table['name']) . ' [' . $_table['table_name'] . ']') . '=' . $_table['table_name'];
                            $_options[$_table['table_name']] = str_replace(',', '.', $_options[$_table['table_name']]);
                        }
                        if (!isset($v['default'])) {
                            $v['default'] = '';
                        }
                        $yform->setValueField('choice', ['name' => $k_field, 'label' => $v['label'], 'choices' => implode(',', $_options), 'default' => $v['default']]);
                        break;

                    case 'select_name':
                        $_fields = [];
                        foreach ($table->getValueFields() as $_k => $_v) {
                            $_fields[] = $_k;
                        }
                        $v['notice'] = ($v['notice'] ?? '');
                        $yform->setValueField('choice', ['name' => $k_field, 'label' => $v['label'], 'choices' => implode(',', $_fields), 'notice' => $v['notice']]);
                        break;

                    case 'select_names':
                        $_fields = [];
                        foreach ($table->getValueFields() as $_k => $_v) {
                            $_fields[] = $_k;
                        }
                        $v['notice'] = ($v['notice'] ?? '');
                        $yform->setValueField('choice', ['name' => $k_field, 'label' => $v['label'], 'choices' => implode(',', $_fields), 'multiple' => true, 'notice' => $v['notice']]);
                        break;

                    case 'text':
                        // nur beim "Bezeichnungsfeld"
                        if ('label' == $k_field && '' != $type_real_field && !isset($v['value'])) {
                            $v['value'] = $type_real_field;
                        } elseif (!isset($v['value'])) {
                            $v['value'] = '';
                        }
                        $v['name'] = $k_field;
                        $yform->setValueField('text', $v);
                        break;

                    case 'textarea':
                    case 'choice':
                    default:
                        $v['name'] = $k_field;
                        $yform->setValueField($v['type'], $v);
                        break;
                }
            }

            if (isset($types[$type_id][$type_name]['validates']) && is_array($types[$type_id][$type_name]['validates'])) {
                foreach ($types[$type_id][$type_name]['validates'] as $v) {
                    $yform->setValidateField(key($v), current($v));
                }
            }

            $yform->setActionField('showtext', ['', '<p>' . rex_i18n::msg('yform_thankyouforentry') . '</p>']);
            $yform->setObjectparams('main_table', rex_yform_manager_field::table());

            switch ($func) {
                case 'edit':
                    $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_field_update'));
                    $yform->setHiddenField('field_id', $field_id);
                    $yform->setActionField('manage_db', [rex_yform_manager_field::table(), "id=$field_id"]);
                    $yform->setObjectparams('main_id', $field_id);
                    $yform->setObjectparams('main_where', "id=$field_id");
                    $sql = rex_sql::factory();
                    $sql->setQuery('SELECT * FROM ' . rex_yform_manager_field::table() . " WHERE id=$field_id");
                    foreach ($selectFields as $alias => $s_field) {
                        if ($alias != $s_field) {
                            if ((!$sql->hasValue($s_field) || null === $sql->getValue($s_field) || '' === $sql->getValue($s_field)) && $sql->hasValue($alias)) {
                                $sql->setValue($s_field, $sql->getValue($alias));
                            }
                            $yform->setValueField('hidden', [$alias, '']);
                        }
                    }
                    $yform->setObjectparams('sql_object', $sql);
                    $yform->setObjectparams('getdata', true);
                    break;
                case 'add':
                default:
                    $yform->setObjectparams('submit_btn_label', rex_i18n::msg('yform_field_add'));
                    $yform->setActionField('manage_db', [rex_yform_manager_field::table()]);
                    break;
            }

            if ('value' == $type_id) {
                $db_choices = $field->getDatabaseFieldTypes();
                $default = $field->getDatabaseFieldDefaultType();
                $yform->setValueField('choice', ['name' => 'db_type', 'label' => rex_i18n::msg('yform_field_db_type'), 'choices' => $db_choices, 'default' => $default]);

                if (!$field->isHiddenInListDisabled()) {
                    $yform->setValueField('checkbox', ['name' => 'list_hidden', 'label' => rex_i18n::msg('yform_hideinlist'), 'default' => '1']);
                }

                if (!$field->isSearchableDisabled()) {
                    $yform->setValueField('checkbox', ['name' => 'search', 'label' => rex_i18n::msg('yform_useassearchfieldalidatenamenotempty'), 'default' => '0']);
                }
            } elseif ('validate' == $type_id) {
                $yform->setValueField('hidden', ['list_hidden', 1]);
                $yform->setValueField('hidden', ['search', 0]);
            }

            $form = $yform->getForm();

            if ($yform->objparams['form_show']) {
                if ('add' == $func) {
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
                switch ($func) {
                    case 'edit':
                        rex_yform_manager_table_api::generateTableAndFields($table);
                        echo rex_view::success(rex_i18n::msg('yform_thankyouforupdate'));
                        break;
                    case 'add':
                    default:
                        rex_yform_manager_table_api::generateTableAndFields($table);
                        echo rex_view::success(rex_i18n::msg('yform_thankyouforentry'));
                        break;
                }
                $func = 'list';
            }
        }

        if ('delete' == $func) {
            if (!rex_csrf_token::factory($_csrf_key)->isValid()) {
                echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
            } else {
                $sf = rex_sql::factory();
                $sf->setDebug(self::$debug);
                $sf->setQuery('select * from ' . rex_yform_manager_field::table() . ' where table_name="' . $table->getTableName() . '" and id=' . $field_id);
                $sfa = $sf->getArray();
                if (1 == count($sfa)) {
                    $query = 'delete from ' . rex_yform_manager_field::table() . ' where table_name="' . $table->getTableName() . '" and id=' . $field_id;
                    $delsql = rex_sql::factory();
                    $delsql->setDebug(self::$debug);
                    $delsql->setQuery($query);
                    echo rex_view::success(rex_i18n::msg('yform_tablefielddeleted'));
                    rex_yform_manager_table_api::generateTableAndFields($table);
                } else {
                    echo rex_view::warning(rex_i18n::msg('yform_tablefieldnotfound'));
                }
                $func = 'list';
            }
        }

        // ********************************************* CREATE/UPDATE FIELDS
        if ('updatetable' == $func) {
            rex_yform_manager_table_api::generateTableAndFields($table);
            echo rex_view::info(rex_i18n::msg('yform_tablesupdated'));
            $func = 'list';
        }

        if ('updatetablewithdelete' == $func) {
            rex_yform_manager_table_api::generateTableAndFields($table, true);
            echo rex_view::info(rex_i18n::msg('yform_tablesupdated'));
            $func = 'list';
        }

        if ('show_form_notation' == $func) {
            $formbuilder_fields = $table->getFields();

            $notation_php = '';
            $notation_pipe = '';
            $notation_email = '';

            $notation_php_pre = [
                '$yform = new rex_yform();',
                '$yform->setObjectparams(\'form_name\', \'table-'.$table->getTableName().'\');',
                '$yform->setObjectparams(\'form_action\',rex_getUrl(\'REX_ARTICLE_ID\'));',
                '$yform->setObjectparams(\'form_ytemplate\', \'bootstrap\');',
                '$yform->setObjectparams(\'form_showformafterupdate\', 0);',
                '$yform->setObjectparams(\'real_field_names\', true);',
            ];

            $notation_php .= implode("\n", $notation_php_pre) . "\n";

            $notation_pipe_pre = [
                'objparams|form_name|table-'.$table->getTableName().'',
                'objparams|form_ytemplate|bootstrap',
                'objparams|form_showformafterupdate|0',
                'objparams|real_field_names|true',
            ];

            $notation_pipe .= implode("\n", $notation_pipe_pre) . "\n";

            foreach ($formbuilder_fields as $field) {
                $class = 'rex_yform_'.$field->getType().'_'.$field->getTypeName();

                $cl = new $class();
                $definitions = $cl->getDefinitions();

                $values = [];
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
                    ++$i;
                }
                $php_values = array_map(static function ($v) {
                    return str_replace("'", "\'", $v);
                }, $values);

                if ('value' == $field['type_id']) {
                    $notation_php .= "\n" . '$yform->setValueField(\'' . $field['type_name'] . '\', [\'' . rtrim(implode('\',\'', $php_values), '\',\'') . '\']);';
                    $notation_pipe .= "\n" . $field['type_name'] . '|' . rtrim(implode('|', $values), '|') . '|';
                    $notation_email .= "\n" . rex_i18n::translate($field['label']) . ': REX_YFORM_DATA[field="' . $field['name'] . '"]';
                } elseif ('validate' == $field['type_id']) {
                    $notation_php .= "\n" . '$yform->setValidateField(\'' . $field['type_name'] . '\', [\'' . rtrim(implode('\',\'', $php_values), '\',\'') . '\']);';
                    $notation_pipe .= "\n" . $field['type_id'] . '|' . $field['type_name'] . '|' . rtrim(implode('|', $values), '|') . '|';
                } elseif ('action' == $field['type_id']) {
                    $notation_php .= "\n" . '$yform->setActionField(\'' . $field['type_name'] . '\', [\'' . rtrim(implode('\',\'', $php_values), '\',\'') . '\']);';
                    $notation_pipe .= "\n" . $field['type_id'] . '|' . $field['type_name'] . '|' . rtrim(implode('|', $values), '|') . '|';
                }
            }

            $notation_php .= "\n\n"  . '$yform->setActionField(\'tpl2email\', [\'emailtemplate\', \'emailfieldname/email@example.org\']);';
            $notation_php .= "\n".'echo $yform->getForm();';

            $notation_pipe .= "\n\n"  . 'action|tpl2email|emailtemplate|emailfieldname/email@example.org';

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

        if ('list' == $func) {
            $show_list = true;
            $show_list = rex_extension::registerPoint(
                new rex_extension_point(
                    'YFORM_MANAGER_TABLE_FIELD_FUNC',
                    $show_list,
                    [
                        'table' => $table,
                        'link_vars' => $this->getLinkVars(),
                    ]
                )
            );

            if ($show_list) {
                $context = new rex_context(
                    $this->getLinkVars()
                );

                $fragment = new rex_fragment();
                $fragment->setVar('size', 'xs', false);
                $fragment->setVar('buttons', [
                    [
                        'label' => rex_i18n::msg('yform_data_view'),
                        'url' => 'index.php?page=yform/manager/data_edit&table_name=' . $table->getTableName(),
                        'attributes' => [
                            'class' => [
                                'btn-default',
                            ],
                        ],
                    ],
                ], false);
                $panel_options = '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_datas') . '</small> ' . $fragment->parse('core/buttons/button_group.php');

                $fragment = new rex_fragment();
                $fragment->setVar('size', 'xs', false);
                $fragment->setVar('buttons', [
                    [
                        'label' => rex_i18n::msg('yform_edit'),
                        'url' => 'index.php?page=yform/manager/table_edit&func=edit&table_id='.$table->getId(),
                        'attributes' => [
                            'class' => [
                                'btn-default',
                            ],
                        ],
                    ],
                    [
                        'label' => rex_i18n::msg('yform_manager_show_form_notation'),
                        'url' => $context->getUrl(['table_name' => $table->getTableName(), 'func' => 'show_form_notation']),
                        'attributes' => [
                            'class' => [
                                'btn-default',
                            ],
                        ],
                    ],
                    [
                        'label' => rex_i18n::msg('yform_updatetable'),
                        'url' => $context->getUrl(['table_name' => $table->getTableName(), 'func' => 'updatetable'] + rex_csrf_token::factory($_csrf_key)->getUrlParams()),
                        'attributes' => [
                            'class' => [
                                'btn-default',
                            ],
                        ],
                    ],
                    [
                        'label' => rex_i18n::msg('yform_updatetable').' '.rex_i18n::msg('yform_updatetable_with_delete'),
                        'url' => $context->getUrl(['table_name' => $table->getTableName(), 'func' => 'updatetablewithdelete'] + rex_csrf_token::factory($_csrf_key)->getUrlParams()),
                        'attributes' => [
                            'class' => [
                                'btn-default',
                            ],
                            'onclick' => [
                                'return confirm(\'' . rex_i18n::msg('yform_updatetable_with_delete_confirm') . '\')',
                            ],
                        ],
                    ],
                ], false);
                $panel_options .= '<small class="rex-panel-option-title">' . rex_i18n::msg('yform_table') . '</small> ' . $fragment->parse('core/buttons/button_group.php');

                $sql = 'select id, prio, type_id, type_name, name, label from ' . rex_yform_manager_field::table() . ' where table_name="' . $table->getTableName() . '" order by prio';
                $list = rex_list::factory($sql, 200);
                // $list->debug = 1;
                // $list->setColumnFormat('id', 'Id');

                $list->addTableAttribute('class', 'table-hover');

                $tdIcon = '<i class="rex-icon rex-icon-editmode"></i>';
                $thIcon = '<a class="rex-link-expanded" href="' . $list->getUrl(['table_name' => $table->getTableName(), 'func' => 'choosenadd']) . '" ' . rex::getAccesskey(rex_i18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
                $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
                $list->setColumnParams($thIcon, ['field_id' => '###id###', 'func' => 'edit', 'type_name' => '###type_name###', 'type_id' => '###type_id###']);

                foreach ($this->getLinkVars() as $k => $v) {
                    $list->addParam($k, $v);
                }
                $list->addParam('start', rex_request('start', 'int'));

                $list->addParam('table_name', $table->getTableName());

                $list->removeColumn('id');

                $list->setColumnLabel('prio', rex_i18n::msg('yform_manager_table_prio_short'));
                // $list->setColumnLayout('prio', ['<th class="rex-table-priority">###VALUE###</th>', '<td class="rex-table-priority" data-title="' . rex_i18n::msg('yform_manager_table_prio_short') . '">###VALUE###</td>']);
                $list->setColumnLayout('prio', ['<th>###VALUE###</th>', '###VALUE###']);
                $list->setColumnFormat('prio', 'custom', 'rex_yform_list_tools::listFormat');

                $list->setColumnLabel('type_id', rex_i18n::msg('yform_manager_type_id'));
                $list->setColumnLayout('type_id', ['<th>###VALUE###</th>', '###VALUE###']);
                $list->setColumnFormat('type_id', 'custom', 'rex_yform_list_tools::listFormat');

                $list->setColumnLabel('type_name', rex_i18n::msg('yform_manager_type_name'));
                $list->setColumnLayout('type_name', ['<th>###VALUE###</th>', '###VALUE###']);
                $list->setColumnFormat('type_name', 'custom', 'rex_yform_list_tools::listFormat');

                $list->setColumnLabel('name', rex_i18n::msg('yform_values_defaults_name'));
                $list->setColumnLayout('name', ['<th>###VALUE###</th>', '###VALUE###']); // ###VALUE###
                $list->setColumnFormat('name', 'custom', 'rex_yform_list_tools::listFormat');

                $list->setColumnLabel('label', rex_i18n::msg('yform_values_defaults_label'));
                $list->setColumnLayout('label', ['<th>###VALUE###</th>', '###VALUE###']); // ###VALUE###
                $list->setColumnFormat('label', 'custom', 'rex_yform_list_tools::listFormat');

                $list->addColumn(rex_i18n::msg('yform_function'), '<i class="rex-icon rex-icon-editmode"></i> ' . rex_i18n::msg('yform_edit'));
                $list->setColumnParams(rex_i18n::msg('yform_function'), ['field_id' => '###id###', 'func' => 'edit', 'type_name' => '###type_name###', 'type_id' => '###type_id###']);
                $list->setColumnLayout(rex_i18n::msg('yform_function'), ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '###VALUE###']);
                $list->setColumnFormat(rex_i18n::msg('yform_function'), 'custom', 'rex_yform_list_tools::editFormat');

                $list->addColumn(rex_i18n::msg('yform_delete'), '<i class="rex-icon rex-icon-delete"></i> ' . rex_i18n::msg('yform_delete'));
                $list->setColumnParams(rex_i18n::msg('yform_delete'), ['field_id' => '###id###', 'func' => 'delete'] + rex_csrf_token::factory($_csrf_key)->getUrlParams());
                $list->setColumnLayout(rex_i18n::msg('yform_delete'), ['', '###VALUE###']);
                $list->setColumnFormat(rex_i18n::msg('yform_delete'), 'custom', 'rex_yform_list_tools::deleteFormat');
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
            ++$key;
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

    public static function checkField($l, $v, $p)
    {
        $q = 'select * from ' . rex_yform_manager_field::table() . ' where table_name="' . $p['table_name'] . '" and type_id="value" and ' . $l . '="' . $v . '" LIMIT 1';
        $c = rex_sql::factory();
        $c->setDebug(self::$debug);
        $c->setQuery($q);
        if ($c->getRows() > 0) {
            return true;
        }
        return false;
    }

    public function createTable($mifix, $data_table, $params = [], $debug = false)
    {
        // Tabelle erstellen wenn noch nicht vorhanden
        $c = rex_sql::factory();
        $c->setDebug($debug);
        $c->setQuery('CREATE TABLE IF NOT EXISTS `' . $data_table . '` ( `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        // Tabellenset in die Basics einbauen, wenn noch nicht vorhanden
        $c = rex_sql::factory();
        $c->setDebug($debug);
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
}
