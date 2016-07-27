<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_time extends rex_yform_value_abstract
{

    function preValidateAction()
    {
        if (is_array($this->getValue())) {
            $a = $this->getValue();

            $hour = (int) @$a['hour'];
            $min = (int) @$a['min'];

            $r =
                str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' .
                str_pad($min, 2, '0', STR_PAD_LEFT) . ':00';

            $this->setValue($r);

        }
    }


    function enterObject()
    {

        $r = $this->getValue();

        $hour = '00';
        $minute = '00';

        if ($r != '') {
            $r = explode(':', $r);

            if (count($r) == 1) {
                $hour = (int) substr($r[0], 0, 2);
                $minute = (int) substr($r[0], 2, 2);

            } else {
                if (isset($r[0])) {
                    $hour = (int) substr($r[0], 0, 2);
                }
                if (isset($r[1])) {
                    $minute  = (int) substr($r[1], 0, 2);
                }
            }

        }

        $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
        $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);

        $isotime = $hour . ':' . $minute . ':00';

        $this->params['value_pool']['email'][$this->getName()] = $isotime;

        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $isotime;
        }

        // ------------- hour

        if ($this->getElement('hours') != '') {
            $hours = explode(',', trim($this->getElement('hours')));
        } else {
            $hours = array();
            for ($i = 0; $i < 24; $i++) {
                $hours[$i] = str_pad($i, 2, '0', STR_PAD_LEFT);
            }
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

        $layout = $this->getElement('format');
        if ($layout == '') {
            $layout = '###H###h ###I###m';
        }
        $layout = preg_split('/(?<=###[HI]###)(?=.)|(?<=.)(?=###[HI]###)/', $layout);

        $this->params['form_output'][$this->getId()] = $this->parse(
            array('value.time.tpl.php', 'value.datetime.tpl.php'),
            compact('layout', 'hours', 'minutes', 'hour', 'minute')
        );
    }

    function getDescription()
    {
        return 'time -> Beispiel: time|name|label|[stundenraster 0,1,2,3,4,5]|[minutenraster 00,15,30,45]|[Anzeigeformat ###H###h ###I###m]|[no_db]';
    }


    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'time',
            'values' => array(
                'name'    => array( 'type' => 'name',   'label' => rex_i18n::msg("yform_values_defaults_name")),
                'label'   => array( 'type' => 'text',   'label' => rex_i18n::msg("yform_values_defaults_label")),
                'hours'   => array( 'type' => 'text',   'label' => rex_i18n::msg("yform_values_time_hours")),
                'minutes' => array( 'type' => 'text',   'label' => rex_i18n::msg("yform_values_time_minutes")),
                'format'  => array( 'type' => 'text',   'label' => rex_i18n::msg("yform_values_time_format")),
                'no_db'   => array( 'type' => 'no_db',   'label' => rex_i18n::msg("yform_values_defaults_table"),  'default' => 0),
            ),
            'description' => rex_i18n::msg("yform_values_time_description"),
            'dbtype' => 'time'
        );

    }

    static function getListValue($params)
    {

        $format = rex_i18n::msg('yform_format_time');
        if (($d = DateTime::createFromFormat('H:i:s', $params['subject'])) && $d->format('H:i:s') == $params['subject']) {
            return $d->format($format);
        }
        return '[' . $params['subject'] . ']';
    }


}
