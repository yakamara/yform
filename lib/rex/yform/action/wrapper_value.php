<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_wrapper_value extends rex_yform_action_abstract
{
    function executeAction()
    {
        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if ($this->getElement(2) == $key) {
                $this->params['value_pool']['sql'][$key] = str_replace('###value###', $this->params['value_pool']['sql'][$key], $this->getElement(3));
                break;
            }
        }
        return;
    }

    function getDescription()
    {
        return 'action|wrapper_value|label|prefix###value###suffix';
    }
}
