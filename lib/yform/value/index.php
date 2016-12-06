<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_index extends rex_yform_value_abstract
{

    function postFormAction()
    {
        if ($this->params['send'] != 1) {
            return;
        }

        $index_labels = explode(',', $this->getElement('names'));

        $value = '';
        $relations = [];

        foreach ($index_labels as $name) {
            $name = trim($name);

            if (isset($this->params['value_pool']['sql'][$name])) {
                $value .= ' '.$this->params['value_pool']['sql'][$name];
                continue;
            }

            $name = explode('.', $name);
            if (count($name) > 1) {
                $this->addRelation($relations, $name);
            }
        }

        if ($relations) {
            foreach ($this->getRelationValues($relations) as $v) {
                $value .= ' '.$v;
            }
        }

        $fnc = trim($this->getElement('function'));
        if (function_exists($fnc)) {
            $value = call_user_func($fnc, $value);
        }

        $this->setValue($value);

        $this->params['value_pool']['email'][$this->getName()] = $value;
        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $value;
        }
    }

    function getDescription()
    {
        return 'index|label|name|name1,name2,name3|[no_db]|[func/md5/sha]';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'index',
            'values' => array(
                'name'     => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'    => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label") ),
                'names'    => array( 'type' => 'text',  'label' => rex_i18n::msg("yform_values_index_names"), 'notice' => rex_i18n::msg("yform_values_index_names_notice")),
                'no_db'    => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'function' => array( 'type' => 'text',  'label' => rex_i18n::msg("yform_values_index_function"), 'notice' => rex_i18n::msg("yform_values_index_function_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_index_description"),
            'is_hiddeninlist' => true,
            'dbtype' => 'text',
            'multi_edit' => false,
        );

    }

    private function addRelation(array &$relations, array $names)
    {
        if (!$names) {
            return;
        }

        $name = array_shift($names);

        if (!isset($relations[$name])) {
            $relations[$name] = [];
        }

        $this->addRelation($relations[$name], $names);
    }

    private function getRelationValues(array $relations)
    {
        $table = rex_yform_manager_table::get($this->params['main_table']);
        $sql = rex_sql::factory();
        $sql->setDebug($this->params['debug']);

        foreach ($relations as $name => $sub) {
            $relation = $table->getRelation($name);

            if (!$relation || !in_array($relation->getElement('type'), [0, 2]) || empty($this->params['value_pool']['sql'][$name])) {
                continue;
            }

            $fields = [];
            $joins = [];
            $maxIndex = 0;

            $addFieldsAndJoins = function (array $columns, rex_yform_manager_table $table, $index) use (&$addFieldsAndJoins, &$fields, &$joins, &$maxIndex, $sql) {
                foreach ($columns as $name => $sub) {
                    if (!$sub) {
                        $fields[] = 't'.$index.'.'.$sql->escapeIdentifier($name);
                        continue;
                    }

                    $relation = $table->getRelation($name);

                    if (!$relation || !in_array($relation->getElement('type'), [0, 2])) {
                        continue;
                    }

                    $nextIndex = ++$maxIndex;
                    $joins[] = sprintf(
                        'LEFT JOIN %s t%d ON t%2$d.id = t%d.%s',
                        $sql->escapeIdentifier($relation['table']),
                        $nextIndex,
                        $index,
                        $sql->escapeIdentifier($name)
                    );

                    $addFieldsAndJoins($sub, rex_yform_manager_table::get($relation['table']), $nextIndex);
                }
            };

            $addFieldsAndJoins($sub, rex_yform_manager_table::get($relation['table']), $maxIndex);

            if (!$fields) {
                continue;
            }

            $data = $sql->getArray(sprintf(
                'SELECT %s FROM %s t0 %s WHERE t0.id = %d LIMIT 1',
                implode(', ', $fields),
                $sql->escapeIdentifier($relation['table']),
                implode(' ', $joins),
                $this->params['value_pool']['sql'][$name]
            ));

            if (!isset($data[0])) {
                continue;
            }

            foreach ($data[0] as $value) {
                yield $value;
            }
        }
    }
}
