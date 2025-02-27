<?php

namespace Yakamara\YForm\Value;

use function count;

class ObjParams extends AbstractValue
{
    public function init()
    {
        $key = trim($this->getElement(1));
        $value = trim($this->getElement(2));
        $when = trim($this->getElement(3));

        if ('runtime' != $when) { // -> init
            $this->setObjectParamsValue($key, $value);
        }
    }

    public function enterObject()
    {
        $key = trim($this->getElement(1));
        $value = trim($this->getElement(2));
        $when = trim($this->getElement(3));

        if ('runtime' == $when) {
            $this->setObjectParamsValue($key, $value);
        }
    }

    public function getDescription(): string
    {
        return 'objparams|key|newvalue|[init/runtime]';
    }

    private function setObjectParamsValue($key, $value)
    {
        switch ($value) {
            case 'false':
                $value = false;
                break;

            case 'true':
                $value = true;
                break;

            default:
                $value = ((string) (int) $value === $value) ? (int) $value : $value;
        }

        $vars = explode('.', $key);
        if (3 == count($vars)) {
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
