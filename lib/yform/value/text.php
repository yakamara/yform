<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_text extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $this->setValue((string) $this->getValue());

        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput() || !$this->isViewable()) {
        }

        $templateParams = [];
        $templateParams['prepend'] = $this->getElement('prepend');
        $templateParams['append'] = $this->getElement('append');
        if (!$this->isEditable()) {
            $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
            $attributes['readonly'] = 'readonly';
            $this->setElement('attributes', json_encode($attributes));
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text-view.tpl.php', 'value.view.tpl.php', 'value.text.tpl.php'], $templateParams);
        } else {
            $this->params['form_output'][$this->getId()] = $this->parse('value.text.tpl.php', $templateParams);
        }
    }

    public function getDescription(): string
    {
        return 'text|name|label|defaultwert|[no_db]|[attributes]|[notice]|[prepend]|[append]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'text',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_text_default')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'prepend' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_prepend')],
                'append' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_append')],
            ],
            'description' => rex_i18n::msg('yform_values_text_description'),
            'db_type' => ['varchar(191)', 'text'],
            'famous' => true,
            'hooks' => [
                'preDefault' => static function (rex_yform_manager_field $field) {
                    return $field->getElement('default');
                },
            ],
        ];
    }

    public static function getSearchField($params)
    {
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => rex_i18n::msg('yform_search_defaults_wildcard_notice')]);
    }

    public static function getSearchFilter($params)
    {
        $value = trim($params['value']);
        /** @var rex_yform_manager_query $query */
        $query = $params['query'];
        $field = $query->getTableAlias() . '.' . $params['field']->getName();

        if ('(empty)' == $value) {
            return $query->whereNested(static function (rex_yform_manager_query $query) use ($field) {
                $query
                    ->where($field, '')
                    ->where($field, null)
                ;
            }, 'OR');
        }
        if ('!(empty)' == $value) {
            return $query->whereNested(static function (rex_yform_manager_query $query) use ($field) {
                $query
                    ->where($field, '', '<>')
                    ->where($field, null, '<>')
                ;
            }, 'OR');
        }

        $pos = strpos($value, '*');
        if (false !== $pos) {
            $value = str_replace('%', '\%', $value);
            $value = str_replace('*', '%', $value);
            return $query->where($field, $value, 'LIKE');
        }
        return $query->where($field, $value);
    }

    public static function getListValue($params)
    {
        $value = (string) $params['subject'];
        $length = mb_strlen($value);
        $title = $value;
        if ($length > 40) {
            $value = mb_substr($value, 0, 20).' ... '.mb_substr($value, -20);
        }
        return '<span title="'.rex_escape($title).'">'.rex_escape($value).'</span>';
    }
}
