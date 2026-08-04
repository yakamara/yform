<?php

namespace Yakamara\YForm\Action;

use Yakamara\YForm\Attribute\AsAction;

#[AsAction('php')]
class Php extends AbstractAction
{
    public function executeAction(): void
    {
        $php = $this->getElement(2);

        if ('' == $php) {
            return;
        }

        eval('?>' . $php . '<?php ');
    }

    public function getDescription(): string
    {
        return 'action|php|&lt;?php echo date("mdY"); ?&gt;';
    }
}
