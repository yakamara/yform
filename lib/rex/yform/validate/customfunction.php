<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_validate_customfunction extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $label = $this->getElement('name');
            $func = $this->getElement('function');
            $parameter = $this->getElement('params');

            $comparator = true;
            if (is_string($func) && substr($func, 0, 1) == '!') {
                $comparator = false;
                $func = substr($func, 1);
            }

            foreach ($this->obj_array as $object) {
                if (!is_callable($func)) {
                    $this->params['warning'][$object->getId()] = $this->params['error_class'];
                    $this->params['warning_messages'][$object->getId()] = 'ERROR: customfunction "' . $func . '" not found';
                } elseif (call_user_func($func, $label, $object->getValue(), $parameter) === $comparator) {
                    $this->params['warning'][$object->getId()] = $this->params['error_class'];
                    $this->params['warning_messages'][$object->getId()] = $this->getElement('message');
                }
            }
        }
    }

    function getDescription()
    {
        return 'customfunction -> prüft über customfunc, beispiel: validate|customfunction|label|[!]function/class::method|weitere_parameter|warning_message';
    }

    function getDefinitions()
    {
        return array(
            'type' => 'validate',
            'name' => 'customfunction',
            'values' => array(
                'name'     => array( 'type' => 'select_name', 'label' => 'Name'),
                'function' => array( 'type' => 'text',  'label' => 'Name der Funktion' ),
                'params'   => array( 'type' => 'text',   'label' => 'Weitere Parameter'),
                'message'  => array( 'type' => 'text',   'label' => 'Fehlermeldung'),
            ),
            'description' => 'Mit eigener Funktion vergleichen',
        );

    }

}
