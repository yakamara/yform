<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_date extends rex_yform_value_abstract
{
    const VALUE_DATE_DEFAULT = 'YYYY-MM-DD';

    public function preValidateAction()
    {
        // if date is unformated
        $value = $this->getValue();
        if (is_string($value) && $value != '') {
            if (strlen($value) == 8) {
                if ($d = DateTime::createFromFormat('Ymd', $value)) {
                    if ($d->format('Ymd') == $value) {
                        $this->setValue($d->format('Y-m-d'));
                        return;
                    }
                }
            } else {
                if ($d = date_create_from_format('Y-m-d', $value)) {
                    if ($d->format('Y-m-d') == $value) {
                        return;
                    }
                }
            }
        }

        if ($this->getElement('current_date') == 1 && $this->getValue() == '' && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d'));
        }

        if ($this->params['send']) {
            $value = $this->getValue();

            if (is_array($value)) {
                // widget: choice
                $year = (int) substr(@$value['year'], 0, 4);
                $month = (int) substr(@$value['month'], 0, 2);
                $day = (int) substr(@$value['day'], 0, 2);

                $value =
                str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // widget: input:text
                $value = self::date_convertFromFormatToIsoDate($this->getValue(), self::date_getFormat($this->getElement('format')));
            }

            $this->setValue($value);
        }
    }

    public static function date_getFormat($format)
    {
        if ($format == '') {
            $format = self::VALUE_DATE_DEFAULT;
        }
        return $format;
    }

    public static function date_convertIsoDateToFormat($iso_datestring, $format)
    {
        // 2010-12-31 13:15:23
        $year = (int) substr($iso_datestring, 0, 4);
        $month = (int) substr($iso_datestring, 5, 2);
        $day = (int) substr($iso_datestring, 8, 2);

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $replace = ['YYYY' => $year, 'MM' => $month, 'DD' => $day];
        $datestring = strtr($format, $replace);

        return $datestring;
    }

    public static function date_convertFromFormatToIsoDate($datestring, $format)
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

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $iso_datestring = sprintf('%04d-%02d-%02d', $year, $month, $day);

        return $iso_datestring;
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

        $format = self::date_getFormat($this->getElement('format'));

        $yearStart = (int) $this->getElement('year_start');

        if ($yearStart == '0') {
            $yearStart = date('Y');
        }

        if (substr($this->getElement('year_end'), 0, 1) == '+') {
            $add_years = (int) substr($this->getElement('year_end'), 1);
            $yearEnd = date('Y') + $add_years;
        } else {
            $yearEnd = (int) $this->getElement('year_end');
        }

        if ($yearEnd <= $yearStart) {
            $yearEnd = $yearStart + 20;
        }

        $year = (int) substr($this->getValue(), 0, 4);
        $month = (int) substr($this->getValue(), 5, 2);
        $day = (int) substr($this->getValue(), 8, 2);

        $input_value = self::date_convertIsoDateToFormat($this->getValue(), $format);

        if ($this->getElement('widget') == 'input:text') {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'text', 'value' => $input_value]);

            // } else if ($this->getElement('widget') == 'input:date') {
            // wird im moment nicht genutzt.
            // $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'date']);
        } else {
            $this->params['form_output'][$this->getId()] = $this->parse(
            ['value.date.tpl.php', 'value.datetime.tpl.php'],
            compact('format', 'yearStart', 'yearEnd', 'year', 'month', 'day', 'value')
            );
        }
    }

    public function getDescription()
    {
        return 'date|name|label| jahrstart | [jahrende/+5 ]| [Anzeigeformat YYYY-MM-DD] | [1/Aktuelles Datum voreingestellt] | [no_db] ';
    }

    public function getDefinitions($values = [])
    {
        return [
            'type' => 'value',
            'name' => 'date',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'year_start' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_year_start')],
                'year_end' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_year_end')],
                'format' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_date_format'), 'notice' => rex_i18n::msg('yform_values_date_format_notice')],
                'current_date' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_date_current_date')],
                'no_db' => ['type' => 'no_db',   'label' => rex_i18n::msg('yform_values_defaults_table')],
                'widget' => ['type' => 'choice', 'label' => rex_i18n::msg('yform_values_defaults_widgets'), 'choices' => ['select' => 'select', 'input:text' => 'input:text'], 'default' => 'select'],
                'attributes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_attributes'), 'notice' => rex_i18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_date_description'),
            'db_type' => ['date'],
            'famous' => true,
        ];
    }

    public static function getListValue($params)
    {
        $format = '';

        if (isset($params['params']['field']['format'])) {
            $format = $params['params']['field']['format'];
        }

        if ($format == '') {
            $format = self::VALUE_DATE_DEFAULT;
        }

        return self::date_convertIsoDateToFormat($params['subject'], $format);
    }

    public static function getSearchField($params)
    {
        // 01/15/2015 - 02/15/2015
        $format = self::date_getFormat($params['field']->getElement('format'));
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
                $date = self::date_convertFromFormatToIsoDate($date, $format);
                return '(' . $sql->escapeIdentifier($field) . ' ' . $firstchar . ' ' . $sql->escape($date) . ')';
                break;
        }

        // date
        if (strlen($value) == $format_len) {
            $date = self::date_convertFromFormatToIsoDate($value, $format);
            return '(' . $sql->escapeIdentifier($field) . ' = ' . $sql->escape($date) . ')';
        }

        $dates = explode(' - ', $value);
        if (count($dates) == 2) {
            // daterange
            $date_from = self::date_convertFromFormatToIsoDate($dates[0], $format);
            $date_to = self::date_convertFromFormatToIsoDate($dates[1], $format);

            return ' (
            ' . $sql->escapeIdentifier($field) . '>= ' . $sql->escape($date_from) . ' and
            ' . $sql->escapeIdentifier($field) . '<= ' . $sql->escape($date_to) . '
            ) ';
        }

        // wenn alles nicht hilfe -> plain rein
        return '(' . $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value) . ')';
    }
}
