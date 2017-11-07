<?php


/*
 * TODO:
 *
 * Suche nach id noch anpassen
 * Suche mit diversen krietrien ermöglich
 * z.B. selectfeld (genaue suche. like suche. etc.. siehe phpmyadmin)
 * dadurch auch rex_yform_searchvars mit unterarray erlauben
 * getSearchVars entsprechend umbauen
 *
 *
 * text/textarea / s: text / einfach text suche: suchtext / auch mit * -> % und (empty) oder !(empty)
  // o: select, sqlselect / s: multiselect mit checkbox: ist vorhanden, oder ist nicht vorhanden find in set
  // o: relation / s: lookup mit übernehmen.
  andere felder fehlen noch..
  select und co
 */

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

    public function setLinkVars($vars)
    {
        $this->linkVars = array_merge($this->linkVars, $vars);
    }

    public function setScriptPath($scriptpath)
    {
        $this->scriptPath = $scriptpath;
    }

    public function getForm()
    {
        if (!$this->table->isSearchable()) {
            return '';
        }

        $yform = new rex_yform_manager_searchform();
        $yform->setObjectparams('form_showformafterupdate', 1);
        $yform->setObjectparams('real_field_names', true);
        $yform->setObjectparams('form_action', $this->scriptPath);
        $yform->setObjectparams('form_method', 'get');
        $yform->setObjectparams('form_name', 'yform-manager-search-form');
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

        return $yform->getForm();
    }

    public function getSearchVars()
    {
        $rex_yform_searchvars = rex_request('rex_yform_searchvars', 'array');
        unset($rex_yform_searchvars['send']);
        unset($rex_yform_searchvars['yform_search_submit']);
        foreach ($rex_yform_searchvars as $k => $v) {
            if ($v == '') {
                unset($rex_yform_searchvars[$k]);
            }
        }
        return ['rex_yform_searchvars' => $rex_yform_searchvars];
    }

    public function getQueryFilterArray()
    {
        if (!$this->table->isSearchable()) {
            return [];
        }

        $queryFilter = [];
        $vars = $this->getSearchVars();

        foreach ($this->fields as $field) {
            if (array_key_exists($field->getName(), $vars['rex_yform_searchvars']) && $field->getType() == 'value' && $field->isSearchable()) {
                //                rex_yform::includeClass($field->getType(), $field->getTypeName());
                if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchFilter')) {
                    $qf = call_user_func('rex_yform_value_' . $field->getTypeName() . '::getSearchFilter',
                        [
                            'field' => $field,
                            'fields' => $this->fields,
                            'value' => $vars['rex_yform_searchvars'][$field->getName()],
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
