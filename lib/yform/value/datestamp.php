<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datestamp extends rex_yform_value_abstract
{
    public function preValidateAction()
    {
        $format = 'Y-m-d H:i:s';
        $default_value = date($format);
        $value = $this->getValue();
        $this->showValue = self::datestamp_getValueByFormat($value, $this->getElement('format'));

        if ($this->getElement('only_empty') == 2) {
            // wird nicht gesetzt
        } elseif ($this->getElement('only_empty') != 1) { // -> == 0
            // wird immer neu gesetzt
            $value = $default_value;
        } elseif ($this->getValue() != '' && $this->getValue() != '0000-00-00 00:00:00') {
            // wenn Wert vorhanden ist direkt zurück
        } elseif (isset($this->params['sql_object']) && $this->params['sql_object']->getValue($this->getName()) != '' && $this->params['sql_object']->getValue($this->getName()) != '0000-00-00 00:00:00') {
            // sql object vorhanden und Wert gesetzt ?
        } else {
            $value = $default_value;
        }

        $this->setValue($value);
    }

    public function enterObject()
    {
        if ($this->needsOutput() && $this->getElement('show_value') == 1) {
            if ($this->showValue != '') {
                $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php', ['showValue' => $this->showValue]);
            } elseif ($this->getValue() != '') {
                $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'datestamp|name|label|[YmdHis/U/dmy/mysql]|[no_db]|[0-always,1-only if empty,2-never]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'datestamp',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'format' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_datestamp_format'), 'notice' => rex_i18n::msg('yform_values_datestamp_notice')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'only_empty' => ['type' => 'choice',  'label' => rex_i18n::msg('yform_values_datestamp_only_empty'), 'default' => '0', 'choices' => 'translate:yform_always=0,translate:yform_onlyifempty=1,translate:yform_never=2'],
                'show_value' => ['type' => 'checkbox',  'label' => rex_i18n::msg('yform_values_defaults_showvalue'), 'default' => '0', 'options' => '0,1'],
            ],
            'description' => rex_i18n::msg('yform_values_datestamp_description'),
            'db_type' => ['datetime'],
            'multi_edit' => false,
        ];
    }

    public static function getListValue($params)
    {
        $return = self::datestamp_getValueByFormat($params['subject'], $params['params']['field']['format']);
        return ($return == '') ? '-' : $return;
    }

    public static function datestamp_getValueByFormat($value, $format)
    {
        if ($value == '0000-00-00 00:00:00') {
            $return = '';
        } elseif ($format == '') {
            $return = $value;
        } else {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
            if ($date) {
                $return = $date->format($format);
            } else {
                $return = '';
            }
        }
        return $return;
    }

    public static function getSearchFilter($params)
    {
        $sql = rex_sql::factory();

        $value = $params['value'];
        $field = $sql->escapeIdentifier($params['field']->getName());

        preg_match('/^\s*(<|<=|>|>=|<>|!=)?\s*(.*)$/', $value, $match);
        $comparator = $match[1] ?: '=';
        $value = $match[2];
        return ' ' . $field . ' ' . $comparator . ' ' . $sql->escape($value);
    }
}
