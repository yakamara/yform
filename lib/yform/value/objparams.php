<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_objparams extends rex_yform_value_abstract
{

    public function init()
    {
        $key = trim($this->getElement(1));
        $value = trim($this->getElement(2));
        $when = trim($this->getElement(3));

        if ($when != 'runtime') { // -> init
            $this->setObjectParamsValue($key, $value);

        }

    }

    public function enterObject()
    {
        $key = trim($this->getElement(1));
        $value = trim($this->getElement(2));
        $when = trim($this->getElement(3));

        if ($when == 'runtime') {
            $this->setObjectParamsValue($key, $value);

        }

    }

    public function getDescription()
    {
        return 'objparams|key|newvalue|[init/runtime]';
    }

    private function setObjectParamsValue($key, $value)
    {

        switch ($value) {

            case 'false';
                $value = false;
                break;

            case 'true';
                $value = true;
                break;

            default:
                $value = ((string) (int) $value === $value) ? (int) $value : $value;

        }

        $vars = explode('.', $key);
        if (count($vars) == 3) {

            $ObjectType = trim($vars[0]);
            $ObjectName = trim($vars[1]);
            $ElementKey = trim($vars[2]);

            switch ($ObjectType) {
                case 'values':
                case 'validate':
                case 'actions':
                    break;
                default:
                    $ObjectType = 'values';
            }

            foreach ($this->params[$ObjectType] as $valueObject) {
                if ($valueObject->getName() == $ObjectName) {
                    $valueObject->setElement($ElementKey, $value);
                }
            }

        } else {
            $this->params[$key] = $value;
        }

    }


}
