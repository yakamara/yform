<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('resetbutton')]
class ResetButton extends AbstractValue
{
    public function enterObject()
    {
        $this->setValue($this->getElement(3));

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('resetbutton');
        }
    }

    public function getDescription(): string
    {
        return 'resetbutton|name|label|value|cssclassname';
    }
}
