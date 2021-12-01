<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_unique extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        $cd = rex_sql::factory();

        $table = $this->params['main_table'];
        if ('' != $this->getElement('table')) {
            $table = $this->getElement('table');
        }

        $fields = explode(',', $this->getElement('name'));
        $qfields = [];

        foreach ($this->getObjects() as $Object) {
            if ($this->isObject($Object) && in_array($Object->getName(), $fields)) {
                $value = $Object->getValue();
                // select array ? (special case)
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                if (1 == count($fields) && 1 == $this->getElement('empty_option')) {
                    $qfields[$Object->getId()] = $cd->escapeIdentifier($Object->getName()) . '=' . $cd->escape($value) . '  AND ' . $cd->escape($value) . '!=""';
                } else {
                    $qfields[$Object->getId()] = $cd->escapeIdentifier($Object->getName()) . '=' . $cd->escape($value) . '';
                }
            }
        }

        // all fields available ?
        if (count($qfields) != count($fields)) {
            $this->params['warning'][] = $this->getElement('message');
            $this->params['warning_messages'][] = $this->getElement('message');
            return;
        }

        $sql = 'select * from ' . $table . ' WHERE ' . implode(' AND ', $qfields) . ' LIMIT 1';
        if ('' != $this->params['main_where']) {
            $sql = 'select * from ' . $table . ' WHERE ' . implode(' AND ', $qfields) . ' AND !(' . $this->params['main_where'] . ') LIMIT 1';
        }

        $cd->setQuery($sql);
        if ($cd->getRows() > 0) {
            foreach ($qfields as $qfield_id => $qfield_name) {
                $this->params['warning'][$qfield_id] = $this->params['error_class'];
                $this->params['warning_messages'][$qfield_id] = $this->getElement('message');
            }
        }
    }

    public function getDescription(): string
    {
        return 'validate|unique|name[,name2]|warning_message|[table]|emptyoption[1/0]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'validate',
            'name' => 'unique',
            'values' => [
                'name' => ['type' => 'text',       'label' => rex_i18n::msg('yform_validate_unique_name'), 'notice' => rex_i18n::msg('yform_validate_unique_notice')],
                'message' => ['type' => 'text',      'label' => rex_i18n::msg('yform_validate_unique_message')],
                'table' => ['type' => 'text',      'label' => rex_i18n::msg('yform_validate_unique_table')],
                'empty_option' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_validate_unique_empty_option')],
            ],
            'description' => rex_i18n::msg('yform_validate_unique_description'),
            'multi_edit' => false,
        ];
    }
}
