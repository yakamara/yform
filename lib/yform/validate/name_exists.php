<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_name_exists extends rex_yform_validate_abstract
{
    public function enterObject()
    {
        if ($this->params['send'] == '1') {

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
                    if ($this->isObject($Object)) {
                        if (in_array($Object->getName(), $fields)) {
                            $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                        }
                    }
                }
            }
        }
    }

    public function getDescription()
    {
        return 'validate|name_exists|name,name2,name3|[minnames]|[maximalnames]|Fehlermeldung';
    }
}
