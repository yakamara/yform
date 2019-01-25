<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_generate_password extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $this->setValue(mb_substr(md5(microtime() . rand(1000, getrandmax())), 0, 6));
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement(2) != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription()
    {
        return 'generate_password|name|[no_db]';
    }

    public function isDeprecated()
    {
        return true;
    }
}
