<?php

/**
 * XForm.
 *
 * @author p.schulze@bitshifters.de
 * @author <a href="http://www.bitshifters.de">www.bitshifters.de</a>
 */

class rex_yform_value_recaptcha extends rex_yform_value_abstract
{
    public function enterObject()
    {
        if ($this->getElement(2) == '') {
            return;
        }

        $this->params['form_output'][$this->getId()] = $this->parse('value.recaptcha.tpl.php');
    }

    public function getDescription()
    {
        return 'recaptcha|name|public_key|load_script(bool)|[no_db]<br />Beispiel: recaptcha|botcheck|XLKJHD1233-D|true|[no_db]';
    }
}
