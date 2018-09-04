<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_time extends rex_yform_value_abstract
{
    const VALUE_TIME_DEFAULT = 'HH:ii:ss';

    public function preValidateAction()
    {
        if (is_array($this->getValue())) {
            $a = $this->getValue();

            $hour = (int) @$a['hour'];
            $minute = (int) @$a['minute'];
            $second = (int) @$a['second'];

            $r =
                str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' .
                str_pad($minute, 2, '0', STR_PAD_LEFT) . ':'.
                str_pad($second, 2, '0', STR_PAD_LEFT);

            $this->setValue($r);
        }

        if ($this->params['send']) {
            $value = $this->getValue();

            if (is_array($value)) {
                $hour = (int) substr(@$value['hour'], 0, 2);
                $minute = (int) substr(@$value['minute'], 0, 2);
                $second = (int) substr(@$value['second'], 0, 2);

                $value =
                    str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($minute, 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($second, 2, '0', STR_PAD_LEFT);
            } else {
                // widget: input:text
                $value = self::time_convertFromFormatToIsoTime($this->getValue(), $this->time_getFormat());
            }

            $this->setValue($value);
        }

        // value set: isotimeformat
    }

    private function time_getFormat()
    {
        $format = $this->getElement('format');
        if ($format == '') {
            $format = self::VALUE_TIME_DEFAULT;
        }
        return $format;
    }

    public static function time_convertIsoTimeToFormat($iso_timestring, $format)
    {
        // 13:15:23
        $hour = (int) substr($iso_timestring, 0, 2);
        $minute = (int) substr($iso_timestring, 3, 2);
        $second = (int) substr($iso_timestring, 6, 2);

        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
        $second = str_pad($second, 2, '0', STR_PAD_LEFT);

        $replace = ['HH' => $hour, 'ii' => $minute, 'ss' => $second];
        $timestring = strtr($format, $replace);

        return $timestring;
    }

    public static function time_convertFromFormatToIsotime($timestring, $format)
    {
        $hour = 0;
        $pos = strpos($format, 'HH');
        if ($pos !== false) {
            $hour = (int) substr($timestring, $pos, 2);
        }

        $minute = 0;
        $pos = strpos($format, 'ii');
        if ($pos !== false) {
            $minute = (int) substr($timestring, $pos, 2);
        }

        $second = 0;
        $pos = strpos($format, 'ss');
        if ($pos !== false) {
            $second = (int) substr($timestring, $pos, 2);
        }

        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
        $second = str_pad($second, 2, '0', STR_PAD_LEFT);

        $iso_timestring = sprintf('%02d:%02d:%02d', $hour, $minute, $second);

        return $iso_timestring;
    }

    public function enterObject()
    {
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput()) {
            return;
        }

        $format = $this->time_getFormat();

        if ($this->getElement('hours') != '') {
            $hours = explode(',', trim($this->getElement('hours')));
        } else {
            $hours = [];
            for ($i = 0; $i < 24; ++$i) {
                $hours[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }
        }

        if ($this->getElement('minutes') != '') {
            $minutes = explode(',', trim($this->getElement('minutes')));
        } else {
            $minutes = [];
            for ($i = 0; $i < 60; ++$i) {
                $minutes[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }
        }

        $seconds = [];
        for ($i = 0; $i < 60; ++$i) {
            $seconds[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        $hour = (int) substr($this->getValue(), 0, 2);
        $minute = (int) substr($this->getValue(), 3, 2);
        $second = (int) substr($this->getValue(), 6, 2);

        $input_value = self::time_convertIsoTimeToFormat($this->getValue(), $format);

        if ($this->getElement('widget') == 'input:text') {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'text', 'value' => $input_value]);
        } else {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.time.tpl.php', 'value.datetime.tpl.php'],
                compact('format', 'hours', 'minutes', 'seconds', 'hour', 'minute', 'second')
            );
        }
    }

    public function getDescription()
    {
        return 'time|name|label|[stundenraster 0,1,2,3,4,5]|[minutenraster 00,15,30,45]|';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'time',
            'values' => [
                'name' => ['type' => 'name',   'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_defaults_label')],
                'hours' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_time_hours')],
                'minutes' => ['type' => 'text',   'label' => rex_i18n::msg('yform_values_time_minutes')],
                'format' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_time_format'), 'notice' => rex_i18n::msg('yform_values_time_format_notice')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'widget' => ['type' => 'choice',    'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_time_description'),
            'db_type' => ['time'],
        ];
    }

    public static function getListValue($params)
    {
        $format = $params['params']['field']['format'];
        if ($format == '') {
            $format = self::VALUE_TIME_DEFAULT;
        }

        return self::time_convertIsoTimeToFormat($params['subject'], $format);
    }
}
