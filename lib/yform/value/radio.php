<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_radio extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $options = $this->getArrayFromString($this->getElement('options'));

        if (!array_key_exists($this->getValue(), $options)) {
            $this->setValue('');
            $default = $this->getElement('default');
            if ($default && array_key_exists($default, $options)) {
                $this->setValue($default);
            }
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.radio.tpl.php', compact('options'));
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'radio|name|label|Frau=w,Herr=m|[defaultwert]|[attributes]|[notice]|[no_db]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'radio',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'options' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_radio_options')],
                'default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_radio_default')],
                'attributes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),          'default' => 0],
            ],
            'description' => rex_i18n::msg('yform_values_radio_description'),
            'db_type' => ['text'],
            'deprecated' => rex_i18n::msg('yform_values_deprecated_radio'),
        ];
    }

    public static function getListValue($params)
    {
        $return = [];

        $new_select = new self();
        $values = $new_select->getArrayFromString($params['params']['field']['options']);

        foreach (explode(',', $params['value']) as $k) {
            if (isset($values[$k])) {
                $return[] = rex_i18n::translate($values[$k]);
            }
        }

        return implode('<br />', $return);
    }

    public static function getSearchField($params)
    {
        $options = [];
        $options['(empty)'] = '(empty)';
        $options['!(empty)'] = '!(empty)';

        $new_select = new self();
        $options += $new_select->getArrayFromString($params['field']['options']);

        $params['searchForm']->setValueField('select', [
            'name' => $params['field']->getName(),
            'label' => $params['field']->getLabel(),
            'options' => $options,
            'multiple' => 1,
            'size' => 5,
        ]
        );
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();

        $field = $params['field']->getName();
        $values = (array) $params['value'];

        $where = [];
        foreach ($values as $value) {
            switch ($value) {
                case '(empty)':
                    $where[] = ' ' . $sql->escapeIdentifier($field) . ' = ""';
                    break;
                case '!(empty)':
                    $where[] = ' ' . $sql->escapeIdentifier($field) . ' != ""';
                    break;
                default:
                    $where[] = ' ( FIND_IN_SET( ' . $sql->escape($value) . ', ' . $sql->escapeIdentifier($field) . ') )';
                    break;
            }
        }

        if (count($where) > 0) {
            return ' ( ' . implode(' or ', $where) . ' )';
        }
    }

    public function isDeprecated()
    {
        return true;
    }
}
