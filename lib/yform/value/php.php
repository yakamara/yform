<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_php extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $label = $this->getElement('label');
        $php = $this->getElement('php');

        // BC
        if ($php == '') {
            $php = $label;
        }

        ob_start();
        eval('?>' . $php);
        $out = ob_get_contents();
        ob_end_clean();
        $this->params['form_output'][$this->getId()] = $out;
    }

    public function getDescription()
    {
        return htmlspecialchars('php|name|<?php echo date("mdY"); ?>');
    }

    public function getDefinitions()
    {
        return [
            'type' => 'value',
            'name' => 'php',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'php' => ['type' => 'textarea',    'label' => rex_i18n::msg('yform_values_php_code')],
            ],
            'description' => rex_i18n::msg('yform_values_php_description'),
            'dbtype' => 'none',
            'is_hiddeninlist' => true,
            'famous' => false,
            'multi_edit' => 'always',
        ];
    }

    public static function getListValue($params)
    {
        $label = $params['params']['field']['label'];
        $php = $params['params']['field']['php'];
        $list = true;

        // BC
        if ($php == '') {
            $php = $label;
        }

        ob_start();
        eval('?>' . $php);
        $out = ob_get_contents();
        ob_end_clean();

        return $out;
    }
}
