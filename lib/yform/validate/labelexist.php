<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_labelexist extends rex_yform_validate_abstract
{
    public function enterObject()
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
            foreach ($this->getObjects() as $Object) {
                if (in_array($Object->getName(), $fields) && $Object->getValue() != '') {
                    ++$value;
                }
            }

            if ($value < $minamount || $value > $maxamount) {
                $this->params['warning_messages'][] = $this->getElement(5);

                foreach ($this->getObjects() as $Object) {
                    if (in_array($Object->getName(), $fields)) {
                        $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                    }
                }
            }
        }
    }

    public function getDescription()
    {
        return 'validate|labelexist|name,name2,name3|[minnames]|[maximalnames]|Fehlermeldung';
    }
}
