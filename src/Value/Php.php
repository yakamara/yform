<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;

use function Redaxo\Core\View\escape;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('php')]
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
        return escape('php|name|label|<?php echo date("mdY"); ?>');
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'php',
            'values' => [
                'name' => ['type' => 'name',    'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_label')],
                'php' => ['type' => 'textarea',    'attributes' => ['class' => 'form-control rex-code'], 'label' => I18n::msg('yform_values_php_code')],
            ],
            'description' => I18n::msg('yform_values_php_description'),
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

        // BC
        if ('' == $php) {
            $php = $label;
        }

        ob_start();
        eval('?>' . $php);
        $out = ob_get_clean();

        return $out;
    }
}
