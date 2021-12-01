<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_callback extends rex_yform_action_abstract
{
    public function executeAction(): void
    {
        if (!$this->getElement(2)) {
            return;
        }
        call_user_func($this->getElement(2), $this);
    }

    public function getDescription(): string
    {
        return 'action|callback|mycallback / myclass::mycallback';
    }
}
