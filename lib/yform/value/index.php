<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_index extends rex_yform_value_abstract
{
    public function postFormAction(): void
    {
        if (1 != $this->params['send']) {
            return;
        }

        $value = $this->getValue() ?? '';

        if ('' != $this->getElement('names')) {
            $index_labels = explode(',', $this->getElement('names'));

            $value = '';
            $relations = [];

            foreach ($index_labels as $name) {
                $name = trim($name);

                if ('id' == $name && $this->params['main_id'] > 0) {
                    $value .= $this->params['main_id'];
                }

                if (isset($this->params['value_pool']['sql'][$name])) {
                    $value .= ' ' . $this->params['value_pool']['sql'][$name];
                    continue;
                }

                $name = explode('.', $name);
                if (count($name) > 1) {
                    $this->addRelation($relations, $name);
                }
            }

            if ($relations) {
                foreach ($this->getRelationValues($relations) as $v) {
                    $value .= ' ' . $v;
                }
            }

            $value .= $this->getElement('salt');

            $fnc = trim($this->getElement('function'));
            if ('' != $fnc) {
                if (1 == $this->getElement('add_this_param')) {
                    $value = call_user_func($fnc, $value, $this) ?? '';
                } else {
                    $value = call_user_func($fnc, $value) ?? '';
                }
            }
        }

        $this->setValue(trim($value));

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'index|name|label|name1,name2,name3|[no_db]|[func/md5/sha]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'index',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'names' => ['type' => 'text',  'label' => rex_i18n::msg('yform_values_index_names'), 'notice' => rex_i18n::msg('yform_values_index_names_notice')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'function' => ['type' => 'text',  'label' => rex_i18n::msg('yform_values_index_function'), 'notice' => rex_i18n::msg('yform_values_index_function_notice')],
                'add_this_param' => ['type' => 'checkbox',   'label' => rex_i18n::msg('yform_values_index_add_this_param'),  'default' => 0],
                'salt' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_hashvalue_salt')],
            ],
            'description' => rex_i18n::msg('yform_values_index_description'),
            'db_type' => ['mediumtext', 'text', 'varchar(191)'], // text (65kb) mediumtext (16Mb)
            'multi_edit' => false,
        ];
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
        $sql = rex_sql::factory($table->getDatabaseId());
        $sql->setDebug($this->params['debug']);

        foreach ($relations as $name => $sub) {
            $relation = $table->getRelation($name);

            if (!$relation || (4 != $relation->getElement('type') && 5 != $relation->getElement('type')) && !$relation->getElement('relation_table') && empty($this->params['value_pool']['sql'][$name])) {
                continue;
            }

            $fields = [];
            $joins = [];
            $maxIndex = 0;

            $addJoin = static function ($table, $index, $name, $type) use ($sql, &$joins, &$maxIndex) {
                switch ($type) {
                    case 0:
                    case 2:
                        $format = 'LEFT JOIN %s t%d ON t%2$d.id = t%d.%s';
                        break;
                    case 1:
                    case 3:
                        $format = 'LEFT JOIN %s t%d ON FIND_IN_SET(t%2$d.id, t%d.%s)';
                        break;
                    case 4:
                    case 5:
                        $format = 'LEFT JOIN %s t%d ON t%2$d.%4$s = t%d.id';
                        break;
                    default:
                        throw new LogicException(sprintf('Unknown relation type "%s"', $type));
                }

                $nextIndex = ++$maxIndex;
                $joins[] = sprintf(
                    $format,
                    $sql->escapeIdentifier($table),
                    $nextIndex,
                    $index,
                    $sql->escapeIdentifier($name),
                );

                return $nextIndex;
            };

            $addFieldsAndJoins = static function (array $columns, rex_yform_manager_field $relation, $index) use (&$addFieldsAndJoins, $addJoin, &$fields, $sql) {
                $table = rex_yform_manager_table::get($relation->getElement('table'));

                $fieldFormat = 't%d.%s';
                if ($relation->getElement('relation_table') || in_array($relation->getElement('type'), [1, 3, 4, 5])) {
                    $fieldFormat = 'GROUP_CONCAT(' . $fieldFormat . ' SEPARATOR " ")';
                }

                foreach ($columns as $name => $sub) {
                    if (!$sub) {
                        $fields[] = sprintf($fieldFormat, $index, $sql->escapeIdentifier($name));
                        continue;
                    }

                    $relation = $table->getRelation($name);

                    if (!$relation) {
                        continue;
                    }

                    $currentIndex = $index;

                    if ($relation->getElement('relation_table')) {
                        try {
                            $columns = $table->getRelationTableColumns($name);
                        } catch (RuntimeException $e) {
                            continue;
                        }

                        $relationTable = rex_yform_manager_table::get($relation->getElement('relation_table'));
                        $name = $columns['target'];
                        $relation = $relationTable->getValueField($name);
                        $relation['relation_table'] = true;

                        $currentIndex = $addJoin($relationTable->getTableName(), $currentIndex, $columns['source'], 4);
                    }

                    if (4 == $relation->getElement('type') || 5 == $relation->getElement('type')) {
                        $name = $relation->getElement('field');
                    }

                    $currentIndex = $addJoin($relation->getElement('table'), $currentIndex, $name, $relation->getElement('type'));

                    $addFieldsAndJoins($sub, $relation, $currentIndex);
                }
            };

            $fromTable = $relation->getElement('table');
            $type = $relation->getElement('type');

            $columns = [];
            if ($relation->getElement('relation_table')) {
                try {
                    $columns = $table->getRelationTableColumns($name);
                } catch (RuntimeException $e) {
                    continue;
                }

                $fromTable = $relation->getElement('relation_table');
                $relationTable = rex_yform_manager_table::get($fromTable);
                $relation = $relationTable->getValueField($columns['target']);
                $relation['relation_table'] = true;
                $type = 'relation_table';

                $maxIndex = $addJoin($relation->getElement('table'), $maxIndex, $columns['target'], 0);
            }

            $addFieldsAndJoins($sub, $relation, $maxIndex);

            if (!$fields) {
                continue;
            }

            $query = sprintf(
                'SELECT %s FROM %s t0 %s WHERE ',
                implode(', ', $fields),
                $sql->escapeIdentifier($fromTable),
                implode(' ', $joins),
            );

            switch ($type) {
                case 'relation_table':
                    $query .= sprintf('t0.%s = %d', $sql->escapeIdentifier($columns['source']), $this->params['main_id']);
                    break;
                case 0:
                case 2:
                    $query .= sprintf('t0.id = %d', $this->params['value_pool']['sql'][$name]);
                    break;
                case 1:
                case 3:
                    $query .= sprintf('FIND_IN_SET(t0.id, %s)', $sql->escape($this->params['value_pool']['sql'][$name]));
                    break;
                case 4:
                case 5:
                    $query .= sprintf('t0.%s = %d', $sql->escapeIdentifier($relation->getElement('field')), $this->params['main_id']);
                    break;
                default:
                    throw new LogicException(sprintf('Unknown relation type "%s"', $type));
            }

            $data = $sql->getArray($query . ' LIMIT 1');

            if (!isset($data[0])) {
                continue;
            }

            foreach ($data[0] as $value) {
                yield $value;
            }
        }
    }
}
