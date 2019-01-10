<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_callback extends rex_yform_action_abstract
{
    public function executeAction()
    {
        if (!$this->getElement(2)) {
            return false;
        }
        $f = $this->getElement(2);

        if (is_callable($f)) {
            call_user_func($f, $this);
        } elseif (strpos($f, '::') !== false) {
            $f = explode('::', $f, 2);
            if (is_callable($f[0], $f[1])) {
                call_user_func($f, $this);
            }
        } elseif (function_exists($f)) {
            $f($this);
        }
    }

    public function getDescription()
    {
        return 'action|callback|mycallback / myclass::mycallback';
    }
}
