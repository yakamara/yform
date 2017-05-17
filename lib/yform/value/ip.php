<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_ip extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $sk = 'REMOTE_ADDR';
        if ($this->getElement(3) != '') {
            $sk = $this->getElement(3);
        }

        $this->setValue($_SERVER[$sk]);

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(2) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'ip|name|[no_db]';
    }
}
