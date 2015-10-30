<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_copy_value extends rex_yform_action_abstract
{

    function executeAction()
    {

        $label_from = $this->getElement(2);
        $label_to = $this->getElement(3);

        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if ($label_from == $key) {
                $this->params['value_pool']['sql'][$label_to] = $value;
                break;
            }
        }

        return;

    }

    function getDescription()
    {
        return 'action|copy_value|label_from|label_to';
    }

}
