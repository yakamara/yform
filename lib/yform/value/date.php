<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_date extends rex_yform_value_abstract
{

    function preValidateAction()
    {
        if ($this->getElement('current_date') == 1 && $this->params['send'] == 0 && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d'));

        }

        if (is_array($this->getValue())) {
            $a = $this->getValue();

            $year = (int) substr(@$a['year'], 0, 4);
            $month = (int) substr(@$a['month'], 0, 2);
            $day = (int) substr(@$a['day'], 0, 2);

            $r =
                str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                str_pad($day, 2, '0', STR_PAD_LEFT);

            $this->setValue($r);
        }

    }


    function enterObject()
    {

        $r = $this->getValue();

        $day = '00';
        $month = '00';
        $year = '0000';

        if ($r != '') {

            if (strlen($r) == 8) {

                // 20000101
                $year = (int) substr($this->getValue(), 0, 4);
                $month = (int) substr($this->getValue(), 4, 2);
                $day = (int) substr($this->getValue(), 6, 2);

            } else {

                // 2000-01-01
                $year = (int) substr($this->getValue(), 0, 4);
                $month = (int) substr($this->getValue(), 5, 2);
                $day = (int) substr($this->getValue(), 8, 2);

            }
        }

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $isodatum = sprintf('%04d-%02d-%02d', $year, $month, $day);

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

        $layout = $this->getElement('layout');
        if ($layout == '') {
            $layout = '###Y###-###M###-###D###';
        }
        $layout = preg_split('/(?<=###[YMD]###)(?=.)|(?<=.)(?=###[YMD]###)/', $layout);

        $this->params['form_output'][$this->getId()] = $this->parse(
            array('value.date.tpl.php', 'value.datetime.tpl.php'),
            compact('layout', 'yearStart', 'yearEnd', 'year', 'month', 'day')
        );
    }


    function getDescription()
    {
        return 'date -> Beispiel: date|name|label| jahrstart | [jahrsende/+5 ]| [Anzeigeformat###Y###-###M###-###D###] | [1/Aktuelles Datum voreingestellt] | [no_db] ';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'date',
            'values' => array(
                'name'         => array( 'type' => 'name', 'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'        => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_defaults_label")),
                'year_start'   => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_date_year_start")),
                'year_end'     => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_date_year_end")),
                'layout'       => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_date_year_format")),
                'current_date' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_date_current_date")),
                'no_db'        => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table")),
            ),
            'description' => rex_i18n::msg("yform_values_date_description"),
            'dbtype' => 'date'
        );
    }

    static function getListValue($params)
    {

        $format = rex_i18n::msg('yform_format_date');
        if (($d = DateTime::createFromFormat('Y-m-d', $params['subject'])) && $d->format('Y-m-d') == $params['subject']) {
            return $d->format($format);
        }
        return '[' . $params['subject'] . ']';
    }

}
