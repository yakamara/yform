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

#[AsValue('fieldset')]
class Fieldset extends AbstractValue
{
    public static $fieldset_options = ['onlyclose' => 'onlyclose', 'onlycloseall' => 'onlycloseall', 'onlyopen' => 'onlyopen', 'closeandopen' => 'closeandopen'];

    public function enterObject()
    {
        if (!$this->needsOutput() || !$this->isViewable()) {
            return;
        }

        $output = '';

        $option = $this->getElement('options');
        if (!in_array($option, self::$fieldset_options)) {
            $option = 'closeandopen';
        }

        $attributes = $this->getElement('attributes');
        $attributes = json_decode($attributes, true);

        // deprecated
        // BC yform < Version 4
        if ('' != $this->getElement('attributes') && !is_array($attributes)) {
            $attributes['class'] = $this->getElement('attributes');
        }

        $this->setElement('attributes', $attributes);

        switch ($option) {
            case 'closeandopen':
            case 'onlyclose':
                if ($this->params['fieldsets_opened'] > 0) {
                    $output .= $this->parse('fieldset', ['option' => 'close', 'attributes' => $attributes]);
                    --$this->params['fieldsets_opened'];
                }
                break;
            case 'onlycloseall':
                for ($i = 0; $i < $this->params['fieldsets_opened']; ++$i) {
                    $output .= $this->parse('fieldset', ['option' => 'close', 'attributes' => $attributes]);
                }
                $this->params['fieldsets_opened'] = 0;
                break;
            case 'onlyopen':
                break;
        }

        switch ($option) {
            case 'closeandopen':
            case 'onlyopen':
                $this->params['fieldsets_opened']++;
                $output .= $this->parse('fieldset', ['option' => 'open', 'attributes' => $attributes]);
                break;
        }

        $this->params['form_output'][$this->getId()] = $output;
    }

    public function getDescription(): string
    {
        return 'fieldset|name|label|[attributes]|[onlyclose/onlycloseall/onlyopen/closeandopen]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'fieldset',
            'values' => [
                'name' => ['type' => 'name',  'label' => I18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',  'label' => I18n::msg('yform_values_defaults_label')],
                'attributes' => ['type' => 'text',    'label' => I18n::msg('yform_values_defaults_attributes'), 'notice' => I18n::msg('yform_values_defaults_attributes_notice')],
                'options' => ['type' => 'choice', 'label' => I18n::msg('yform_values_defaults_options'), 'choices' => self::$fieldset_options, 'default' => 'onlyopen'],
            ],
            'description' => I18n::msg('yform_values_fieldset_description'),
            'db_type' => ['none'],
            'is_searchable' => false,
            'is_hiddeninlist' => true,
            'multi_edit' => 'always',
        ];
    }
}
