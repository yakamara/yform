<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_date extends rex_yform_value_abstract
{

    const VALUE_DATE_DEFAULT = 'YYYY/MM/DD';
    
    function preValidateAction()
    {

        if ($this->getElement('current_date') == 1 && $this->params['send'] == 0 && $this->params['main_id'] < 1) {
            $this->setValue(date('Y-m-d'));

        } else {

            // if not isodate / fallback / BC
            $value = $this->getValue();
            if (is_string($value) && strlen($value) == 8) {

                // 20000101
                $year = (int) substr($value, 0, 4);
                $month = (int) substr($value, 4, 2);
                $day = (int) substr($value, 6, 2);

                $value =
                    str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                    str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                    str_pad($day, 2, '0', STR_PAD_LEFT);

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

                $value =
                    str_pad($year, 4, '0', STR_PAD_LEFT) . '-' .
                    str_pad($month, 2, '0', STR_PAD_LEFT) . '-' .
                    str_pad($day, 2, '0', STR_PAD_LEFT);


            } else {

                // widget: input:text
                $value = self::date_convertFromFormatToIsoDate($this->getValue(), $this->date_getFormat());

            }

            $this->setValue($value);

        }

        // value set: isodateformat
    }

    private function date_getFormat(){
        $format = $this->getElement('format');
        if ($format == '') {
            $format = self::VALUE_DATE_DEFAULT;
        }
        return $format;
    }

    static function date_convertIsoDateToFormat($iso_datestring, $format)
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

    static function date_convertFromFormatToIsoDate($datestring, $format)
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

        $year = str_pad($year, 4, '0', STR_PAD_LEFT);
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $iso_datestring = sprintf('%04d-%02d-%02d', $year, $month, $day);

        return $iso_datestring;
    }


    function enterObject()
    {
        $format = $this->date_getFormat();

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

        $year = (int) substr($this->getValue(), 0, 4);
        $month = (int) substr($this->getValue(), 5, 2);
        $day = (int) substr($this->getValue(), 8, 2);

        $input_value = self::date_convertIsoDateToFormat($this->getValue(), $format);

        if ($this->getElement('widget') == 'input:text') {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'text', 'value' => $input_value]);

        } else if ($this->getElement('widget') == 'input:date') {
            $this->params['form_output'][$this->getId()] = $this->parse(['value.text.tpl.php'], ['type' => 'date']);

        } else {
            $this->params['form_output'][$this->getId()] = $this->parse(
                array('value.date.tpl.php', 'value.datetime.tpl.php'),
                compact('format', 'yearStart', 'yearEnd', 'year', 'month', 'day', 'value')
            );
        }

    }

    function getDescription()
    {
        return 'date -> Beispiel: date|name|label| jahrstart | [jahrsende/+5 ]| [Anzeigeformat YYYY/MM/DD] | [1/Aktuelles Datum voreingestellt] | [no_db] ';
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
                'format'       => array( 'type' => 'text', 'label' => rex_i18n::msg("yform_values_date_format"), 'notice' => rex_i18n::msg("yform_values_date_format_notice")),
                'current_date' => array( 'type' => 'boolean', 'label' => rex_i18n::msg("yform_values_date_current_date")),
                'no_db'        => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table")),
                'widget'       => ['type' => 'select', 'label' => rex_i18n::msg("yform_values_defaults_widgets"), 'options' => ['select'=>'select', 'input:date'=>'input:date', 'input:text'=>'input:text'], 'default' => 'select'],
                'attributes'   => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_attributes"), 'notice' => rex_i18n::msg("yform_values_defaults_attributes_notice")),
            ),
            'description' => rex_i18n::msg("yform_values_date_description"),
            'search' => true,
            'list_hidden' => false,
            'dbtype' => 'date'
        );
    }

    static function getListValue($params)
    {
        $format = $params['params']['field']['format'];
        if ($format == '') {
            $format = self::VALUE_DATE_DEFAULT;
        }

        return self::date_convertIsoDateToFormat($params['subject'], $format);

    }

}
