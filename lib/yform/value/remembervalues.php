<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_remembervalues extends rex_yform_value_abstract
{

    function postValidateAction()
    {
        if ($this->params['send'] == 0) {
            $fields = explode(',', $this->getElement(3));
            $cookiename = $this->getName();
            if ($cookiename == '') {
                $cookiename = 'dummyremembercookie';
            }
            if (isset($_COOKIE[$cookiename])) {
                $fields = unserialize(base64_decode($_COOKIE[$cookiename]));
            } else {
                $fields = array();
            }
            if (is_array($fields)) {
                foreach ($this->obj as $o) {
                    if (array_key_exists($o->getName(), $fields)) {
                        $o->setValue($fields[$o->getName()]);
                        $this->setValue(1); // checked = ' checked="checked"';
                    }
                }
            }
        }

    }

    function enterObject()
    {
        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.checkbox.tpl.php');
        }
    }

    function postFormAction()
    {
        if ($this->params['send'] == 1) {
            $c = array();
            if ($this->getValue() == 1) {
                $fields = explode(',', $this->getElement(3));
                foreach ($this->obj as $o) {
                    if (in_array($o->getName(), $fields)) {
                        $c[$o->getName()] = $o->getValue();
                    }
                }
            }
            $c = base64_encode(serialize($c));
            $cookiename = $this->getName();
            if ($cookiename == '') {
                $cookiename = 'dummyremembercookie';
            }
            $lastfor = (int) $this->getElement(5);
            if ($lastfor < 3600) {
                $lastfor = 4 * 7 * 24 * 60 * 60;
            } // if < 1 hour -> one month
            setcookie($cookiename, $c , time() + $lastfor, '/');
        }
    }

    function getDescription()
    {
        return 'remembervalues|name|label|label1,label2,label3,label4|opt:default:1/0|opt:dauerinsekunden';
    }

}
