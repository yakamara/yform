<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datetime extends rex_yform_value_abstract
{

    const VALUE_DATETIME_DEFAULT = 'YYYY/MM/DD HH:ii:ss';

    function preValidateAction()
    {

        if ($this->getElement('current_date') == 1 && $this->params['send'] == 0 && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d H:i:00'));

        } else {

            // if not isodate / fallback / BC
            $value = $this->getValue();
            if (is_string($value) && strlen($value) == 14) {

                // 20000101152500
                $year = (int) substr($value, 0, 4);
                $month = (int) substr($value, 4, 2);
                $day = (int) substr($value, 6, 2);
                $hour = (int) substr($value, 8, 2);
                $minute = (int) substr($value, 10, 2);
                $second = (int) substr($value, 12, 2);

                $value =
                    str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                    str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                    str_pad($day, 2, '0', STR_PAD_LEFT) . ' ' .
                    str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($minute, 2, '0', STR_PAD_LEFT) . ':'.
                    str_pad($second, 2, '0', STR_PAD_LEFT);

                $this->setValue($value);
            }
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

        // value set: isodateformat

    }

    private function datetime_getFormat(){
        $format = $this->getElement('format');
        if ($format == '') {
            $format = self::VALUE_DATETIME_DEFAULT;
        }
        return $format;
    }

    static function datetime_convertIsoDatetimeToFormat($iso_datestring, $format)
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

    static function datetime_convertFromFormatToIsoDatetime($datestring, $format)
    {

        $year = 0;
        $pos = strpos($format, "YYYY");
        if ($pos !== false) {
            $year = (int) substr($datestring, $pos, 4);
        }

        $month = 0;
        $pos = strpos($format, "MM");
        if ($pos !== false) {
            $month = (int) substr($datestring, $pos, 2);
        }

        $day = 0;
        $pos = strpos($format, "DD");
        if ($pos !== false) {
            $day = (int) substr($datestring, $pos, 2);
        }

        $hour = 0;
        $pos = strpos($format, "HH");
        if ($pos !== false) {
            $hour = (int) substr($datestring, $pos, 2);
        }

        $minute = 0;
        $pos = strpos($format, "ii");
        if ($pos !== false) {
            $minute = (int) substr($datestring, $pos, 2);
        }

        $second = 0;
        $pos = strpos($format, "ss");
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

    function enterObject()
    {

        $format = $this->datetime_getFormat();

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        $yearStart = (int) $this->getElement('year_start');

        if ($yearStart == "0") {
            $yearStart = date("Y");
        }

        if (substr($this->getElement('year_end'),0,1) == "+") {
            $add_years = (int) substr($this->getElement('year_end'),1);
            $yearEnd = date("Y") + $add_years;

        } else {
            $yearEnd = (int) $this->getElement('year_end');

        }

        if ($yearEnd <= $yearStart) {
            $yearEnd = $yearStart + 20;

        }

        $hours = array();
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        if ($this->getElement('minutes') != '') {
            $minutes = explode(',', trim($this->getElement('minutes')));
        } else {
            $minutes = array();
            for ($i = 0; $i < 60; $i++) {
                $minutes[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
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
                array('value.datetime.tpl.php', 'value.datetime.tpl.php'),
                compact('format', 'yearStart', 'yearEnd', 'hours', 'minutes', 'year', 'month', 'day', 'hour', 'minute', 'second')
            );
        }

    }


    function getDescription()
    {
        return 'datetime -> Beispiel: datetime|name|label| jahrstart | jahrsende | minutenformate 00,15,30,45 | [Anzeigeformat YYYY/MM/DD HH:ii:ss] |[1/Aktuelles Datum voreingestellt]|[no_db]';
    }


    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'datetime',
            'values' => array(
                'name'       => array( 'type' => 'name', 'label' => rex_i18n::msg("yform_values_defaults_name") ),
                'label'      => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_label")),
                'year_start' => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_datetime_year_start")),
                'year_end'   => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_datetime_year_end")),
                'minutes'    => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_datetime_minutes")),
                'format'     => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_datetime_format"), 'notice' => rex_i18n::msg("yform_values_datetime_format_notice")),
                'current_date' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_datetime_current_date")),
                'no_db'      => array( 'type' => 'no_db', 'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
                'widget'       => ['type' => 'select', 'label' => rex_i18n::msg("yform_values_defaults_widgets"), 'options' => ['select'=>'select', 'input:text'=>'input:text'], 'default' => 'select'],
                'attributes'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_attributes"), 'notice' => rex_i18n::msg("yform_values_defaults_attributes_notice")),
            ),
            'description' => 'Datum & Uhrzeit Eingabe',
            'dbtype' => 'datetime'
        );
    }

    static function getListValue($params)
    {
        $format = $params['params']['field']['format'];
        if ($format == '') {
            $format = self::VALUE_DATETIME_DEFAULT;
        }

        if (($d = DateTime::createFromFormat('Y-m-d H:i:s', $params['subject'])) && $d->format('Y-m-d H:i:s') == $params['subject']) {
            $replace = ['YYYY'=>'Y','MM'=>'m','DD'=>'d','HH'=>'H','ii'=>'i','ss'=>'s'];
            $format = strtr($format, $replace);
            return $d->format($format);
        }
        return '[' . $params['subject'] . ']';
    }

}
