<?php

class rex_yform_manager_search
{
    private $linkVars = [];
    private $scriptPath = '';

    /** @var rex_yform_manager_table */
    protected $table = null;
    protected $fields = null;

    public function __construct(rex_yform_manager_table $table)
    {
        $this->table = $table;

        $id_field = [new rex_yform_manager_field([
            'id' => 0,
            'table_name' => $this->table->getTableName(),
            'type_id' => 'value',
            'type_name' => 'integer',
            'search' => 1,
            'label' => 'ID',
            'name' => 'id',
        ])];

        $this->fields = array_merge($id_field, $this->table->getFields());

        $this->setScriptPath($_SERVER['PHP_SELF']);
    }

    public function setLinkVar($k, $v)
    {
        $this->linkVars[$k] = $v;
    }

    public function setSearchLinkVars(array $vars)
    {
        $this->linkVars = array_merge($this->linkVars, $vars);
    }

    public function setScriptPath($scriptpath)
    {
        $this->scriptPath = $scriptpath;
    }

    public function getYForm()
    {
        $yform = new rex_yform();
        $yform->setObjectparams('form_name', 'rex_yform_searchvars-'.$this->table->getTableName());
        $yform->setObjectparams('form_showformafterupdate', 1);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_action', $this->scriptPath);
        $yform->setObjectparams('form_method', 'get');
        $yform->setObjectparams('main_table', $this->table->getTableName());

        foreach ($this->linkVars as $k => $v) {
            $yform->setHiddenField($k, $v);
        }

        foreach ($this->fields as $field) {
            if ($field->getTypeName() && $field->getType() == 'value' && $field->isSearchable()) {
                if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchField')) {
                    call_user_func('rex_yform_value_' . $field->getTypeName() . '::getSearchField', [
                        'searchForm' => $yform,
                        'searchObject' => $this,
                        'field' => $field,
                        'fields' => $this->fields,
                    ]);
                }
            }
        }
        $yform->setValueField('submit', ['yform_search_submit', rex_i18n::msg('yform_search')]);

        return $yform;
    }

    public function getForm()
    {
        if (!$this->table->isSearchable()) {
            return '';
        }
        $yform = $this->getYForm();
        return $yform->getForm();
    }

    public function getSearchVars()
    {
        $yform = $this->getYForm();
        $yform->getForm();
        $fieldValues = $yform->getFieldValue();

        $return = [];
        foreach ($yform->objparams['values'] as $i => $valueObject) {
            if (isset($fieldValues[$i]) && $fieldValues[$i] != '') {
                $return[$yform->getFieldName($valueObject->getLabel(), [$i])] = $fieldValues[$i];
            }
        }
        return $return;
    }

    public function getQueryFilterArray()
    {
        if (!$this->table->isSearchable()) {
            return [];
        }

        $queryFilter = [];

        $yform = $this->getYForm();
        $yform->getForm();
        $fieldValues = $yform->getFieldValue();

        $vars = [];
        foreach ($yform->objparams['values'] as $i => $valueObject) {
            if (isset($fieldValues[$i]) && $fieldValues[$i] != '') {
                $vars[$valueObject->getName()] = $fieldValues[$i];
            }
        }
        if (isset($vars['yform_search_submit'])) {
            unset($vars['yform_search_submit']);
        }

        foreach ($this->fields as $field) {
            if (array_key_exists($field->getName(), $vars) && $field->getType() == 'value' && $field->isSearchable()) {
                if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchFilter')) {
                    $qf = call_user_func('rex_yform_value_' . $field->getTypeName() . '::getSearchFilter',
                        [
                            'field' => $field,
                            'fields' => $this->fields,
                            'value' => $vars[$field->getName()],
                        ]
                    );
                    if ($qf != '') {
                        $queryFilter[] = $qf;
                    }
                }
            }
        }
        return $queryFilter;
    }
}
