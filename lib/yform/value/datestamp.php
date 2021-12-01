<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datestamp extends rex_yform_value_abstract
{
    public function preValidateAction(): void
    {
        $format = rex_sql::FORMAT_DATETIME;
        $default_value = date($format);
        $value = $this->getValue();
        $this->showValue = self::datestamp_getValueByFormat($value, $this->getElement('format'));

        if (2 == $this->getElement('only_empty')) {
            // wird nicht gesetzt
        } elseif (1 != $this->getElement('only_empty')) { // -> == 0
            // wird immer neu gesetzt
            $value = $default_value;
        } elseif ('' != $this->getValue() && '0000-00-00 00:00:00' != $this->getValue()) {
            // wenn Wert vorhanden ist direkt zurück
        } elseif (isset($this->params['sql_object']) && '' != $this->params['sql_object']->getValue($this->getName()) && '0000-00-00 00:00:00' != $this->params['sql_object']->getValue($this->getName())) {
            // sql object vorhanden und Wert gesetzt ?
        } else {
            $value = $default_value;
        }

        $this->setValue($value);
    }

    public function enterObject()
    {
        if ($this->needsOutput() && 1 == $this->getElement('show_value')) {
            if ('' != $this->showValue) {
                $this->params['form_output'][$this->getId()] = $this->parse('value.showvalue.tpl.php', ['showValue' => $this->showValue]);
            } elseif ('' != $this->getValue()) {
                $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'datestamp|name|label|[YmdHis/U/dmy/mysql]|[no_db]|[0-always,1-only if empty,2-never]';
    }

    public function getDefinitions(): array
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
        return ('' == $return) ? '-' : $return;
    }

    public static function datestamp_getValueByFormat($value, $format)
    {
        if ('0000-00-00 00:00:00' == $value) {
            $return = '';
        } elseif ('' == $format) {
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

    public static function getSearchField($params)
    {
        rex_yform_value_datetime::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return rex_yform_value_datetime::getSearchFilter($params);
    }
}
