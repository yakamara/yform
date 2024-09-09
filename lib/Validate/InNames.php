<?php

namespace Yakamara\YForm\Validate;

use function in_array;

class InNames extends AbstractValidate
{
    public function enterObject()
    {
        if ('' == $this->getElement(3)) {
            $minamount = 1;
        } else {
            $minamount = (int) $this->getElement(3);
        }

        if ('' == $this->getElement(4)) {
            $maxamount = 1000;
        } else {
            $maxamount = (int) $this->getElement(4);
        }

        $fields = explode(',', $this->getElement(2));

        $value = 0;
        foreach ($this->getObjects() as $Object) {
            if (in_array($Object->getName(), $fields) && '' != $Object->getValue()) {
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

    public function getDescription(): string
    {
        return 'validate|in_names|name,name2,name3|[minnames]|[maximalnames]|Fehlermeldung';
    }
}
