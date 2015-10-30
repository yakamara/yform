<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_labelexist extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            // optional, ein oder mehrere felder müssen ausgefüllt sein
            if ($this->getElement(3) == '') {
                $minamount = 1;
            } else {
                $minamount = (int) $this->getElement(3);
            }

            if ($this->getElement(4) == '') {
                $maxamount = 1000;
            } else {
                $maxamount = (int) $this->getElement(4);
            }


            // labels auslesen
            $fields = explode(',', $this->getElement(2));

            $value = 0;
            foreach ($this->obj as $o) {
                if (in_array($o->getName(), $fields) && $o->getValue() != '') {
                    $value++;
                }
            }

            if ($value < $minamount || $value > $maxamount) {
                $this->params['warning_messages'][] = $this->getElement(5);

                foreach ($this->obj as $o) {
                    if (in_array($o->getName(), $fields)) {
                        $this->params['warning'][$o->getId()] = $this->params['error_class'];
                    }
                }
            }
        }
    }

    function getDescription()
    {
        return 'labelexist -> mindestens ein feld muss ausgefüllt sein, example: validate|labelexist|label,label2,label3|[minlabels]|[maximallabels]|Fehlermeldung';
    }
}
