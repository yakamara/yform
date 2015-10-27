<?php

/**
 * yform
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_php extends rex_yform_value_abstract
{

    function enterObject()
    {
        ob_start();
        eval('?>' . $this->getElement(2));
        $out = ob_get_contents();
        ob_end_clean();
        $this->params['form_output'][$this->getId()] = $out;
    }

    function getDescription()
    {
        return htmlspecialchars(stripslashes('php -> Beispiel: php|name|<?php echo date("mdY"); ?>'));
    }
    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'php',
            'values' => array(
                'name'      => array( 'type' => 'name',    'label' => 'Feld' ),
                'label'     => array( 'type' => 'textarea',    'label' => 'PHP Code'),
            ),
            'description' => 'Ein PHP Code',
            'dbtype' => 'text',
            'famous' => false
        );
    }
}
