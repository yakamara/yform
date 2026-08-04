<?php

namespace Yakamara\YForm\Value;

use Yakamara\YForm\Attribute\AsValue;
use Redaxo\Core\Translation\I18n;

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

#[AsValue('signature')]
class Signature extends AbstractValue
{
    public function enterObject()
    {
        $this->setValue((string) $this->getValue());

        if ('' == $this->getValue() && !$this->params['send']) {
            $this->setValue($this->getElement('default'));
        }

        $this->params['form_output'][$this->getId()] = $this->parse('signature');
        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();

        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'signature|name|label|defaultwert|[no_db]|[attributes]|[notice]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'signature',
            'values' => [
                'name' => ['type' => 'name', 'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_label')],
                'default' => ['type' => 'text', 'label' => I18n::msg('yform_values_text_default')],
                'no_db' => ['type' => 'no_db', 'label' => I18n::msg('yform_values_defaults_table'),  'default' => 0],
                'attributes' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
                'notice' => ['type' => 'text', 'label' => I18n::msg('yform_values_defaults_notice')],
            ],
            'description' => 'Fügt ein Zeichenfeld ein, in dem eine <b>Unterschrift</b> niedergeschrieben werden kann.',
            'db_type' => ['text'],
            'famous' => false,
            'search' => false,
        ];
    }

    public static function getListValue($params)
    {
        return '' == $params['subject'] ? '<i>ungesetzt</i>' : '<img src="' . $params['subject'] . '" style="width: auto; max-height: 30px; height: 100%;">';
    }
}
