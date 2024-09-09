<?php

namespace Yakamara\YForm\Action;

class Html extends AbstractAction
{
    public function executeAction(): void
    {
        $html = $this->getElement(2);
        echo $html;
    }

    public function getDescription(): string
    {
        return 'action|html|&lt;b&gt;fett&lt;/b&gt;';
    }
}
