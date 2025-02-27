<?php

namespace Yakamara\YForm\Manager;

use Exception;
use rex_i18n;
use Yakamara\YForm\Manager\Table\Table;
use Yakamara\YForm\YForm;

use function array_key_exists;
use function call_user_func;

class Search
{
    private array $linkVars = [];
    private string $scriptPath = '';

    /** @var Table */
    protected $table;
    protected array $fields;

    public function __construct(Table $table)
    {
        $this->table = $table;

        $id_field = [new Field([
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

    public function setLinkVar(string $k, mixed $v): self
    {
        $this->linkVars[$k] = $v;
        return $this;
    }

    public function setSearchLinkVars(array $vars): self
    {
        $this->linkVars = array_merge($this->linkVars, $vars);
        return $this;
    }

    public function setScriptPath(string $scriptpath): self
    {
        $this->scriptPath = $scriptpath;
        return $this;
    }

    public function getYForm(): YForm
    {
        $yform = new YForm();
        $yform->setObjectparams('form_name', 'rex_yform_searchvars-' . $this->table->getTableName());
        $yform->setObjectparams('form_showformafterupdate', 1);
        $yform->setObjectparams('csrf_protection', false);
        $yform->setObjectparams('form_action', $this->scriptPath);
        $yform->setObjectparams('form_method', 'get');
        $yform->setObjectparams('main_table', $this->table->getTableName());

        foreach ($this->linkVars as $k => $v) {
            $yform->setHiddenField($k, $v);
        }

        foreach ($this->fields as $field) {
            if ($field->getTypeName() && 'value' == $field->getType() && $field->isSearchable()) {
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
            if (isset($fieldValues[$i]) && '' != $fieldValues[$i]) {
                $return[$yform->getFieldName($valueObject->getLabel(), [$i])] = $fieldValues[$i];
            }
        }
        return $return;
    }

    public function getQueryFilter($query)
    {
        if (!$this->table->isSearchable()) {
            return $query;
        }

        $yform = $this->getYForm();
        $yform->getForm();
        $fieldValues = $yform->getFieldValue();

        $vars = [];
        foreach ($yform->objparams['values'] as $i => $valueObject) {
            if (isset($fieldValues[$i]) && '' != $fieldValues[$i]) {
                $vars[$valueObject->getName()] = $fieldValues[$i];
            }
        }
        if (isset($vars['yform_search_submit'])) {
            unset($vars['yform_search_submit']);
        }

        foreach ($this->fields as $field) {
            if (array_key_exists($field->getName(), $vars) && 'value' == $field->getType() && $field->isSearchable()) {
                if (method_exists('rex_yform_value_' . $field->getTypeName(), 'getSearchFilter')) {
                    $query = call_user_func('rex_yform_value_' . $field->getTypeName() . '::getSearchFilter',
                        [
                            'field' => $field,
                            'fields' => $this->fields,
                            'value' => $vars[$field->getName()],
                            'query' => $query,
                        ],
                    );
                    if (Query::class != $query::class) {
                        throw new Exception('getSearchFilter in rex_yform_value_' . $field->getTypeName() . ' does not return a Yakamara\YForm\Manager\Query');
                    }
                }
            }
        }
        return $query;
    }
}
