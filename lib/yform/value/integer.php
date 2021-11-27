<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_integer extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        if ('' === $this->getValue()) {
            $this->setValue(null);
        } else {
            $this->setValue((int) $this->getValue());
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.integer-view.tpl.php', 'value.view.tpl.php'], ['prepend' => $this->getElement('unit')]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse(['value.integer.tpl.php', 'value.text.tpl.php'], ['prepend' => $this->getElement('unit')]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'integer|name|label|defaultwert|[no_db]|[notice]|[unit]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'integer',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_integer_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'unit' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_unit')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_integer_description'),
            'db_type' => ['int', 'bigint'],
            'db_null' => true,
        ];
    }

    public static function getListValue($params)
    {
        return (!empty($params['params']['field']['unit']) && '' != $params['subject']) ? $params['params']['field']['unit'].' '.$params['subject'] : $params['subject'];
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => rex_i18n::msg('yform_search_integer_notice'), 'prepend' => $params['field']->getElement('unit')]);
    }

    public static function getSearchFilter($params)
    {
        $value = $params['value'];
        /** @var rex_yform_manager_query $query */
        $query = $params['query'];
        $field = $params['field']->getName();

        if ('(empty)' == $value) {
            return $query->whereNested(function (rex_yform_manager_query $query) use ($field) {
                $query
                    ->where($field, '')
                    ->where($field, NULL)
                ;
            }, 'OR');
        }
        if ('!(empty)' == $value) {
            return $query->whereNested(function (rex_yform_manager_query $query) use ($field) {
                $query
                    ->where($field, '', '<>')
                    ->where($field, NULL, '<>')
                ;
            }, 'OR');
        }

        if (preg_match('/^\s*(-?\d+)\s*\.\.\s*(-?\d+)\s*$/', $value, $match)) {
            $match[1] = (int) $match[1];
            $match[2] = (int) $match[2];
            return $query->whereBetween($field, $match[1], $match[2]);
        }
        preg_match('/^\s*(<|<=|>|>=|<>|!=)?\s*(.*)$/', $value, $match);
        $comparator = $match[1] ?: '=';
        $value = (int) $match[2];

        return $query->where($field, $value, $comparator);
    }
}
