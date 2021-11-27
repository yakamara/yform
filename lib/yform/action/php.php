<?php

class rex_yform_action_php extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        $php = $this->getElement(2);

        if ('' == $php) {
            return;
        }

        eval('?>'.$php.'<?php ');
    }

    public function getDescription(): string
    {
        return 'action|php|&lt;?php echo date("mdY"); ?&gt;';
    }
}
