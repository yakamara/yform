<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_action_encrypt_value extends rex_yform_action_abstract
{
    public function executeAction()
    {
        $f = $this->getElement(3); // the function
        if (!function_exists($f)) {
            $f = 'md5';
        } // default func = md5

        // Labels to get
        $l = explode(',', $this->getElement(2));

        // Label to save in
        $ls = @$this->getElement(4);
        if ($ls == '') {
            $ls = $l[0];
        }
        if ($ls == '') {
            return false;
        }

        // $this->params["value_pool"]["sql"] = Array for database
        $k = '';
        foreach ($this->params['value_pool']['sql'] as $key => $value) {
            if (in_array($key, $l)) {
                $k .= $value;
            }
        }

        if ($k != '') {
            $this->params['value_pool']['sql'][$ls] = $f($k);
            $this->params['value_pool']['email'][$ls] = $f($k);
        }
    }

    public function getDescription()
    {
        return 'action|encrypt|label[,label2,label3]|md5|[save_in_this_label]';
    }
}
