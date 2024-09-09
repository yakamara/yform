<?php

namespace Yakamara\YForm\Action;

class CopyValue extends AbstractAction
{
    public function executeAction(): void
    {
        $label_from = $this->getElement(2);
        $label_to = $this->getElement(3);

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if ($label_from == $key) {
                $this->params['value_pool']['sql'][$label_to] = $value;
                break;
            }
        }
    }

    public function getDescription(): string
    {
        return 'action|copy_value|label_from|label_to';
    }
}
