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
        $label = $this->getElement('label');
        $php = $this->getElement('php');

        // BC
        if ($php == "") {
            $php = $label;
        }

        ob_start();
        eval('?>' . $php);
        $out = ob_get_contents();
        ob_end_clean();
        $this->params['form_output'][$this->getId()] = $out;
    }

    function getDescription()
    {
        return htmlspecialchars('php -> Beispiel: php|name|<?php echo date("mdY"); ?>');
    }

    function getDefinitions()
    {
        return array(
            'type' => 'value',
            'name' => 'php',
            'values' => array(
                'name'      => array( 'type' => 'name',    'label' => rex_i18n::msg("yform_values_defaults_name") ),
                'label'      => array( 'type' => 'text',    'label' => rex_i18n::msg("yform_values_defaults_label") ),
                'php'     => array( 'type' => 'textarea',    'label' => rex_i18n::msg("yform_values_php_code")),
            ),
            'description' => rex_i18n::msg("yform_values_php_description"),
            'dbtype' => 'text',
            'is_hiddeninlist' => true,
            'famous' => false,
            'multi_edit' => 'always',
        );
    }

    static function getListValue($params)
    {
        $label = $params['params']['field']['label'];
        $php = $params['params']['field']['php'];
        $list = true;

        // BC
        if ($php == "") {
            $php = $label;
        }

        ob_start();
        eval('?>' . $php);
        $out = ob_get_contents();
        ob_end_clean();

        return $out;

    }

}
