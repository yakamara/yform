<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_datetime extends rex_yform_value_abstract
{

    function preValidateAction()
    {
        if ($this->getElement('current_date') == 1 && $this->params['send'] == 0 && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d H:i:00'));

        }

        if (is_array($this->getValue())) {
            $a = $this->getValue();

            $year = (int) substr(@$a['year'], 0, 4);
            $month = (int) substr(@$a['month'], 0, 2);
            $day = (int) substr(@$a['day'], 0, 2);
            $hour = (int) substr(@$a['hour'], 0, 2);
            $min = (int) substr(@$a['min'], 0, 2);

            $r =
                str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                str_pad($day, 2, '0', STR_PAD_LEFT) . ' ' .
                str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' .
                str_pad($min, 2, '0', STR_PAD_LEFT) . ':00';

            $this->setValue($r);
        }
    }


    function enterObject()
    {

        $r = $this->getValue();

        $day = '00';
        $month = '00';
        $year = '0000';
        $hour = '00';
        $minute = '00';

        if ($r != '') {
            $year = (int) substr($this->getValue(), 0, 4);
            $month = (int) substr($this->getValue(), 5, 2);
            $day = (int) substr($this->getValue(), 8, 2);
            $hour = (int) substr($this->getValue(), 11, 2);
            $minute   = (int) substr($this->getValue(), 14, 2);
        }

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);

        $isodatum = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, 0);

        $this->params['value_pool']['email'][$this->getName()] = $isodatum;

        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $isodatum;
        }

        // ------------- year

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

        // ------------- hour

        $hours = array();
        for ($i = 0; $i < 24; $i++) {
            $hours[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        // ------------- min

        if ($this->getElement('minutes') != '') {
            $minutes = explode(',', trim($this->getElement('minutes')));
        } else {
            $minutes = array();
            for ($i = 0; $i < 60; $i++) {
                $minutes[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }
        }

        // -------------

        $layout = $this->getElement('layout');

        if ($layout == '') {
            $layout = '###Y###-###M###-###D### ###H###h ###I###m';
        }
        $layout = preg_split('/(?<=###[YMDHI]###)(?=.)|(?<=.)(?=###[YMDHI]###)/', $layout);

        $this->params['form_output'][$this->getId()] = $this->parse(
            'value.datetime.tpl.php',
            compact('layout', 'yearStart', 'yearEnd', 'hours', 'minutes', 'year', 'month', 'day', 'hour', 'minute')
        );
    }


    function getDescription()
    {
        return 'datetime -> Beispiel: datetime|name|label| jahrstart | jahrsende | minutenformate 00,15,30,45 | [Anzeigeformat ###Y###-###M###-###D### ###H###h ###I###m] |[1/Aktuelles Datum voreingestellt]|[no_db]';
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
                'layout'     => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_datetime_layout")),
                'current_date' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_datetime_current_date")),
                'no_db'      => array( 'type' => 'no_db', 'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
            ),
            'description' => 'Datum & Uhrzeit Eingabe',
            'dbtype' => 'datetime'
        );
    }

    static function getListValue($params)
    {

        $format = rex_i18n::msg('yform_format_datetime');
        if (($d = DateTime::createFromFormat('Y-m-d H:i:s', $params['subject'])) && $d->format('Y-m-d H:i:s') == $params['subject']) {
            return $d->format($format);
        }
        return '[' . $params['subject'] . ']';
    }

}
