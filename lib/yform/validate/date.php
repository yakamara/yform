<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_date extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $Object = $this->getValueObject($this->getElement("name"));
            $date = $Object->getValue();
            if ($date == '') {
                return;
            }
            $regex='/^\d{2}\.\d{2}\.(\d{2}|\d{4})$/';
            $date = $Object->getValue();
            // Datum auf Format prüfen
            if (preg_match($regex,$date)){
                // Datum auf validität prüfen
                $d = substr($date,0,2);
                $m = substr($date,3,2);
                $y = substr($date,6,4);
                if (checkdate($m,$d,$y)){
                    return;
                }
            }
            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
        }
    }

    function getDescription()
    {
        return 'date -> Auf ein Datum der Form tt.mm.yyyy prüfen, beispiel: validate|date|datumsfeld|warning_message';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'size',
            'values' => array(
                'name'    => array( 'type' => 'select_name', 'label' => 'Name' ),
                'message' => array( 'type' => 'text', 'label' => 'Fehlermeldung'),
            ),
            'description' => 'Hiermit wird ein Label überprüft ob es ein Datum der Form tt.mm.yyyy hat.',
        );

    }
}
