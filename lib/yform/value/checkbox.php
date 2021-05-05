<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_checkbox extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if (1 == $this->params['send'] && 1 != $this->getValue()) {
            $this->setValue(0);
        } elseif ('' != $this->getValue()) {
            $this->setValue((1 != $this->getValue()) ? '0' : '1');
        } else {
            $this->setValue($this->getElement('default'));
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if (!$this->isEditable()) {
                $attributes = empty($this->getElement('attributes')) ? [] : json_decode($this->getElement('attributes'), true);
                $attributes['disabled'] = 'disabled';
                $this->setElement('attributes', json_encode($attributes));
                $this->params['form_output'][$this->getId()] = $this->parse(['value.checkbox-view.tpl.php', 'value.view.tpl.php', 'value.checkbox.tpl.php'], ['value' => $this->getValue()]);
            } else {
                $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox.tpl.php', ['value' => $this->getValue()]);
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'checkbox|name|label|default clicked (0/1)|[no_db]|[notice]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'checkbox',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'checkbox', 'label' => rex_i18n::msg('yform_values_checkbox_default'), 'default' => 0],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'), 'default' => 0],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'output_values' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_status_output_values'), 'notice' => rex_i18n::msg('yform_values_status_output_values_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_checkbox_description'),
            'db_type' => ['tinyint(1)'],
            'famous' => true,
            'hooks' => [
                'preDefault' => static function (rex_yform_manager_field $field) {
                    return (1 == $field->getElement('default')) ? '1' : '0';
                },
            ],
        ];
    }

    public static function getSearchField($params)
    {
        $v = 1;
        $w = 0;

        $options = [];
        $options[$v] = rex_i18n::rawMsg('yform_values_checked');
        $options[$w] = rex_i18n::rawMsg('yform_values_not_checked');
        $options[''] = '---';

        $params['searchForm']->setValueField('choice', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'choices' => $options,
        ]);
    }

    public static function getSearchFilter($params)
    {
        $value = $params['value'];
        $field = $params['field']->getName();

        $sql = rex_sql::factory();

        return ' ' . $sql->escapeIdentifier($field) . ' =  ' . $sql->escape($value) . '';
    }

    public static function getListValue($params)
    {
        $values = $params['params']['field']['output_values'] ?? '0,1';
        $values = explode(',', $values);
        if (2 != count($values)) {
            $values = [0, 1];
        }

        if ('1' === $params['subject']) {
            return $values[1];
        }
        if ('0' === $params['subject']) {
            return $values[0];
        }

        return $params['subject'];
    }
}
