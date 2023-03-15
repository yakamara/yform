<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datestamp extends rex_yform_value_abstract
{
    private string $value_datestamp_currentValue = '';

    public function preValidateAction(): void
    {
        $default_value = date(rex_sql::FORMAT_DATETIME);
        if ('' != $this->getElement('modify_default')) {
            $dt = new DateTime();
            if (false !== @$dt->modify($this->getElement('modify_default'))) {
                $default_value = $dt->format(rex_sql::FORMAT_DATETIME);
            }
        }

        $value = $this->getValue() ?? '';
        $this->value_datestamp_currentValue = $value;

        switch ($this->getElement('only_empty')) {
            case 2:
                // wird nicht gesetzt
                break;
            case 1:
                // nur wenn leer, oder neu
                if ('0000-00-00 00:00:00' == $value || '' == $value || $this->params['main_id'] < 1) {
                    $value = $default_value;
                    $this->value_datestamp_currentValue = '';
                }
                break;
            case 0:
            default:
                // wird immer neu gesetzt
                $value = $default_value;
        }
        $this->setValue($value);
    }

    public function enterObject()
    {
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getValue() && $this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput() && !$this->isViewable()) {
            return;
        }

        $this->params['form_output'][$this->getId()] = '';
        if ($this->needsOutput() && $this->isViewable() && !rex::isFrontend()) {
            if ($this->isEditable()) {
                $notice = '';
                if ($this->value_datestamp_currentValue != $this->getValue()) {
                    $notice = 'translate:yform_values_datestamp_update_notice';
                }
                $this->params['form_output'][$this->getId()] .= $this->parse(
                    ['value.datestamp-view.tpl.php', 'value.datetime-view.tpl.php', 'value.date-view.tpl.php', 'value.view.tpl.php'],
                    [
                        'type' => 'text',
                        'value' => ('' != $this->value_datestamp_currentValue) ? rex_yform_value_datetime::datetime_getFormattedDatetime($this->getElement('format'), $this->value_datestamp_currentValue) : '',
                        'notice' => $notice,
                    ]
                );
            } elseif ('' != $this->value_datestamp_currentValue) {
                $this->params['form_output'][$this->getId()] .= $this->parse(
                    ['value.datestamp-view.tpl.php', 'value.datetime-view.tpl.php', 'value.date-view.tpl.php', 'value.view.tpl.php'],
                    [
                        'type' => 'text',
                        'value' => rex_yform_value_datetime::datetime_getFormattedDatetime($this->getElement('format'), $this->value_datestamp_currentValue),
                    ]
                );
            }
        }

        $this->params['form_output'][$this->getId()] .= $this->parse('value.hidden.tpl.php');
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
                'format' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_datetime_format'), 'choices' => rex_yform_value_datetime::VALUE_DATETIME_FORMATS, 'default' => rex_yform_value_datetime::VALUE_DATETIME_DEFAULT_FORMAT, 'notice' => rex_i18n::msg('yform_values_format_show_notice')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'only_empty' => ['type' => 'choice',  'label' => rex_i18n::msg('yform_values_datestamp_only_empty'), 'default' => '0', 'choices' => 'translate:yform_always=0,translate:yform_onlyifempty=1,translate:yform_never=2'],
                'modify_default' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_date_modify_default'), 'notice' => rex_i18n::msg('yform_values_date_modify_default_notics')],
            ],
            'description' => rex_i18n::msg('yform_values_datestamp_description'),
            'db_type' => ['datetime'],
            'multi_edit' => false,
        ];
    }

    public static function getListValue($params)
    {
        return rex_yform_value_datetime::getListValue($params);
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
