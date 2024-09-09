<?php

namespace Yakamara\YForm\Value;

use rex_i18n;

class Php extends AbstractValue
{
    public function enterObject()
    {
        $label = $this->getElement('label');
        $php = $this->getElement('php');

        // BC
        if ('' == $php) {
            $php = $label;
        }

        ob_start();
        eval('?>' . $php);
        $out = ob_get_clean();
        $this->params['form_output'][$this->getId()] = $out;
    }

    public function getDescription(): string
    {
        return rex_escape('php|name|label|<?php echo date("mdY"); ?>');
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'php',
            'values' => [
                'name' => ['type' => 'name',    'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'php' => ['type' => 'textarea',    'attributes' => ['class' => 'form-control rex-code'], 'label' => rex_i18n::msg('yform_values_php_code')],
            ],
            'description' => rex_i18n::msg('yform_values_php_description'),
            'db_type' => ['none'],
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

        ob_start();
        eval('?>' . $php);
        $out = ob_get_clean();

        return $out;
    }
}
