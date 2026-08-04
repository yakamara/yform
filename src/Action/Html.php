<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsAction('html')]
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
