<?php

namespace Yakamara\YForm\Value;

class ResetButton extends AbstractValue
{
    public function enterObject()
    {
        $this->setValue($this->getElement(3));

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.resetbutton.tpl.php');
        }
    }

    public function getDescription(): string
    {
        return 'resetbutton|name|label|value|cssclassname';
    }
}
