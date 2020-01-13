<?php

class rex_yform_action_php extends rex_yform_action_abstract
{
    public function executeAction()
    {
        $php = $this->getElement(2);

        if ('' == $php) {
            return false;
        }

        eval('?>'.$php.'<?php');
        return true;
    }

    public function getDescription()
    {
        return 'action|php|&lt;?php echo date("mdY"); ?&gt;';
    }
}
