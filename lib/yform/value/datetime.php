<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datetime extends rex_yform_value_abstract
{
    const
        VALUE_DATETIME_DEFAULT = 'YYYY-MM-DD HH:ii:ss';
    const VALUE_DATETIME_FORMATS = ['DD.MM.YYYY HH:ii' => 'DD.MM.YYYY HH:ii', 'YYYY-MM-DD HH:ii:ss' => 'YYYY-MM-DD HH:ii:ss', 'DD-MM-YYYY HH:ii:ss' => 'DD-MM-YYYY HH:ii:ss', 'MM-DD-YYYY HH:ii:ss' => 'MM-DD-YYYY HH:ii:ss', 'MM-YYYY HH:ii:ss' => 'MM-YYYY HH:ii:ss', 'YYYY-MM HH:ii:ss' => 'YYYY-MM HH:ii:ss', 'DD-MM HH:ii:ss' => 'DD-MM HH:ii:ss', 'MM-DD HH:ii:ss' => 'MM-DD HH:ii:ss'];

    public function preValidateAction()
    {
        // if date is unformated
        $value = $this->getValue();
        if (is_string($value) && $value != '') {
            if (strlen($value) == 14) {
                if ($d = DateTime::createFromFormat('YmdHis', $value)) {
                    if ($d->format('YmdHis') == $value) {
                        $this->setValue($d->format('Y-m-d H:i:s'));
                        return;
                    }
                }
            } else {
                if ($d = date_create_from_format('Y-m-d H:i:s', $value)) {
                    if ($d->format('Y-m-d') == $value) {
                        return;
                    }
                }
            }
        }

        if ($this->getElement('current_date') == 1 && $this->getValue() == '' && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d H:i:00'));
            return;
        }

        if ($this->params['send']) {
            $value = $this->getValue();

            if (is_array($value)) {
                // widget: choice
                $year = (int) substr(@$value['year'], 0, 4);
                $month = (int) substr(@$value['month'], 0, 2);
                $day = (int) substr(@$value['day'], 0, 2);
                $hour = (int) substr(@$value['hour'], 0, 2);
                $minute = (int) substr(@$value['minute'], 0, 2);
                $second = (int) substr(@$value['second'], 0, 2);

                $value =
                    str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                    str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                    str_pad($day, 2, '0', STR_PAD_LEFT) . ' ' .
                    str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($minute, 2, '0', STR_PAD_LEFT) . ':'.
                    str_pad($second, 2, '0', STR_PAD_LEFT);
            } else {
                // widget: input:text
                $value = self::datetime_convertFromFormatToIsoDatetime($this->getValue(), $this->datetime_getFormat());
            }

            $this->setValue($value);
        }
    }

    private static function datetime_getFormat($format = '')
    {
        if ($format == '') {
            $format = self::VALUE_DATETIME_DEFAULT;
        }
        return $format;
    }

    public static function datetime_convertIsoDatetimeToFormat($iso_datestring, $format)
    {
        // 2010-12-31 13:15:23
        $year = (int) substr($iso_datestring, 0, 4);
        $month = (int) substr($iso_datestring, 5, 2);
        $day = (int) substr($iso_datestring, 8, 2);
        $hour = (int) substr($iso_datestring, 11, 2);
        $minute = (int) substr($iso_datestring, 14, 2);
        $second = (int) substr($iso_datestring, 17, 2);

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
        $second = str_pad($second, 2, '0', STR_PAD_LEFT);

        $replace = ['YYYY' => $year, 'MM' => $month, 'DD' => $day, 'HH' => $hour, 'ii' => $minute, 'ss' => $second];
        $datestring = strtr($format, $replace);

        return $datestring;
    }

    public static function datetime_convertFromFormatToIsoDatetime($datestring, $format)
    {
        $year = 0;
        $pos = strpos($format, 'YYYY');
        if ($pos !== false) {
            $year = (int) substr($datestring, $pos, 4);
        }

        $month = 0;
        $pos = strpos($format, 'MM');
        if ($pos !== false) {
            $month = (int) substr($datestring, $pos, 2);
        }

        $day = 0;
        $pos = strpos($format, 'DD');
        if ($pos !== false) {
            $day = (int) substr($datestring, $pos, 2);
        }

        $hour = 0;
        $pos = strpos($format, 'HH');
        if ($pos !== false) {
            $hour = (int) substr($datestring, $pos, 2);
        }

        $minute = 0;
        $pos = strpos($format, 'ii');
        if ($pos !== false) {
            $minute = (int) substr($datestring, $pos, 2);
        }

        $second = 0;
        $pos = strpos($format, 'ss');
        if ($pos !== false) {
            $second = (int) substr($datestring, $pos, 2);
        }

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
        $second = str_pad($second, 2, '0', STR_PAD_LEFT);

        $iso_datestring = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);

        return $iso_datestring;
    }

    public function enterObject()
    {
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if (!$this->needsOutput()) {
            return;
        }

        $format = self::datetime_getFormat($this->datetime_getFormat());

        $yearStart = (int) $this->getElement('year_start');

        if ($yearStart == '0') {
            $yearStart = date('Y');
        }

        if (substr($this->getElement('year_end'), 0, 1) == '+') {
            $add_years = (int) substr($this->getElement('year_end'), 1);
            $yearEnd = date('Y') + $add_years;
        } elseif ($this->getElement('year_end') != '') {
            $yearEnd = (int) $this->getElement('year_end');
        } else {
            $yearEnd = date('Y');
        }

        if ($yearEnd <= $yearStart) {
            $yearEnd = $yearStart + 20;
        }

        $hours = [];
        for ($i = 0; $i < 24; ++$i) {
            $hours[$i] = $i;
        }

        if ($this->getElement('minutes') != '') {
            $minutes = explode(',', trim($this->getElement('minutes')));
        } else {
            $minutes = [];
            for ($i = 0; $i < 60; ++$i) {
                $minutes[$i] = $i;
            }
        }

        if ($this->getElement('seconds') != '') {
            $seconds = explode(',', trim($this->getElement('seconds')));
        } else {
            $seconds = [];
            for ($i = 0; $i < 60; ++$i) {
                $seconds[$i] = $i;
            }
        }

        $year = (int) substr($this->getValue(), 0, 4);
        $month = (int) substr($this->getValue(), 5, 2);
        $day = (int) substr($this->getValue(), 8, 2);
        $hour = (int) substr($this->getValue(), 11, 2);
        $minute = (int) substr($this->getValue(), 14, 2);
        $second = (int) substr($this->getValue(), 17, 2);

        $input_value = self::datetime_convertIsoDatetimeToFormat($this->getValue(), $format);

        if ($this->getElement('widget') == 'input:text') {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'text', 'value' => $input_value]);
        } else {
            $this->params['form_output'][$this->getId()] = $this->parse(
                ['value.datetime.tpl.php', 'value.datetime.tpl.php'],
                compact('format', 'yearStart', 'yearEnd', 'hours', 'minutes', 'seconds', 'year', 'month', 'day', 'hour', 'minute', 'second')
            );
        }
    }

    public function getDescription()
    {
        return 'datetime|name|label| jahrstart | jahrsende | minutenformate 00,15,30,45 | [Anzeigeformat YYYY-MM-DD HH:ii:ss] |[1/Aktuelles Datum voreingestellt]|[no_db]';
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'datetime',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'year_start' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_datetime_year_start')],
                'year_end' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_datetime_year_end')],
                'minutes' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_datetime_minutes')],
                'format' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_datetime_format'), 'choices' => self::VALUE_DATETIME_FORMATS, 'default' => self::VALUE_DATETIME_DEFAULT],
                'current_date' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_datetime_current_date')],
                'no_db' => ['type' => 'no_db', 'label' => rex_i18n::msg('yform_values_defaults_table'),  'default' => 0],
                'widget' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => 'Datum & Uhrzeit Eingabe',
            'db_type' => ['datetime'],
        ];
    }

    public static function getListValue($params)
    {
        $format = @$params['params']['field']['format'];
        if (!$format) {
            $format = self::VALUE_DATETIME_DEFAULT;
        }

        if (($d = DateTime::createFromFormat('Y-m-d H:i:s', $params['subject'])) && $d->format('Y-m-d H:i:s') == $params['subject']) {
            $replace = ['YYYY' => 'Y', 'MM' => 'm', 'DD' => 'd', 'HH' => 'H', 'ii' => 'i', 'ss' => 's'];
            $format = strtr($format, $replace);
            return $d->format($format);
        }
        return '[' . $params['subject'] . ']';
    }

    public static function getSearchField($params)
    {
        // 01/15/2015 - 02/15/2015
        $format = self::datetime_getFormat($params['field']->getElement('format'));
        $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel(), 'notice' => rex_i18n::msg('yform_values_date_search_notice', $format), 'attributes' => '{"data-yform-tools-daterangepicker":"' . $format . '"}']);
    }

    public static function getSearchFilter($params)
    {
        // 01/15/2015 - 02/15/2015
        // >19/11/2015
        // <19/11/2015
        // =19/11/2015
        // 19/11/2015
        // 19/11/2015-19/12/2015
        // $value = self::date_convertFromFormatToIsoDate($this->getValue(), self::date_getFormat($this->getElement('format')));

        $value = trim($params['value']);
        if ($value == '') {
            return;
        }

        $sql = rex_sql::factory();
        $format = $params['field']->getElement('format');
        $format_len = strlen($format);
        $field = $params['field']->getName();
        $firstchar = substr($value, 0, 1);

        switch ($firstchar) {
            case '>':
            case '<':
            case '=':
                $date = substr($value, 1);
                $date = self::datetime_convertFromFormatToIsoDatetime($date, $format);
                return '(' . $sql->escapeIdentifier($field) . ' ' . $firstchar . ' ' . $sql->escape($date) . ')';
                break;
        }

        // date
        if (strlen($value) == $format_len) {
            $date = self::datetime_convertFromFormatToIsoDatetime($value, $format);
            return '(' . $sql->escapeIdentifier($field) . ' = ' . $sql->escape($date) . ')';
        }

        $dates = explode(' - ', $value);
        if (count($dates) == 2) {
            // daterange
            $date_from = self::datetime_convertFromFormatToIsoDatetime($dates[0], $format);
            $date_to = self::datetime_convertFromFormatToIsoDatetime($dates[1], $format);

            return ' (
            ' . $sql->escapeIdentifier($field) . '>= ' . $sql->escape($date_from) . ' and
            ' . $sql->escapeIdentifier($field) . '<= ' . $sql->escape($date_to) . '
            ) ';
        }

        // wenn alles nicht hilfe -> plain rein
        return '(' . $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value) . ')';
    }
}
