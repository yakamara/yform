<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_time extends rex_yform_value_abstract
{
    public const VALUE_TIME_SHOW_DEFAULT_FORMAT = 'H:i:s';
    public const VALUE_TIME_SHOW_FORMATS = ['H:i:s' => 'H:i:s', 'H:i' => 'H:i', 'H' => 'H', 'G:i' => 'G:i', 'g:i a' => 'g:i a', 'g:i:s a' => 'g:i:s a', 'h:i a' => 'h:i a', 'h:i:s a' => 'h:i:s a'];

    public function preValidateAction(): void
    {
        $value = $this->getValue();
        if (1 == $this->getElement('current_time') && '' == $this->getValue() && $this->params['main_id'] < 1) {
            $value = date('H:i:s');
        } else if (is_array($value)) {
            $hour = (int) @$value['hour'];
            $minute = (int) @$value['minute'];
            $second = (int) @$value['second'];
            $value = sprintf("%02d:%02d:%02d", $hour, $minute, $second);
        } else {
            $value = (string) $value;
        }
        if (1 == $this->getElement('current_time') && '' == $this->getValue() && $this->params['main_id'] < 1) {
            $value = date('H:i:s');
        }
        $this->setValue($value);
    }

    public function enterObject()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $hour = (int) substr(@$value['hour'], 0, 2);
            $minute = (int) substr(@$value['minute'], 0, 2);
            $second = (int) substr(@$value['second'], 0, 2);
            $value = sprintf("%02d:%02d:%02d", $hour, $minute, $second);
        }
        $this->setValue($value);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput() && !$this->isViewable()) {
            return;
        }

        if (!$this->isEditable()) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.time-view.tpl.php', 'value.view.tpl.php'], ['type' => 'text', 'value' => self::time_getFormattedTime($this->getElement('format'), $this->getValue())]);
        } elseif ('input:text' == $this->getElement('widget')) {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.text.tpl.php'], ['type' => 'text', 'value' => $this->getValue()]);
        } else {
            $format = 'HH:ii:ss'; // Format of Select Order
            $hour = (int) substr($this->getValue(), 0, 2);
            $minute = (int) substr($this->getValue(), 3, 2);
            $second = (int) substr($this->getValue(), 6, 2);
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.time.tpl.php', 'value.datetime.tpl.php'], compact('format', 'hour', 'minute', 'second')
            );
        }
    }

    public function getDescription(): string
    {
        return 'time|name|label|[stundenraster 0,1,2,3,4,5]|[minutenraster 00,15,30,45]|';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'time',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_defaults_label')],
                'format' => ['type' => 'choice',    'label' => rex_i18n::msg('yform_values_time_format'), 'choices' => self::VALUE_TIME_SHOW_FORMATS],
                'current_time' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_time_current_time')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'widget' => ['type' => 'choice',    'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_time_description'),
            'db_type' => ['time'],
        ];
    }

    public static function time_getFormattedTime($format, $time)
    {
        $format = (in_array($format, self::VALUE_TIME_SHOW_FORMATS, true)) ? $format : self::VALUE_TIME_SHOW_DEFAULT_FORMAT;
        $hour = (int) substr($time, 0, 2);
        $minute = (int) substr($time, 3, 2);
        $second = (int) substr($time, 6, 2);
        return date($format, mktime($hour, $minute, $second, 1, 1, 2000)); // dummy date
    }

    public static function getListValue($params)
    {
        return '<nobr>'.self::time_getFormattedTime($params['params']['field']['format'], $params['subject']).'</nobr>';
    }

    public static function getSearchField($params)
    {
        rex_yform_value_text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return rex_yform_value_text::getSearchFilter($params);
    }
}
